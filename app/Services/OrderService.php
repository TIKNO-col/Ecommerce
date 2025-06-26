<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderService
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Create a new order from cart.
     *
     * @param User $user
     * @param array $orderData
     * @return Order|null
     */
    public function createOrder(User $user, array $orderData): ?Order
    {
        // Validate cart first
        $cartValidation = $this->cartService->validateCart();
        if (!$cartValidation['valid']) {
            throw new \Exception('El carrito contiene errores: ' . implode(', ', $cartValidation['errors']));
        }

        $cartSummary = $this->cartService->getCartSummary();
        if ($cartSummary['item_count'] === 0) {
            throw new \Exception('El carrito está vacío.');
        }

        return DB::transaction(function () use ($user, $orderData, $cartSummary) {
            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'status' => 'pending',
                'subtotal' => $cartSummary['subtotal'],
                'tax_amount' => $cartSummary['tax'],
                'shipping_amount' => $cartSummary['shipping'],
                'total_amount' => $cartSummary['total'],
                'currency' => 'USD',
                'payment_method' => $orderData['payment_method'] ?? 'pending',
                'payment_status' => 'pending',
                
                // Shipping address
                'shipping_name' => $orderData['shipping_name'],
                'shipping_email' => $orderData['shipping_email'] ?? $user->email,
                'shipping_phone' => $orderData['shipping_phone'] ?? null,
                'shipping_address_line_1' => $orderData['shipping_address_line_1'],
                'shipping_address_line_2' => $orderData['shipping_address_line_2'] ?? null,
                'shipping_city' => $orderData['shipping_city'],
                'shipping_state' => $orderData['shipping_state'],
                'shipping_postal_code' => $orderData['shipping_postal_code'],
                'shipping_country' => $orderData['shipping_country'] ?? 'US',
                
                // Billing address (same as shipping if not provided)
                'billing_name' => $orderData['billing_name'] ?? $orderData['shipping_name'],
                'billing_email' => $orderData['billing_email'] ?? $orderData['shipping_email'] ?? $user->email,
                'billing_phone' => $orderData['billing_phone'] ?? $orderData['shipping_phone'],
                'billing_address_line_1' => $orderData['billing_address_line_1'] ?? $orderData['shipping_address_line_1'],
                'billing_address_line_2' => $orderData['billing_address_line_2'] ?? $orderData['shipping_address_line_2'],
                'billing_city' => $orderData['billing_city'] ?? $orderData['shipping_city'],
                'billing_state' => $orderData['billing_state'] ?? $orderData['shipping_state'],
                'billing_postal_code' => $orderData['billing_postal_code'] ?? $orderData['shipping_postal_code'],
                'billing_country' => $orderData['billing_country'] ?? $orderData['shipping_country'] ?? 'US',
                
                'notes' => $orderData['notes'] ?? null,
            ]);

            // Create order items
            foreach ($cartSummary['items'] as $cartItem) {
                $product = $cartItem->product;
                
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                    'total' => $cartItem->quantity * $cartItem->price,
                ]);

                // Update product stock
                $product->decrement('stock', $cartItem->quantity);
                
                // Update sales count
                $product->increment('sales_count', $cartItem->quantity);
            }

            // Clear cart after successful order
            $this->cartService->clearCart();

            // Send order confirmation email
            $this->sendOrderConfirmationEmail($order);

            return $order;
        });
    }

    /**
     * Update order status.
     *
     * @param Order $order
     * @param string $status
     * @param string|null $notes
     * @return bool
     */
    public function updateOrderStatus(Order $order, string $status, ?string $notes = null): bool
    {
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Estado de pedido inválido: {$status}");
        }

        $oldStatus = $order->status;
        
        $order->update([
            'status' => $status,
            'status_updated_at' => now(),
        ]);

        // Add status history
        $order->statusHistory()->create([
            'status' => $status,
            'notes' => $notes,
            'changed_by' => auth()->id(),
            'changed_at' => now(),
        ]);

        // Handle status-specific actions
        $this->handleStatusChange($order, $oldStatus, $status);

        return true;
    }

    /**
     * Cancel an order.
     *
     * @param Order $order
     * @param string|null $reason
     * @return bool
     */
    public function cancelOrder(Order $order, ?string $reason = null): bool
    {
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            throw new \Exception('Solo se pueden cancelar pedidos pendientes o confirmados.');
        }

        return DB::transaction(function () use ($order, $reason) {
            // Restore product stock
            foreach ($order->items as $item) {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock', $item->quantity);
                    $product->decrement('sales_count', $item->quantity);
                }
            }

            // Update order status
            $this->updateOrderStatus($order, 'cancelled', $reason);

            // Send cancellation email
            $this->sendOrderCancellationEmail($order, $reason);

            return true;
        });
    }

    /**
     * Get order statistics for a user.
     *
     * @param User $user
     * @return array
     */
    public function getUserOrderStats(User $user): array
    {
        $orders = $user->orders();
        
        return [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('total_amount'),
            'pending_orders' => $orders->where('status', 'pending')->count(),
            'completed_orders' => $orders->where('status', 'delivered')->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'average_order_value' => $orders->avg('total_amount') ?? 0,
            'last_order_date' => $orders->latest()->first()?->created_at,
            'favorite_products' => $this->getUserFavoriteProducts($user),
        ];
    }

    /**
     * Track order by order number and email.
     *
     * @param string $orderNumber
     * @param string $email
     * @return Order|null
     */
    public function trackOrder(string $orderNumber, string $email): ?Order
    {
        return Order::where('order_number', $orderNumber)
            ->where(function ($query) use ($email) {
                $query->where('shipping_email', $email)
                      ->orWhere('billing_email', $email);
            })
            ->with(['items.product', 'statusHistory'])
            ->first();
    }

    /**
     * Get orders with filters.
     *
     * @param array $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getOrdersWithFilters(array $filters = [])
    {
        $query = Order::with(['user', 'items.product']);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('shipping_email', 'like', "%{$search}%")
                  ->orWhere('shipping_name', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Generate unique order number.
     *
     * @return string
     */
    protected function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(Str::random(8));
        } while (Order::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Handle status change actions.
     *
     * @param Order $order
     * @param string $oldStatus
     * @param string $newStatus
     * @return void
     */
    protected function handleStatusChange(Order $order, string $oldStatus, string $newStatus): void
    {
        switch ($newStatus) {
            case 'confirmed':
                $this->sendOrderConfirmedEmail($order);
                break;
                
            case 'shipped':
                $this->sendOrderShippedEmail($order);
                break;
                
            case 'delivered':
                $this->sendOrderDeliveredEmail($order);
                $order->update(['delivered_at' => now()]);
                break;
                
            case 'cancelled':
                if ($oldStatus !== 'cancelled') {
                    // Stock restoration is handled in cancelOrder method
                }
                break;
        }
    }

    /**
     * Get user's favorite products based on order history.
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    protected function getUserFavoriteProducts(User $user, int $limit = 5)
    {
        return OrderItem::whereHas('order', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('status', 'delivered');
            })
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->with('product')
            ->get()
            ->pluck('product');
    }

    /**
     * Send order confirmation email.
     *
     * @param Order $order
     * @return void
     */
    protected function sendOrderConfirmationEmail(Order $order): void
    {
        // TODO: Implement email sending
        // Mail::to($order->shipping_email)->send(new OrderConfirmation($order));
    }

    /**
     * Send order confirmed email.
     *
     * @param Order $order
     * @return void
     */
    protected function sendOrderConfirmedEmail(Order $order): void
    {
        // TODO: Implement email sending
        // Mail::to($order->shipping_email)->send(new OrderConfirmed($order));
    }

    /**
     * Send order shipped email.
     *
     * @param Order $order
     * @return void
     */
    protected function sendOrderShippedEmail(Order $order): void
    {
        // TODO: Implement email sending
        // Mail::to($order->shipping_email)->send(new OrderShipped($order));
    }

    /**
     * Send order delivered email.
     *
     * @param Order $order
     * @return void
     */
    protected function sendOrderDeliveredEmail(Order $order): void
    {
        // TODO: Implement email sending
        // Mail::to($order->shipping_email)->send(new OrderDelivered($order));
    }

    /**
     * Send order cancellation email.
     *
     * @param Order $order
     * @param string|null $reason
     * @return void
     */
    protected function sendOrderCancellationEmail(Order $order, ?string $reason): void
    {
        // TODO: Implement email sending
        // Mail::to($order->shipping_email)->send(new OrderCancelled($order, $reason));
    }
}