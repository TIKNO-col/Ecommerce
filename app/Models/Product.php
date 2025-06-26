<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'brand_id',
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'price',
        'sale_price',
        'stock_quantity',
        'manage_stock',
        'in_stock',
        'is_featured',
        'is_active',
        'weight',
        'dimensions',
        'images',
        'gallery',
        'attributes',
        'rating',
        'reviews_count',
        'views_count',
        'sales_count',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'rating' => 'decimal:2',
        'manage_stock' => 'boolean',
        'in_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'images' => 'array',
        'gallery' => 'array',
        'attributes' => 'array',
        'stock_quantity' => 'integer',
        'reviews_count' => 'integer',
        'views_count' => 'integer',
        'sales_count' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'PRD-' . strtoupper(Str::random(8));
            }
        });

        static::updating(function ($product) {
            if ($product->isDirty('name') && empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    /**
     * Get the brand for this product.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Get the categories for this product.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    /**
     * Get the images for this product.
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->ordered();
    }

    /**
     * Get the primary image for this product.
     */
    public function primaryImage()
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get the reviews for this product.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get approved reviews for this product.
     */
    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('is_approved', true);
    }

    /**
     * Get the cart items for this product.
     */
    public function cartItems(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    /**
     * Get the order items for this product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the wishlist items for this product.
     */
    public function wishlistItems(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include featured products.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope a query to only include products in stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    /**
     * Scope a query to only include products on sale.
     */
    public function scopeOnSale($query)
    {
        return $query->whereNotNull('sale_price')->where('sale_price', '>', 0);
    }

    /**
     * Scope a query to search products.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'ILIKE', "%{$term}%")
              ->orWhere('description', 'ILIKE', "%{$term}%")
              ->orWhere('sku', 'ILIKE', "%{$term}%");
        });
    }

    /**
     * Scope a query to filter by price range.
     */
    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    /**
     * Scope a query to get new arrivals (products from last 30 days).
     */
    public function scopeNewArrivals($query)
    {
        return $query->where('created_at', '>=', now()->subDays(30))
                    ->orderByDesc('created_at');
    }

    /**
     * Scope a query to order by popularity.
     */
    public function scopePopular($query)
    {
        return $query->orderByDesc('sales_count')->orderByDesc('views_count');
    }

    /**
     * Scope a query to order by rating.
     */
    public function scopeTopRated($query)
    {
        return $query->orderByDesc('rating')->orderByDesc('reviews_count');
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get the current price (sale price if available, otherwise regular price).
     */
    public function getCurrentPriceAttribute(): float
    {
        return $this->sale_price && $this->sale_price > 0 ? $this->sale_price : $this->price;
    }

    /**
     * Check if product is on sale.
     */
    public function getIsOnSaleAttribute(): bool
    {
        return $this->sale_price && $this->sale_price > 0 && $this->sale_price < $this->price;
    }

    /**
     * Get discount percentage.
     */
    public function getDiscountPercentageAttribute(): int
    {
        if (!$this->is_on_sale) {
            return 0;
        }
        
        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    /**
     * Get the main image URL.
     */
    public function getMainImageAttribute(): ?string
    {
        // First try to get from ProductImage relationship
        $primaryImage = $this->primaryImage;
        if ($primaryImage) {
            return $primaryImage->getImageUrl();
        }
        
        // Fallback to images array if it exists and is valid
        $imagesArray = $this->getImagesArray();
        if (!empty($imagesArray)) {
            return asset('storage/' . $imagesArray[0]);
        }
        
        return asset('images/placeholder-product.jpg');
    }

    /**
     * Get the main image URL (method version).
     */
    public function getMainImageUrl(): ?string
    {
        // First try to get from ProductImage relationship
        $primaryImage = $this->primaryImage;
        if ($primaryImage) {
            return $primaryImage->getImageUrl();
        }
        
        // Fallback to first image from images array
        $imagesArray = $this->getImagesArray();
        if (!empty($imagesArray)) {
            return asset('storage/' . $imagesArray[0]);
        }
        
        // Default placeholder
        return asset('images/placeholder-product.jpg');
    }
    
    /**
     * Get images as array, handling both JSON string and array formats.
     */
    private function getImagesArray(): array
    {
        if (empty($this->images)) {
            return [];
        }
        
        if (is_array($this->images)) {
            return $this->images;
        }
        
        if (is_string($this->images)) {
            $decoded = json_decode($this->images, true);
            return is_array($decoded) ? $decoded : [];
        }
        
        return [];
    }

    /**
     * Get all image URLs.
     */
    public function getImageUrlsAttribute(): array
    {
        if (!$this->images) {
            return [asset('images/placeholder-product.jpg')];
        }
        
        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->images);
    }

    /**
     * Get gallery image URLs.
     */
    public function getGalleryUrlsAttribute(): array
    {
        if (!$this->gallery) {
            return [];
        }
        
        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $this->gallery);
    }

    /**
     * Get formatted price.
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    /**
     * Get formatted sale price.
     */
    public function getFormattedSalePriceAttribute(): ?string
    {
        return $this->sale_price ? '$' . number_format($this->sale_price, 2) : null;
    }

    /**
     * Get formatted current price.
     */
    public function getFormattedCurrentPriceAttribute(): string
    {
        return '$' . number_format($this->current_price, 2);
    }

    /**
     * Check if product is on sale.
     */
    public function isOnSale(): bool
    {
        return $this->sale_price && $this->sale_price > 0 && $this->sale_price < $this->price;
    }

    /**
     * Get stock status.
     */
    public function getStockStatusAttribute(): string
    {
        if (!$this->manage_stock) {
            return 'in_stock';
        }
        
        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        }
        
        if ($this->stock_quantity <= 5) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    /**
     * Get rating stars as array.
     */
    public function getRatingStarsAttribute(): array
    {
        $stars = [];
        $rating = $this->rating;
        
        for ($i = 1; $i <= 5; $i++) {
            if ($rating >= $i) {
                $stars[] = 'full';
            } elseif ($rating >= $i - 0.5) {
                $stars[] = 'half';
            } else {
                $stars[] = 'empty';
            }
        }
        
        return $stars;
    }

    /**
     * Increment views count.
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Update rating based on reviews.
     */
    public function updateRating(): void
    {
        $avgRating = $this->approvedReviews()->avg('rating') ?? 0;
        $reviewsCount = $this->approvedReviews()->count();
        
        $this->update([
            'rating' => round($avgRating, 2),
            'reviews_count' => $reviewsCount
        ]);
    }

    /**
     * Check if user can review this product.
     */
    public function canBeReviewedBy($userId): bool
    {
        // Check if user has purchased this product
        return $this->orderItems()
            ->whereHas('order', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->where('status', 'delivered');
            })
            ->exists();
    }

    /**
     * Check if user can review this product (alias method).
     */
    public function canBeReviewedByUser($userId): bool
    {
        return $this->canBeReviewedBy($userId);
    }

    /**
     * Get related products.
     */
    public function getRelatedProducts($limit = 4)
    {
        $categoryIds = $this->categories->pluck('id');
        
        return static::active()
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->where('id', '!=', $this->id)
            ->inStock()
            ->limit($limit)
            ->get();
    }
}