<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'total_price',
        'product_options',
        'product_image',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'product_options' => 'array',
    ];

    /**
     * Get the order that owns the item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the product associated with the item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get formatted unit price.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return '$' . number_format($this->unit_price, 2);
    }

    /**
     * Get formatted total price.
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return '$' . number_format($this->total_price, 2);
    }

    /**
     * Get product image URL.
     */
    public function getProductImageUrlAttribute(): string
    {
        if ($this->product_image) {
            return asset('storage/' . $this->product_image);
        }
        
        return asset('images/placeholder-product.jpg');
    }

    /**
     * Get formatted product options.
     */
    public function getFormattedOptionsAttribute(): string
    {
        if (empty($this->product_options)) {
            return '';
        }
        
        $options = [];
        foreach ($this->product_options as $key => $value) {
            $options[] = ucfirst($key) . ': ' . $value;
        }
        
        return implode(', ', $options);
    }

    /**
     * Check if the product still exists.
     */
    public function hasProduct(): bool
    {
        return $this->product !== null;
    }

    /**
     * Get product URL if product exists.
     */
    public function getProductUrlAttribute(): ?string
    {
        if ($this->hasProduct()) {
            return route('products.show', $this->product->slug);
        }
        
        return null;
    }

    /**
     * Check if item can be reviewed.
     */
    public function canBeReviewed(): bool
    {
        return $this->hasProduct() && 
               $this->order->status === Order::STATUS_DELIVERED &&
               !$this->hasReview();
    }

    /**
     * Check if item has been reviewed.
     */
    public function hasReview(): bool
    {
        if (!$this->hasProduct()) {
            return false;
        }
        
        return Review::where('product_id', $this->product_id)
                    ->where('user_id', $this->order->user_id)
                    ->where('order_id', $this->order_id)
                    ->exists();
    }

    /**
     * Get the review for this item.
     */
    public function getReview()
    {
        if (!$this->hasProduct()) {
            return null;
        }
        
        return Review::where('product_id', $this->product_id)
                    ->where('user_id', $this->order->user_id)
                    ->where('order_id', $this->order_id)
                    ->first();
    }

    /**
     * Calculate savings if product was on sale.
     */
    public function getSavingsAttribute(): float
    {
        if (!$this->hasProduct()) {
            return 0;
        }
        
        $currentPrice = $this->product->current_price;
        $paidPrice = $this->unit_price;
        
        if ($currentPrice > $paidPrice) {
            return ($currentPrice - $paidPrice) * $this->quantity;
        }
        
        return 0;
    }

    /**
     * Get formatted savings.
     */
    public function getFormattedSavingsAttribute(): string
    {
        $savings = $this->savings;
        return $savings > 0 ? '$' . number_format($savings, 2) : '$0.00';
    }

    /**
     * Check if item was purchased at a discount.
     */
    public function wasPurchasedAtDiscountAttribute(): bool
    {
        return $this->savings > 0;
    }
}