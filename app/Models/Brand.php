<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'logo',
        'website',
        'is_active',
        'sort_order',
        'meta_data',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_data' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($brand) {
            if (empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });

        static::updating(function ($brand) {
            if ($brand->isDirty('name') && empty($brand->slug)) {
                $brand->slug = Str::slug($brand->name);
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Scope a query to only include active brands.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order brands by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Get the products for the brand.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the active products for the brand.
     */
    public function activeProducts(): HasMany
    {
        return $this->products()->where('is_active', true);
    }

    /**
     * Get the logo URL.
     */
    public function getLogoUrl(): string
    {
        if ($this->logo) {
            return asset('storage/' . $this->logo);
        }

        return 'https://via.placeholder.com/200x100/f8f9fa/6c757d?text=' . urlencode($this->name);
    }

    /**
     * Get the products count attribute.
     */
    public function getProductsCountAttribute(): int
    {
        return $this->activeProducts()->count();
    }
}