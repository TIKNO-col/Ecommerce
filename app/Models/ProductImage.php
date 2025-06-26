<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'alt_text',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Get the product that owns this image.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the full URL for the image.
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    /**
     * Get the full path for the image.
     */
    public function getFullPathAttribute(): string
    {
        return Storage::path($this->image_path);
    }

    /**
     * Check if the image file exists.
     */
    public function exists(): bool
    {
        return Storage::exists($this->image_path);
    }

    /**
     * Delete the image file from storage.
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            return Storage::delete($this->image_path);
        }
        return true;
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // When deleting a product image, also delete the file
        static::deleting(function ($productImage) {
            $productImage->deleteFile();
        });

        // Ensure only one primary image per product
        static::saving(function ($productImage) {
            if ($productImage->is_primary) {
                // Remove primary flag from other images of the same product
                static::where('product_id', $productImage->product_id)
                    ->where('id', '!=', $productImage->id)
                    ->update(['is_primary' => false]);
            }
        });
    }

    /**
     * Scope to get primary images.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope to order by sort order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Get the image URL (method version).
     */
    public function getImageUrl(): string
    {
        return $this->getUrlAttribute();
    }
}