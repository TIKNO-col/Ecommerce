<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'title',
        'comment',
        'is_verified',
        'is_approved',
        'images',
        'helpful_count',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'is_approved' => 'boolean',
        'images' => 'array',
        'helpful_count' => 'integer',
    ];

    /**
     * Get the product that owns the review.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the user that owns the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the review.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope a query to only include approved reviews.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include verified reviews.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope a query to filter by rating.
     */
    public function scopeRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope a query to filter by minimum rating.
     */
    public function scopeMinRating($query, $minRating)
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Scope a query to order by most helpful.
     */
    public function scopeMostHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    /**
     * Scope a query to order by newest.
     */
    public function scopeNewest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope a query to order by oldest.
     */
    public function scopeOldest($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope a query to order by highest rating.
     */
    public function scopeHighestRated($query)
    {
        return $query->orderBy('rating', 'desc');
    }

    /**
     * Scope a query to order by lowest rating.
     */
    public function scopeLowestRated($query)
    {
        return $query->orderBy('rating', 'asc');
    }

    /**
     * Get star rating as array for display.
     */
    public function getStarsAttribute(): array
    {
        $stars = [];
        for ($i = 1; $i <= 5; $i++) {
            $stars[] = [
                'filled' => $i <= $this->rating,
                'half' => false, // Could be extended for half stars
            ];
        }
        return $stars;
    }

    /**
     * Get rating percentage for progress bars.
     */
    public function getRatingPercentageAttribute(): int
    {
        return ($this->rating / 5) * 100;
    }

    /**
     * Get review image URLs.
     */
    public function getImageUrlsAttribute(): array
    {
        if (empty($this->images)) {
            return [];
        }
        
        return array_map(function ($image) {
            return Storage::url($image);
        }, $this->images);
    }

    /**
     * Get formatted date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('M j, Y');
    }

    /**
     * Get time ago format.
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get user's display name.
     */
    public function getUserDisplayNameAttribute(): string
    {
        if ($this->user) {
            $name = $this->user->name;
            // Mask the name for privacy (show first letter + ***)
            return substr($name, 0, 1) . str_repeat('*', max(0, strlen($name) - 2)) . (strlen($name) > 1 ? substr($name, -1) : '');
        }
        
        return 'Anonymous';
    }

    /**
     * Get user's avatar URL.
     */
    public function getUserAvatarAttribute(): string
    {
        if ($this->user && $this->user->avatar) {
            return Storage::url($this->user->avatar);
        }
        
        // Generate avatar based on user's name
        $name = $this->user ? $this->user->name : 'Anonymous';
        $initials = collect(explode(' ', $name))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=5D3FD3&color=fff&size=40';
    }

    /**
     * Check if review has images.
     */
    public function hasImages(): bool
    {
        return !empty($this->images);
    }

    /**
     * Get images count.
     */
    public function getImagesCountAttribute(): int
    {
        return count($this->images ?? []);
    }

    /**
     * Check if review is helpful (has helpful votes).
     */
    public function isHelpful(): bool
    {
        return $this->helpful_count > 0;
    }

    /**
     * Increment helpful count.
     */
    public function markAsHelpful()
    {
        $this->increment('helpful_count');
    }

    /**
     * Get review summary for display.
     */
    public function getSummaryAttribute(): string
    {
        if (strlen($this->comment) <= 100) {
            return $this->comment;
        }
        
        return substr($this->comment, 0, 100) . '...';
    }

    /**
     * Check if review needs truncation.
     */
    public function needsTruncation(): bool
    {
        return strlen($this->comment) > 100;
    }

    /**
     * Get rating text.
     */
    public function getRatingTextAttribute(): string
    {
        return match($this->rating) {
            1 => 'Poor',
            2 => 'Fair',
            3 => 'Good',
            4 => 'Very Good',
            5 => 'Excellent',
            default => 'Not Rated',
        };
    }

    /**
     * Get verification badge.
     */
    public function getVerificationBadgeAttribute(): string
    {
        if ($this->is_verified) {
            return '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Verified Purchase</span>';
        }
        
        return '';
    }

    /**
     * Create review from order item.
     */
    public static function createFromOrderItem($orderItem, $rating, $title, $comment, $images = [])
    {
        // Check if user already reviewed this product from this order
        $existingReview = static::where('product_id', $orderItem->product_id)
            ->where('user_id', $orderItem->order->user_id)
            ->where('order_id', $orderItem->order_id)
            ->first();
        
        if ($existingReview) {
            throw new \Exception('You have already reviewed this product.');
        }
        
        // Check if order is delivered
        if ($orderItem->order->status !== Order::STATUS_DELIVERED) {
            throw new \Exception('You can only review products from delivered orders.');
        }
        
        $review = static::create([
            'product_id' => $orderItem->product_id,
            'user_id' => $orderItem->order->user_id,
            'order_id' => $orderItem->order_id,
            'rating' => $rating,
            'title' => $title,
            'comment' => $comment,
            'is_verified' => true, // Verified because it's from a purchase
            'is_approved' => true, // Auto-approve verified reviews
            'images' => $images,
        ]);
        
        // Update product rating
        $orderItem->product->updateRating();
        
        return $review;
    }

    /**
     * Approve the review.
     */
    public function approve()
    {
        $this->update(['is_approved' => true]);
        
        // Update product rating
        $this->product->updateRating();
    }

    /**
     * Reject the review.
     */
    public function reject()
    {
        $this->update(['is_approved' => false]);
        
        // Update product rating
        $this->product->updateRating();
    }

    /**
     * Get review response from admin (if any).
     */
    public function getAdminResponseAttribute(): ?string
    {
        // This could be extended to include admin responses
        return null;
    }

    /**
     * Check if review can be edited.
     */
    public function canBeEdited(): bool
    {
        // Allow editing within 24 hours of creation
        return $this->created_at->diffInHours(now()) <= 24;
    }

    /**
     * Check if review can be deleted.
     */
    public function canBeDeleted(): bool
    {
        // Allow deletion within 48 hours of creation
        return $this->created_at->diffInHours(now()) <= 48;
    }
}