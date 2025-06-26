<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class CartService
{
    /**
     * Get cart items for current user or guest.
     *
     * @return Collection
     */
    public function getCartItems(): Collection
    {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::id())
                ->with(['product.categories'])
                ->get();
        }

        // Guest cart from session
        $guestCart = Session::get('cart', []);
        $cartItems = collect();

        foreach ($guestCart as $productId => $item) {
            $product = Product::find($productId);
            if ($product) {
                $cartItems->push((object) [
                    'id' => null,
                    'product_id' => $productId,
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'] ?? $product->price,
                    'created_at' => now(),
                ]);
            }
        }

        return $cartItems;
    }

    /**
     * Add product to cart.
     *
     * @param int $productId
     * @param int $quantity
     * @param array $options
     * @return bool
     */
    public function addToCart(int $productId, int $quantity = 1, array $options = []): bool
    {
        $product = Product::find($productId);
        
        if (!$product || !$product->is_active) {
            return false;
        }

        // Check stock availability
        if ($product->stock < $quantity) {
            return false;
        }

        if (Auth::check()) {
            return $this->addToUserCart($product, $quantity, $options);
        }

        return $this->addToGuestCart($product, $quantity, $options);
    }

    /**
     * Update cart item quantity.
     *
     * @param int $productId
     * @param int $quantity
     * @return bool
     */
    public function updateQuantity(int $productId, int $quantity): bool
    {
        if ($quantity <= 0) {
            return $this->removeFromCart($productId);
        }

        $product = Product::find($productId);
        if (!$product || $product->stock < $quantity) {
            return false;
        }

        if (Auth::check()) {
            $cartItem = Cart::where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->first();

            if ($cartItem) {
                $cartItem->update(['quantity' => $quantity]);
                return true;
            }
        } else {
            $cart = Session::get('cart', []);
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] = $quantity;
                Session::put('cart', $cart);
                return true;
            }
        }

        return false;
    }

    /**
     * Remove product from cart.
     *
     * @param int $productId
     * @return bool
     */
    public function removeFromCart(int $productId): bool
    {
        if (Auth::check()) {
            return Cart::where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->delete() > 0;
        }

        $cart = Session::get('cart', []);
        if (isset($cart[$productId])) {
            unset($cart[$productId]);
            Session::put('cart', $cart);
            return true;
        }

        return false;
    }

    /**
     * Clear entire cart.
     *
     * @return bool
     */
    public function clearCart(): bool
    {
        if (Auth::check()) {
            Cart::where('user_id', Auth::id())->delete();
        } else {
            Session::forget('cart');
        }

        return true;
    }

    /**
     * Get cart summary.
     *
     * @return array
     */
    public function getCartSummary(): array
    {
        $items = $this->getCartItems();
        
        $subtotal = $items->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        $itemCount = $items->sum('quantity');
        $uniqueItems = $items->count();

        // Calculate tax (example: 10%)
        $taxRate = config('ecommerce.tax_rate', 0.10);
        $tax = $subtotal * $taxRate;

        // Calculate shipping (free over certain amount)
        $freeShippingThreshold = config('ecommerce.free_shipping_threshold', 100);
        $shippingCost = $subtotal >= $freeShippingThreshold ? 0 : config('ecommerce.shipping_cost', 10);

        $total = $subtotal + $tax + $shippingCost;

        return [
            'items' => $items,
            'item_count' => $itemCount,
            'unique_items' => $uniqueItems,
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'tax_rate' => $taxRate,
            'shipping' => round($shippingCost, 2),
            'total' => round($total, 2),
            'free_shipping_threshold' => $freeShippingThreshold,
            'free_shipping_remaining' => max(0, $freeShippingThreshold - $subtotal),
        ];
    }

    /**
     * Transfer guest cart to user cart after login.
     *
     * @param User $user
     * @return bool
     */
    public function transferGuestCartToUser(User $user): bool
    {
        $guestCart = Session::get('cart', []);
        
        if (empty($guestCart)) {
            return true;
        }

        foreach ($guestCart as $productId => $item) {
            $existingCartItem = Cart::where('user_id', $user->id)
                ->where('product_id', $productId)
                ->first();

            if ($existingCartItem) {
                // Update quantity if item already exists
                $existingCartItem->update([
                    'quantity' => $existingCartItem->quantity + $item['quantity']
                ]);
            } else {
                // Create new cart item
                Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $productId,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                ]);
            }
        }

        // Clear guest cart
        Session::forget('cart');
        
        return true;
    }

    /**
     * Validate cart before checkout.
     *
     * @return array
     */
    public function validateCart(): array
    {
        $items = $this->getCartItems();
        $errors = [];
        $warnings = [];

        if ($items->isEmpty()) {
            $errors[] = 'El carrito está vacío.';
            return ['valid' => false, 'errors' => $errors, 'warnings' => $warnings];
        }

        foreach ($items as $item) {
            $product = $item->product;
            
            // Check if product is still active
            if (!$product->is_active) {
                $errors[] = "El producto '{$product->name}' ya no está disponible.";
                continue;
            }

            // Check stock availability
            if ($product->stock < $item->quantity) {
                if ($product->stock > 0) {
                    $warnings[] = "Solo quedan {$product->stock} unidades de '{$product->name}'. Se ajustará la cantidad.";
                } else {
                    $errors[] = "El producto '{$product->name}' está agotado.";
                }
            }

            // Check price changes
            if ($item->price != $product->price) {
                $warnings[] = "El precio de '{$product->name}' ha cambiado de \${$item->price} a \${$product->price}.";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Get recommended products based on cart items.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecommendedProducts(int $limit = 4): Collection
    {
        $cartItems = $this->getCartItems();
        
        if ($cartItems->isEmpty()) {
            return Product::where('is_active', true)
                ->where('is_featured', true)
                ->limit($limit)
                ->get();
        }

        // Get categories from cart items
        $categoryIds = $cartItems->pluck('product.categories')
            ->flatten()
            ->pluck('id')
            ->unique();

        // Get products from same categories, excluding cart items
        $cartProductIds = $cartItems->pluck('product_id');
        
        return Product::whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->whereNotIn('id', $cartProductIds)
            ->where('is_active', true)
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Add product to authenticated user's cart.
     *
     * @param Product $product
     * @param int $quantity
     * @param array $options
     * @return bool
     */
    protected function addToUserCart(Product $product, int $quantity, array $options): bool
    {
        $existingCartItem = Cart::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($existingCartItem) {
            $newQuantity = $existingCartItem->quantity + $quantity;
            
            // Check stock for new quantity
            if ($product->stock < $newQuantity) {
                return false;
            }
            
            $existingCartItem->update(['quantity' => $newQuantity]);
        } else {
            Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->price,
                'options' => json_encode($options),
            ]);
        }

        return true;
    }

    /**
     * Add product to guest cart (session).
     *
     * @param Product $product
     * @param int $quantity
     * @param array $options
     * @return bool
     */
    protected function addToGuestCart(Product $product, int $quantity, array $options): bool
    {
        $cart = Session::get('cart', []);
        
        if (isset($cart[$product->id])) {
            $newQuantity = $cart[$product->id]['quantity'] + $quantity;
            
            // Check stock for new quantity
            if ($product->stock < $newQuantity) {
                return false;
            }
            
            $cart[$product->id]['quantity'] = $newQuantity;
        } else {
            $cart[$product->id] = [
                'quantity' => $quantity,
                'price' => $product->price,
                'options' => $options,
                'added_at' => now()->toISOString(),
            ];
        }

        Session::put('cart', $cart);
        return true;
    }
}