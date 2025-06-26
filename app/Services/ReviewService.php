<?php

namespace App\Services;

use App\Models\Review;
use App\Models\Product;
use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Create a new review.
     *
     * @param array $data
     * @param User $user
     * @return Review
     * @throws ValidationException
     */
    public function createReview(array $data, User $user): Review
    {
        // Validate that user has purchased the product
        if (!$this->hasUserPurchasedProduct($user, $data['product_id'])) {
            throw ValidationException::withMessages([
                'product_id' => 'You can only review products you have purchased.'
            ]);
        }

        // Check if user already reviewed this product
        if ($this->hasUserReviewedProduct($user, $data['product_id'])) {
            throw ValidationException::withMessages([
                'product_id' => 'You have already reviewed this product.'
            ]);
        }

        // Create the review
        $review = Review::create([
            'user_id' => $user->id,
            'product_id' => $data['product_id'],
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'is_approved' => false, // Reviews need approval by default
            'is_verified_purchase' => true,
        ]);

        // Update product rating
        $product = Product::find($data['product_id']);
        if ($product) {
            $this->productService->updateProductRating($product);
        }

        return $review;
    }

    /**
     * Update an existing review.
     *
     * @param Review $review
     * @param array $data
     * @param User $user
     * @return Review
     * @throws ValidationException
     */
    public function updateReview(Review $review, array $data, User $user): Review
    {
        // Check if user owns the review
        if ($review->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'review' => 'You can only update your own reviews.'
            ]);
        }

        // Update the review
        $review->update([
            'rating' => $data['rating'] ?? $review->rating,
            'title' => $data['title'] ?? $review->title,
            'comment' => $data['comment'] ?? $review->comment,
            'is_approved' => false, // Reset approval status on update
        ]);

        // Update product rating
        $this->productService->updateProductRating($review->product);

        return $review;
    }

    /**
     * Delete a review.
     *
     * @param Review $review
     * @param User $user
     * @return bool
     * @throws ValidationException
     */
    public function deleteReview(Review $review, User $user): bool
    {
        // Check if user owns the review
        if ($review->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'review' => 'You can only delete your own reviews.'
            ]);
        }

        $product = $review->product;
        $deleted = $review->delete();

        if ($deleted && $product) {
            // Update product rating after deletion
            $this->productService->updateProductRating($product);
        }

        return $deleted;
    }

    /**
     * Get reviews for a product with pagination.
     *
     * @param int $productId
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getProductReviews(int $productId, array $filters = []): LengthAwarePaginator
    {
        $query = Review::where('product_id', $productId)
            ->where('is_approved', true)
            ->with(['user']);

        // Rating filter
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        // Verified purchase filter
        if (!empty($filters['verified_only'])) {
            $query->where('is_verified_purchase', true);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        switch ($sortBy) {
            case 'rating_high':
                $query->orderBy('rating', 'desc')->orderBy('created_at', 'desc');
                break;
            case 'rating_low':
                $query->orderBy('rating', 'asc')->orderBy('created_at', 'desc');
                break;
            case 'helpful':
                $query->orderBy('helpful_count', 'desc')->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Get user's reviews with pagination.
     *
     * @param User $user
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getUserReviews(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Review::where('user_id', $user->id)
            ->with(['product']);

        // Status filter
        if (isset($filters['approved'])) {
            $query->where('is_approved', $filters['approved']);
        }

        // Rating filter
        if (!empty($filters['rating'])) {
            $query->where('rating', $filters['rating']);
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 10);
    }

    /**
     * Get pending reviews for admin moderation.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getPendingReviews(array $filters = []): LengthAwarePaginator
    {
        $query = Review::where('is_approved', false)
            ->with(['user', 'product']);

        // Date filter
        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'asc')
            ->paginate($filters['per_page'] ?? 20);
    }

    /**
     * Approve a review.
     *
     * @param Review $review
     * @return Review
     */
    public function approveReview(Review $review): Review
    {
        $review->update(['is_approved' => true]);
        
        // Update product rating
        $this->productService->updateProductRating($review->product);
        
        return $review;
    }

    /**
     * Reject a review.
     *
     * @param Review $review
     * @return Review
     */
    public function rejectReview(Review $review): Review
    {
        $review->update(['is_approved' => false]);
        
        return $review;
    }

    /**
     * Bulk approve reviews.
     *
     * @param array $reviewIds
     * @return int
     */
    public function bulkApproveReviews(array $reviewIds): int
    {
        $updated = Review::whereIn('id', $reviewIds)
            ->where('is_approved', false)
            ->update(['is_approved' => true]);

        // Update product ratings for affected products
        $productIds = Review::whereIn('id', $reviewIds)
            ->pluck('product_id')
            ->unique();

        foreach ($productIds as $productId) {
            $product = Product::find($productId);
            if ($product) {
                $this->productService->updateProductRating($product);
            }
        }

        return $updated;
    }

    /**
     * Mark review as helpful.
     *
     * @param Review $review
     * @param User $user
     * @return bool
     */
    public function markAsHelpful(Review $review, User $user): bool
    {
        // Check if user already marked this review as helpful
        $exists = DB::table('review_helpful')
            ->where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->exists();

        if ($exists) {
            return false;
        }

        // Add helpful vote
        DB::table('review_helpful')->insert([
            'review_id' => $review->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Update helpful count
        $review->increment('helpful_count');

        return true;
    }

    /**
     * Remove helpful mark from review.
     *
     * @param Review $review
     * @param User $user
     * @return bool
     */
    public function removeHelpfulMark(Review $review, User $user): bool
    {
        $deleted = DB::table('review_helpful')
            ->where('review_id', $review->id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted) {
            $review->decrement('helpful_count');
            return true;
        }

        return false;
    }

    /**
     * Get review statistics for a product.
     *
     * @param int $productId
     * @return array
     */
    public function getProductReviewStatistics(int $productId): array
    {
        $cacheKey = "product_review_stats_{$productId}";
        
        return Cache::remember($cacheKey, 1800, function () use ($productId) {
            $reviews = Review::where('product_id', $productId)
                ->where('is_approved', true);

            $totalReviews = $reviews->count();
            $averageRating = $reviews->avg('rating') ?? 0;
            
            // Rating distribution
            $ratingDistribution = [];
            for ($i = 1; $i <= 5; $i++) {
                $count = $reviews->where('rating', $i)->count();
                $percentage = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                $ratingDistribution[$i] = [
                    'count' => $count,
                    'percentage' => round($percentage, 1),
                ];
            }

            return [
                'total_reviews' => $totalReviews,
                'average_rating' => round($averageRating, 2),
                'rating_distribution' => $ratingDistribution,
                'verified_purchases' => $reviews->where('is_verified_purchase', true)->count(),
            ];
        });
    }

    /**
     * Get products that user can review.
     *
     * @param User $user
     * @return Collection
     */
    public function getReviewableProducts(User $user): Collection
    {
        // Get products from delivered orders that haven't been reviewed yet
        $deliveredProductIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $user->id)
            ->where('orders.status', 'delivered')
            ->pluck('order_items.product_id')
            ->unique();

        $reviewedProductIds = Review::where('user_id', $user->id)
            ->pluck('product_id');

        $reviewableProductIds = $deliveredProductIds->diff($reviewedProductIds);

        return Product::whereIn('id', $reviewableProductIds)
            ->where('is_active', true)
            ->with(['categories'])
            ->get();
    }

    /**
     * Check if user has purchased a product.
     *
     * @param User $user
     * @param int $productId
     * @return bool
     */
    protected function hasUserPurchasedProduct(User $user, int $productId): bool
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $user->id)
            ->where('order_items.product_id', $productId)
            ->where('orders.status', 'delivered')
            ->exists();
    }

    /**
     * Check if user has already reviewed a product.
     *
     * @param User $user
     * @param int $productId
     * @return bool
     */
    protected function hasUserReviewedProduct(User $user, int $productId): bool
    {
        return Review::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Get recent reviews for homepage or dashboard.
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentReviews(int $limit = 5): Collection
    {
        return Cache::remember('recent_reviews', 900, function () use ($limit) {
            return Review::where('is_approved', true)
                ->with(['user', 'product'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get review statistics for admin dashboard.
     *
     * @return array
     */
    public function getReviewStatistics(): array
    {
        return Cache::remember('review_statistics', 3600, function () {
            return [
                'total_reviews' => Review::count(),
                'approved_reviews' => Review::where('is_approved', true)->count(),
                'pending_reviews' => Review::where('is_approved', false)->count(),
                'verified_reviews' => Review::where('is_verified_purchase', true)->count(),
                'average_rating' => Review::where('is_approved', true)->avg('rating') ?? 0,
                'reviews_this_month' => Review::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'reviews_last_month' => Review::whereMonth('created_at', now()->subMonth()->month)
                    ->whereYear('created_at', now()->subMonth()->year)
                    ->count(),
            ];
        });
    }

    /**
     * Clear review-related caches.
     *
     * @param int|null $productId
     * @return void
     */
    public function clearReviewCaches(?int $productId = null): void
    {
        Cache::forget('recent_reviews');
        Cache::forget('review_statistics');
        
        if ($productId) {
            Cache::forget("product_review_stats_{$productId}");
        }
    }
}