<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['show', 'track']);
    }

    /**
     * Display checkout page.
     */
    public function checkout(): View
    {
        $cartItems = Cart::getItems();
        
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Tu carrito está vacío.');
        }

        // Validate cart items
        $validation = Cart::validateItems();
        if (!$validation['valid']) {
            return redirect()->route('cart.index')
                ->with('error', 'Hay problemas con algunos productos en tu carrito.');
        }

        $cartSummary = Cart::getSummary();
        
        // Get user's previous addresses for quick selection
        $previousAddresses = Auth::user()->orders()
            ->whereNotNull('billing_address')
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($order) {
                return [
                    'billing' => json_decode($order->billing_address, true),
                    'shipping' => json_decode($order->shipping_address, true),
                ];
            })
            ->unique()
            ->values();

        return view('checkout.index', compact('cartItems', 'cartSummary', 'previousAddresses'));
    }

    /**
     * Process the order.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'billing_address' => 'required|array',
            'billing_address.first_name' => 'required|string|max:100',
            'billing_address.last_name' => 'required|string|max:100',
            'billing_address.email' => 'required|email|max:255',
            'billing_address.phone' => 'required|string|max:20',
            'billing_address.address_line_1' => 'required|string|max:255',
            'billing_address.address_line_2' => 'sometimes|string|max:255',
            'billing_address.city' => 'required|string|max:100',
            'billing_address.state' => 'required|string|max:100',
            'billing_address.postal_code' => 'required|string|max:20',
            'billing_address.country' => 'required|string|max:2',
            
            'shipping_address' => 'sometimes|array',
            'shipping_address.first_name' => 'required_with:shipping_address|string|max:100',
            'shipping_address.last_name' => 'required_with:shipping_address|string|max:100',
            'shipping_address.address_line_1' => 'required_with:shipping_address|string|max:255',
            'shipping_address.address_line_2' => 'sometimes|string|max:255',
            'shipping_address.city' => 'required_with:shipping_address|string|max:100',
            'shipping_address.state' => 'required_with:shipping_address|string|max:100',
            'shipping_address.postal_code' => 'required_with:shipping_address|string|max:20',
            'shipping_address.country' => 'required_with:shipping_address|string|max:2',
            
            'payment_method' => 'required|string|in:stripe,paypal,bank_transfer',
            'notes' => 'sometimes|string|max:500',
            'terms_accepted' => 'required|accepted',
        ]);

        try {
            DB::beginTransaction();

            // Validate cart one more time
            $cartItems = Cart::getItems();
            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Tu carrito está vacío.'
                ]);
            }

            $validation = Cart::validateItems();
            if (!$validation['valid']) {
                throw ValidationException::withMessages([
                    'cart' => 'Hay problemas con algunos productos en tu carrito.'
                ]);
            }

            // Create order from cart
            $order = Order::createFromCart(
                $request->billing_address,
                $request->shipping_address ?? $request->billing_address,
                $request->payment_method,
                $request->notes
            );

            // Clear cart after successful order creation
            Cart::clear();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado exitosamente.',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->formatted_total_amount,
                    'status' => $order->status,
                ],
                'redirect_url' => route('orders.confirmation', $order->order_number),
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pedido. Por favor, inténtalo de nuevo.',
            ], 500);
        }
    }

    /**
     * Show order confirmation page.
     */
    public function confirmation(string $orderNumber): View
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->with(['items.product'])
            ->firstOrFail();

        // Get recommended products based on order items
        $categoryIds = $order->items
            ->pluck('product.categories')
            ->flatten()
            ->pluck('id')
            ->unique();
        
        $recommendedProducts = Product::active()
            ->inStock()
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->whereNotIn('id', $order->items->pluck('product_id'))
            ->featured()
            ->limit(4)
            ->get();

        return view('orders.confirmation', compact('order', 'recommendedProducts'));
    }

    /**
     * Display user's orders.
     */
    public function index(Request $request): View
    {
        $status = $request->get('status');
        $search = $request->get('search');
        
        $ordersQuery = Auth::user()->orders()
            ->with(['items'])
            ->latest();

        if ($status) {
            $ordersQuery->where('status', $status);
        }

        if ($search) {
            $ordersQuery->where('order_number', 'LIKE', '%' . $search . '%');
        }

        $orders = $ordersQuery->paginate(10);
        $orders->appends($request->query());

        // Get order statistics
        $stats = [
            'total_orders' => Auth::user()->orders()->count(),
            'pending_orders' => Auth::user()->orders()->where('status', Order::STATUS_PENDING)->count(),
            'completed_orders' => Auth::user()->orders()->where('status', Order::STATUS_DELIVERED)->count(),
            'total_spent' => Auth::user()->orders()
                ->where('payment_status', Order::PAYMENT_PAID)
                ->sum('total_amount'),
        ];

        return view('orders.index', compact('orders', 'stats', 'status', 'search'));
    }

    /**
     * Display the specified order.
     */
    public function show(string $orderNumber): View
    {
        $order = Order::where('order_number', $orderNumber)
            ->with(['items.product', 'user'])
            ->firstOrFail();

        // Check if user can view this order
        if (Auth::guest() || (Auth::check() && Auth::id() !== $order->user_id)) {
            abort(403, 'No tienes permiso para ver este pedido.');
        }

        return view('orders.show', compact('order'));
    }

    /**
     * Track order by order number (public).
     */
    public function track(Request $request): View|JsonResponse
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'order_number' => 'required|string',
                'email' => 'required|email',
            ]);

            $order = Order::where('order_number', $request->order_number)
                ->whereJsonContains('billing_address->email', $request->email)
                ->with(['items.product'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado. Verifica el número de pedido y email.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'order' => [
                    'order_number' => $order->order_number,
                    'status' => $order->status,
                    'status_text' => $order->status_text,
                    'payment_status' => $order->payment_status,
                    'payment_status_text' => $order->payment_status_text,
                    'total_amount' => $order->formatted_total_amount,
                    'created_at' => $order->created_at->format('d/m/Y H:i'),
                    'items_count' => $order->items_count,
                    'can_cancel' => $order->canCancel(),
                ],
            ]);
        }

        return view('orders.track');
    }

    /**
     * Cancel an order.
     */
    public function cancel(string $orderNumber): JsonResponse
    {
        try {
            $order = Order::where('order_number', $orderNumber)
                ->where('user_id', Auth::id())
                ->firstOrFail();

            if (!$order->canCancel()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este pedido no puede ser cancelado.',
                ], 400);
            }

            $order->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Pedido cancelado exitosamente.',
                'order' => [
                    'status' => $order->status,
                    'status_text' => $order->status_text,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar el pedido.',
            ], 500);
        }
    }

    /**
     * Reorder items from a previous order.
     */
    public function reorder(string $orderNumber): JsonResponse
    {
        try {
            $order = Order::where('order_number', $orderNumber)
                ->where('user_id', Auth::id())
                ->with(['items.product'])
                ->firstOrFail();

            $addedItems = 0;
            $unavailableItems = [];

            foreach ($order->items as $item) {
                $product = $item->product;
                
                if (!$product || !$product->is_active || !$product->in_stock) {
                    $unavailableItems[] = $item->product_name;
                    continue;
                }

                if ($product->stock_quantity < $item->quantity) {
                    // Add available quantity
                    if ($product->stock_quantity > 0) {
                        Cart::addItem(
                            $product->id,
                            $product->stock_quantity,
                            json_decode($item->product_options, true) ?? []
                        );
                        $addedItems++;
                    }
                    $unavailableItems[] = $item->product_name . ' (cantidad limitada)';
                } else {
                    Cart::addItem(
                        $product->id,
                        $item->quantity,
                        json_decode($item->product_options, true) ?? []
                    );
                    $addedItems++;
                }
            }

            $message = "Se añadieron {$addedItems} productos al carrito.";
            if (!empty($unavailableItems)) {
                $message .= ' Algunos productos no están disponibles: ' . implode(', ', $unavailableItems);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'added_items' => $addedItems,
                'unavailable_items' => $unavailableItems,
                'cart_summary' => Cart::getSummary(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al reordenar los productos.',
            ], 500);
        }
    }

    /**
     * Download order invoice.
     */
    public function invoice(string $orderNumber)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->with(['items.product'])
            ->firstOrFail();

        // This would generate a PDF invoice
        // For now, return a simple view
        return view('orders.invoice', compact('order'));
    }

    /**
     * Get order status updates.
     */
    public function statusUpdates(string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // This would return order status history
        // For now, return current status
        $statusUpdates = [
            [
                'status' => Order::STATUS_PENDING,
                'status_text' => 'Pedido recibido',
                'date' => $order->created_at->format('d/m/Y H:i'),
                'description' => 'Tu pedido ha sido recibido y está siendo procesado.',
                'completed' => true,
            ],
        ];

        if (in_array($order->status, [Order::STATUS_PROCESSING, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])) {
            $statusUpdates[] = [
                'status' => Order::STATUS_PROCESSING,
                'status_text' => 'En preparación',
                'date' => $order->updated_at->format('d/m/Y H:i'),
                'description' => 'Tu pedido está siendo preparado para el envío.',
                'completed' => true,
            ];
        }

        if (in_array($order->status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED])) {
            $statusUpdates[] = [
                'status' => Order::STATUS_SHIPPED,
                'status_text' => 'Enviado',
                'date' => $order->updated_at->format('d/m/Y H:i'),
                'description' => 'Tu pedido ha sido enviado.',
                'completed' => true,
            ];
        }

        if ($order->status === Order::STATUS_DELIVERED) {
            $statusUpdates[] = [
                'status' => Order::STATUS_DELIVERED,
                'status_text' => 'Entregado',
                'date' => $order->updated_at->format('d/m/Y H:i'),
                'description' => 'Tu pedido ha sido entregado.',
                'completed' => true,
            ];
        }

        return response()->json($statusUpdates);
    }
}