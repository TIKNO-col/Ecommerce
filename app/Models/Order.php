<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'status',
        'payment_status',
        'payment_method',
        'payment_id',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'currency',
        'billing_address',
        'shipping_address',
        'notes',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'billing_address' => 'array',
        'shipping_address' => 'array',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber();
            }
        });
    }

    /**
     * Get the user that owns the order.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Scope a query to filter by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to filter by payment status.
     */
    public function scopePaymentStatus($query, $status)
    {
        return $query->where('payment_status', $status);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to get recent orders.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'order_number';
    }

    /**
     * Generate unique order number.
     */
    public static function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . date('Y') . '-' . strtoupper(Str::random(8));
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Create order from cart.
     */
    public static function createFromCart($userId, $billingAddress, $shippingAddress, $paymentMethod = null)
    {
        $cartSummary = Cart::getSummary();
        
        if ($cartSummary['items']->isEmpty()) {
            throw new \Exception('Cart is empty');
        }
        
        // Validate cart items
        $errors = Cart::validateItems();
        if (!empty($errors)) {
            throw new \Exception('Cart validation failed: ' . implode(', ', $errors));
        }
        
        $order = static::create([
            'user_id' => $userId,
            'status' => static::STATUS_PENDING,
            'payment_status' => static::PAYMENT_PENDING,
            'payment_method' => $paymentMethod,
            'subtotal' => $cartSummary['subtotal'],
            'tax_amount' => $cartSummary['tax'],
            'shipping_amount' => $cartSummary['shipping'],
            'total_amount' => $cartSummary['total'],
            'billing_address' => $billingAddress,
            'shipping_address' => $shippingAddress,
        ]);
        
        // Create order items
        foreach ($cartSummary['items'] as $cartItem) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product->name,
                'product_sku' => $cartItem->product->sku,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->price,
                'total_price' => $cartItem->total_price,
                'product_options' => $cartItem->product_options,
                'product_image' => $cartItem->product->main_image,
            ]);
            
            // Update product sales count
            $cartItem->product->increment('sales_count', $cartItem->quantity);
            
            // Update stock if managed
            if ($cartItem->product->manage_stock) {
                $cartItem->product->decrement('stock_quantity', $cartItem->quantity);
                
                // Update stock status
                if ($cartItem->product->stock_quantity <= 0) {
                    $cartItem->product->update(['in_stock' => false]);
                }
            }
        }
        
        // Clear cart
        Cart::clearCart();
        
        return $order;
    }

    /**
     * Mark order as paid.
     */
    public function markAsPaid($paymentId = null)
    {
        $this->update([
            'payment_status' => static::PAYMENT_PAID,
            'payment_id' => $paymentId,
            'status' => static::STATUS_PROCESSING,
        ]);
    }

    /**
     * Mark order as shipped.
     */
    public function markAsShipped()
    {
        $this->update([
            'status' => static::STATUS_SHIPPED,
            'shipped_at' => now(),
        ]);
    }

    /**
     * Mark order as delivered.
     */
    public function markAsDelivered()
    {
        $this->update([
            'status' => static::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Cancel order.
     */
    public function cancel()
    {
        if (in_array($this->status, [static::STATUS_SHIPPED, static::STATUS_DELIVERED])) {
            throw new \Exception('Cannot cancel shipped or delivered order');
        }
        
        // Restore stock
        foreach ($this->items as $item) {
            if ($item->product && $item->product->manage_stock) {
                $item->product->increment('stock_quantity', $item->quantity);
                $item->product->update(['in_stock' => true]);
            }
        }
        
        $this->update(['status' => static::STATUS_CANCELLED]);
    }

    /**
     * Get status badge class.
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            static::STATUS_PENDING => 'badge-warning',
            static::STATUS_PROCESSING => 'badge-info',
            static::STATUS_SHIPPED => 'badge-primary',
            static::STATUS_DELIVERED => 'badge-success',
            static::STATUS_CANCELLED => 'badge-danger',
            default => 'badge-secondary',
        };
    }

    /**
     * Get payment status badge class.
     */
    public function getPaymentStatusBadgeClassAttribute(): string
    {
        return match($this->payment_status) {
            static::PAYMENT_PENDING => 'badge-warning',
            static::PAYMENT_PAID => 'badge-success',
            static::PAYMENT_FAILED => 'badge-danger',
            static::PAYMENT_REFUNDED => 'badge-info',
            default => 'badge-secondary',
        };
    }

    /**
     * Get readable status.
     */
    public function getReadableStatusAttribute(): string
    {
        return match($this->status) {
            static::STATUS_PENDING => 'Pending',
            static::STATUS_PROCESSING => 'Processing',
            static::STATUS_SHIPPED => 'Shipped',
            static::STATUS_DELIVERED => 'Delivered',
            static::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown',
        };
    }

    /**
     * Get readable payment status.
     */
    public function getReadablePaymentStatusAttribute(): string
    {
        return match($this->payment_status) {
            static::PAYMENT_PENDING => 'Pending',
            static::PAYMENT_PAID => 'Paid',
            static::PAYMENT_FAILED => 'Failed',
            static::PAYMENT_REFUNDED => 'Refunded',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted total amount.
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total_amount, 2);
    }

    /**
     * Get formatted subtotal.
     */
    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    /**
     * Get formatted tax amount.
     */
    public function getFormattedTaxAttribute(): string
    {
        return '$' . number_format($this->tax_amount, 2);
    }

    /**
     * Get formatted shipping amount.
     */
    public function getFormattedShippingAttribute(): string
    {
        return $this->shipping_amount > 0 ? '$' . number_format($this->shipping_amount, 2) : 'Free';
    }

    /**
     * Get items count.
     */
    public function getItemsCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Check if order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [static::STATUS_SHIPPED, static::STATUS_DELIVERED, static::STATUS_CANCELLED]);
    }

    /**
     * Check if order can be returned.
     */
    public function canBeReturned(): bool
    {
        return $this->status === static::STATUS_DELIVERED && 
               $this->delivered_at && 
               $this->delivered_at->diffInDays(now()) <= 30;
    }

    /**
     * Get billing address as formatted string.
     */
    public function getFormattedBillingAddressAttribute(): string
    {
        $address = $this->billing_address;
        return implode(', ', array_filter([
            $address['name'] ?? '',
            $address['address_line_1'] ?? '',
            $address['address_line_2'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? '',
        ]));
    }

    /**
     * Get shipping address as formatted string.
     */
    public function getFormattedShippingAddressAttribute(): string
    {
        $address = $this->shipping_address;
        return implode(', ', array_filter([
            $address['name'] ?? '',
            $address['address_line_1'] ?? '',
            $address['address_line_2'] ?? '',
            $address['city'] ?? '',
            $address['state'] ?? '',
            $address['postal_code'] ?? '',
            $address['country'] ?? '',
        ]));
    }
}