<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    /**
     * Display the shopping cart.
     */
    public function index(): View
    {
        $cartItems = Cart::getItems();
        $cartSummary = Cart::getSummary();
        
        // Get recommended products based on cart items
        $recommendedProducts = collect();
        if ($cartItems->isNotEmpty()) {
            $categoryIds = $cartItems->pluck('product.categories')
                ->flatten()
                ->pluck('id')
                ->unique();
            
            $recommendedProducts = Product::active()
                ->inStock()
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('categories.id', $categoryIds);
                })
                ->whereNotIn('id', $cartItems->pluck('product_id'))
                ->featured()
                ->limit(4)
                ->get();
        }

        return view('cart.index', compact('cartItems', 'cartSummary', 'recommendedProducts'));
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1|max:99',
            'options' => 'sometimes|array',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            
            // Check if product is active and in stock
            if (!$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este producto no está disponible.'
                ], 400);
            }

            if (!$product->in_stock || $product->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay suficiente stock disponible.'
                ], 400);
            }

            $cartItem = Cart::addItem(
                $request->product_id,
                $request->quantity,
                $request->options ?? []
            );

            $cartSummary = Cart::getSummary();

            return response()->json([
                'success' => true,
                'message' => 'Producto añadido al carrito.',
                'cart_item' => [
                    'id' => $cartItem->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'product_image' => $cartItem->product->main_image_url,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->formatted_unit_price,
                    'total_price' => $cartItem->formatted_total_price,
                    'options' => $cartItem->formatted_options,
                ],
                'cart_summary' => $cartSummary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al añadir el producto al carrito.'
            ], 500);
        }
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, int $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        try {
            $cartItem = Cart::updateItem($itemId, $request->quantity);
            
            if (!$cartItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Artículo del carrito no encontrado.'
                ], 404);
            }

            // Check stock availability
            if (!$cartItem->product->in_stock || $cartItem->product->stock_quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay suficiente stock disponible.'
                ], 400);
            }

            $cartSummary = Cart::getSummary();

            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada.',
                'cart_item' => [
                    'id' => $cartItem->id,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->formatted_unit_price,
                    'total_price' => $cartItem->formatted_total_price,
                ],
                'cart_summary' => $cartSummary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la cantidad.'
            ], 500);
        }
    }

    /**
     * Remove item from cart.
     */
    public function remove(int $itemId): JsonResponse
    {
        try {
            $removed = Cart::removeItem($itemId);
            
            if (!$removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Artículo del carrito no encontrado.'
                ], 404);
            }

            $cartSummary = Cart::getSummary();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado del carrito.',
                'cart_summary' => $cartSummary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto.'
            ], 500);
        }
    }

    /**
     * Clear all items from cart.
     */
    public function clear(): JsonResponse
    {
        try {
            Cart::clear();

            return response()->json([
                'success' => true,
                'message' => 'Carrito vaciado.',
                'cart_summary' => [
                    'items_count' => 0,
                    'subtotal' => 0,
                    'tax' => 0,
                    'total' => 0,
                    'formatted_subtotal' => '$0.00',
                    'formatted_tax' => '$0.00',
                    'formatted_total' => '$0.00',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al vaciar el carrito.'
            ], 500);
        }
    }

    /**
     * Get cart summary.
     */
    public function summary(): JsonResponse
    {
        $cartSummary = Cart::getSummary();
        
        return response()->json($cartSummary);
    }

    /**
     * Get cart items count.
     */
    public function count(): JsonResponse
    {
        $count = Cart::getItemsCount();
        
        return response()->json(['count' => $count]);
    }

    /**
     * Get cart items for mini cart.
     */
    public function miniCart(): JsonResponse
    {
        $cartItems = Cart::getItems()
            ->take(5)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'product_slug' => $item->product->slug,
                    'product_image' => $item->product->main_image_url,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->formatted_unit_price,
                    'total_price' => $item->formatted_total_price,
                    'options' => $item->formatted_options,
                    'product_url' => route('products.show', $item->product->slug),
                ];
            });

        $cartSummary = Cart::getSummary();

        return response()->json([
            'items' => $cartItems,
            'summary' => $cartSummary,
            'has_more' => Cart::getItemsCount() > 5,
        ]);
    }

    /**
     * Validate cart before checkout.
     */
    public function validateCart(): JsonResponse
    {
        try {
            $validation = Cart::validateItems();
            
            return response()->json([
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
                'warnings' => $validation['warnings'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => ['Error al validar el carrito.'],
                'warnings' => [],
            ], 500);
        }
    }

    /**
     * Apply coupon code.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_code' => 'required|string|max:50',
        ]);

        // This would be implemented when you add coupon functionality
        // For now, return a placeholder response
        return response()->json([
            'success' => false,
            'message' => 'Funcionalidad de cupones no implementada aún.'
        ], 501);
    }

    /**
     * Remove coupon.
     */
    public function removeCoupon(): JsonResponse
    {
        // This would be implemented when you add coupon functionality
        return response()->json([
            'success' => false,
            'message' => 'Funcionalidad de cupones no implementada aún.'
        ], 501);
    }

    /**
     * Estimate shipping.
     */
    public function estimateShipping(Request $request): JsonResponse
    {
        $request->validate([
            'country' => 'required|string|max:2',
            'state' => 'sometimes|string|max:100',
            'postal_code' => 'sometimes|string|max:20',
        ]);

        // This would be implemented when you add shipping calculation
        // For now, return a placeholder response
        return response()->json([
            'success' => false,
            'message' => 'Cálculo de envío no implementado aún.'
        ], 501);
    }

    /**
     * Save cart for later (for guest users).
     */
    public function saveForLater(): JsonResponse
    {
        if (Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Los usuarios registrados no necesitan guardar el carrito.'
            ], 400);
        }

        try {
            $cartData = Cart::getItems()->toArray();
            $token = bin2hex(random_bytes(32));
            
            // Store cart data in session with token
            Session::put('saved_cart_' . $token, $cartData);
            
            return response()->json([
                'success' => true,
                'message' => 'Carrito guardado.',
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar el carrito.'
            ], 500);
        }
    }

    /**
     * Restore saved cart.
     */
    public function restoreCart(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string|size:64',
        ]);

        try {
            $cartData = Session::get('saved_cart_' . $request->token);
            
            if (!$cartData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Carrito guardado no encontrado o expirado.'
                ], 404);
            }

            // Clear current cart
            Cart::clear();
            
            // Restore items
            foreach ($cartData as $item) {
                Cart::addItem(
                    $item['product_id'],
                    $item['quantity'],
                    $item['product_options'] ?? []
                );
            }

            // Remove saved cart data
            Session::forget('saved_cart_' . $request->token);
            
            $cartSummary = Cart::getSummary();

            return response()->json([
                'success' => true,
                'message' => 'Carrito restaurado.',
                'cart_summary' => $cartSummary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar el carrito.'
            ], 500);
        }
    }

    /**
     * Transfer guest cart to user account after login.
     */
    public function transferGuestCart(): JsonResponse
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no autenticado.'
            ], 401);
        }

        try {
            Cart::transferGuestCart();
            
            $cartSummary = Cart::getSummary();

            return response()->json([
                'success' => true,
                'message' => 'Carrito transferido.',
                'cart_summary' => $cartSummary,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al transferir el carrito.'
            ], 500);
        }
    }
}