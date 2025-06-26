<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Cart extends Model
{
    use HasFactory;

    protected $table = 'cart';

    protected $fillable = [
        'user_id',
        'session_id',
        'product_id',
        'quantity',
        'price',
        'product_options',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'product_options' => 'array',
    ];

    /**
     * Get the user that owns the cart item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product for this cart item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to only include items for current user or session.
     */
    public function scopeForCurrentUser($query)
    {
        if (Auth::check()) {
            return $query->where('user_id', Auth::id());
        }
        
        return $query->where('session_id', session()->getId());
    }

    /**
     * Get the total price for this cart item.
     */
    public function getTotalPriceAttribute(): float
    {
        return $this->quantity * $this->price;
    }

    /**
     * Get formatted total price.
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return '$' . number_format($this->total_price, 2);
    }

    /**
     * Get formatted unit price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get product options as formatted string.
     */
    public function getFormattedOptionsAttribute(): string
    {
        if (!$this->product_options) {
            return '';
        }
        
        $options = [];
        foreach ($this->product_options as $key => $value) {
            $options[] = ucfirst($key) . ': ' . $value;
        }
        
        return implode(', ', $options);
    }

    /**
     * Add item to cart.
     */
    public static function addItem($productId, $quantity = 1, $options = [])
    {
        $product = Product::findOrFail($productId);
        
        if (!$product->is_active || !$product->in_stock) {
            throw new \Exception('Product is not available');
        }
        
        $price = $product->current_price;
        $userId = Auth::id();
        $sessionId = session()->getId();
        
        // Check if item already exists in cart
        $existingItem = static::where('product_id', $productId)
            ->when($userId, function ($query) use ($userId) {
                return $query->where('user_id', $userId);
            }, function ($query) use ($sessionId) {
                return $query->where('session_id', $sessionId);
            })
            ->where('product_options', json_encode($options))
            ->first();
        
        if ($existingItem) {
            $existingItem->increment('quantity', $quantity);
            return $existingItem;
        }
        
        return static::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price,
            'product_options' => $options,
        ]);
    }

    /**
     * Update item quantity.
     */
    public function updateQuantity($quantity)
    {
        if ($quantity <= 0) {
            $this->delete();
            return null;
        }
        
        $this->update(['quantity' => $quantity]);
        return $this;
    }

    /**
     * Remove item from cart.
     */
    public function removeItem()
    {
        $this->delete();
    }

    /**
     * Get cart items for current user.
     */
    public static function getItems()
    {
        return static::forCurrentUser()
            ->with('product')
            ->get();
    }

    /**
     * Get cart total.
     */
    public static function getTotal()
    {
        return static::forCurrentUser()
            ->get()
            ->sum('total_price');
    }

    /**
     * Get cart items count.
     */
    public static function getItemsCount()
    {
        return static::forCurrentUser()
            ->sum('quantity');
    }

    /**
     * Get cart subtotal.
     */
    public static function getSubtotal()
    {
        return static::getTotal();
    }

    /**
     * Clear cart for current user.
     */
    public static function clearCart()
    {
        static::forCurrentUser()->delete();
    }

    /**
     * Transfer guest cart to user cart.
     */
    public static function transferGuestCartToUser($userId, $sessionId)
    {
        $guestItems = static::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->get();
        
        foreach ($guestItems as $item) {
            // Check if user already has this item
            $existingItem = static::where('user_id', $userId)
                ->where('product_id', $item->product_id)
                ->where('product_options', json_encode($item->product_options))
                ->first();
            
            if ($existingItem) {
                $existingItem->increment('quantity', $item->quantity);
                $item->delete();
            } else {
                $item->update([
                    'user_id' => $userId,
                    'session_id' => null
                ]);
            }
        }
    }

    /**
     * Get formatted cart summary.
     */
    public static function getSummary()
    {
        $items = static::getItems();
        $subtotal = static::getSubtotal();
        $tax = $subtotal * 0.1; // 10% tax
        $shipping = $subtotal > 50 ? 0 : 10; // Free shipping over $50
        $total = $subtotal + $tax + $shipping;
        
        return [
            'items' => $items,
            'items_count' => $items->sum('quantity'),
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'formatted' => [
                'subtotal' => '$' . number_format($subtotal, 2),
                'tax' => '$' . number_format($tax, 2),
                'shipping' => $shipping > 0 ? '$' . number_format($shipping, 2) : 'Free',
                'total' => '$' . number_format($total, 2),
            ]
        ];
    }

    /**
     * Validate cart items before checkout.
     */
    public static function validateItems()
    {
        $items = static::getItems();
        $errors = [];
        
        foreach ($items as $item) {
            $product = $item->product;
            
            if (!$product->is_active) {
                $errors[] = "Product '{$product->name}' is no longer available.";
                continue;
            }
            
            if (!$product->in_stock) {
                $errors[] = "Product '{$product->name}' is out of stock.";
                continue;
            }
            
            if ($product->manage_stock && $product->stock_quantity < $item->quantity) {
                $errors[] = "Only {$product->stock_quantity} units of '{$product->name}' are available.";
            }
            
            // Update price if it has changed
            if ($item->price != $product->current_price) {
                $item->update(['price' => $product->current_price]);
            }
        }
        
        return $errors;
    }
}