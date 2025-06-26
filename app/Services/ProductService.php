<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ProductService
{
    /**
     * Get products with filters and pagination.
     *
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getProductsWithFilters(array $filters = []): LengthAwarePaginator
    {
        $query = Product::with(['categories', 'reviews'])
            ->where('is_active', true);

        // Category filter
        if (!empty($filters['category'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('slug', $filters['category'])
                  ->orWhere('id', $filters['category']);
            });
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        if (!empty($filters['min_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Stock filter
        if (!empty($filters['in_stock'])) {
            $query->where('stock', '>', 0);
        }

        // Featured filter
        if (!empty($filters['featured'])) {
            $query->where('is_featured', true);
        }

        // On sale filter
        if (!empty($filters['on_sale'])) {
            $query->where('sale_price', '>', 0)
                  ->whereColumn('sale_price', '<', 'price');
        }

        // Rating filter
        if (!empty($filters['min_rating'])) {
            $query->where('rating', '>=', $filters['min_rating']);
        }

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhereHas('categories', function ($categoryQuery) use ($search) {
                      $categoryQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        switch ($sortBy) {
            case 'price_low_high':
                $query->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high_low':
                $query->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'rating':
                $query->orderBy('rating', 'desc');
                break;
            case 'popularity':
                $query->orderBy('sales_count', 'desc');
                break;
            case 'name':
                $query->orderBy('name', $sortDirection);
                break;
            default:
                $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate($filters['per_page'] ?? 12);
    }

    /**
     * Search products with advanced features.
     *
     * @param string $query
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function searchProducts(string $query, array $filters = []): LengthAwarePaginator
    {
        $filters['search'] = $query;
        return $this->getProductsWithFilters($filters);
    }

    /**
     * Get search suggestions.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function getSearchSuggestions(string $query, int $limit = 10): array
    {
        $cacheKey = "search_suggestions_{$query}_{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            // Product suggestions
            $products = Product::where('is_active', true)
                ->where('name', 'like', "%{$query}%")
                ->select('id', 'name', 'slug', 'images')
                ->limit($limit)
                ->get()
                ->map(function ($product) {
                    return [
                        'type' => 'product',
                        'id' => $product->id,
                        'name' => $product->name,
                        'url' => route('products.show', $product->slug),
                        'image' => $product->image,
                    ];
                });

            // Category suggestions
            $categories = Category::where('is_active', true)
                ->where('name', 'like', "%{$query}%")
                ->select('id', 'name', 'slug')
                ->limit(5)
                ->get()
                ->map(function ($category) {
                    return [
                        'type' => 'category',
                        'id' => $category->id,
                        'name' => $category->name,
                        'url' => route('categories.show', $category->slug),
                    ];
                });

            return [
                'products' => $products,
                'categories' => $categories,
                'query' => $query,
            ];
        });
    }

    /**
     * Get featured products.
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedProducts(int $limit = 8): Collection
    {
        return Cache::remember('featured_products', 3600, function () use ($limit) {
            return Product::where('is_active', true)
                ->where('is_featured', true)
                ->with(['categories'])
                ->orderBy('rating', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get new products.
     *
     * @param int $limit
     * @return Collection
     */
    public function getNewProducts(int $limit = 8): Collection
    {
        return Cache::remember('new_products', 1800, function () use ($limit) {
            return Product::where('is_active', true)
                ->where('created_at', '>=', now()->subDays(30))
                ->with(['categories'])
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get products on sale.
     *
     * @param int $limit
     * @return Collection
     */
    public function getProductsOnSale(int $limit = 8): Collection
    {
        return Cache::remember('products_on_sale', 1800, function () use ($limit) {
            return Product::where('is_active', true)
                ->where('sale_price', '>', 0)
                ->whereColumn('sale_price', '<', 'price')
                ->with(['categories'])
                ->orderByRaw('((price - sale_price) / price) DESC') // Order by discount percentage
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get best rated products.
     *
     * @param int $limit
     * @return Collection
     */
    public function getBestRatedProducts(int $limit = 8): Collection
    {
        return Cache::remember('best_rated_products', 3600, function () use ($limit) {
            return Product::where('is_active', true)
                ->where('rating', '>=', 4.0)
                ->where('review_count', '>=', 5) // At least 5 reviews
                ->with(['categories'])
                ->orderBy('rating', 'desc')
                ->orderBy('review_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get popular products.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularProducts(int $limit = 8): Collection
    {
        return Cache::remember('popular_products', 3600, function () use ($limit) {
            return Product::where('is_active', true)
                ->where('sales_count', '>', 0)
                ->with(['categories'])
                ->orderBy('sales_count', 'desc')
                ->orderBy('views', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get related products.
     *
     * @param Product $product
     * @param int $limit
     * @return Collection
     */
    public function getRelatedProducts(Product $product, int $limit = 4): Collection
    {
        $categoryIds = $product->categories->pluck('id');
        
        return Product::where('is_active', true)
            ->where('id', '!=', $product->id)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->with(['categories'])
            ->orderBy('rating', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get recommended products for user.
     *
     * @param User|null $user
     * @param int $limit
     * @return Collection
     */
    public function getRecommendedProducts(?User $user = null, int $limit = 8): Collection
    {
        if (!$user) {
            return $this->getFeaturedProducts($limit);
        }

        // Get user's order history to find preferred categories
        $userCategoryIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('product_categories', 'order_items.product_id', '=', 'product_categories.product_id')
            ->where('orders.user_id', $user->id)
            ->where('orders.status', 'delivered')
            ->pluck('product_categories.category_id')
            ->unique();

        if ($userCategoryIds->isEmpty()) {
            return $this->getFeaturedProducts($limit);
        }

        // Get products from user's preferred categories
        $purchasedProductIds = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.user_id', $user->id)
            ->pluck('order_items.product_id');

        return Product::where('is_active', true)
            ->whereNotIn('id', $purchasedProductIds)
            ->whereHas('categories', function ($query) use ($userCategoryIds) {
                $query->whereIn('categories.id', $userCategoryIds);
            })
            ->with(['categories'])
            ->orderBy('rating', 'desc')
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Increment product views.
     *
     * @param Product $product
     * @return void
     */
    public function incrementViews(Product $product): void
    {
        // Use increment to avoid race conditions
        $product->increment('views');
        
        // Clear cache if needed
        Cache::forget('popular_products');
    }

    /**
     * Update product rating based on reviews.
     *
     * @param Product $product
     * @return void
     */
    public function updateProductRating(Product $product): void
    {
        $reviews = Review::where('product_id', $product->id)
            ->where('is_approved', true);
        
        $averageRating = $reviews->avg('rating') ?? 0;
        $reviewCount = $reviews->count();
        
        $product->update([
            'rating' => round($averageRating, 2),
            'review_count' => $reviewCount,
        ]);
        
        // Clear related caches
        Cache::forget('best_rated_products');
        Cache::forget('featured_products');
    }

    /**
     * Get product price (considering sale price).
     *
     * @param Product $product
     * @return float
     */
    public function getProductPrice(Product $product): float
    {
        return $product->sale_price > 0 && $product->sale_price < $product->price 
            ? $product->sale_price 
            : $product->price;
    }

    /**
     * Get product discount percentage.
     *
     * @param Product $product
     * @return int
     */
    public function getDiscountPercentage(Product $product): int
    {
        if ($product->sale_price <= 0 || $product->sale_price >= $product->price) {
            return 0;
        }
        
        return round((($product->price - $product->sale_price) / $product->price) * 100);
    }

    /**
     * Check if product is on sale.
     *
     * @param Product $product
     * @return bool
     */
    public function isOnSale(Product $product): bool
    {
        return $product->sale_price > 0 && $product->sale_price < $product->price;
    }

    /**
     * Check if product is new (created within last 30 days).
     *
     * @param Product $product
     * @return bool
     */
    public function isNew(Product $product): bool
    {
        return $product->created_at >= now()->subDays(30);
    }

    /**
     * Get products for comparison.
     *
     * @param array $productIds
     * @return Collection
     */
    public function getProductsForComparison(array $productIds): Collection
    {
        return Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->with(['categories', 'reviews'])
            ->get();
    }

    /**
     * Get product statistics.
     *
     * @return array
     */
    public function getProductStatistics(): array
    {
        return Cache::remember('product_statistics', 3600, function () {
            return [
                'total_products' => Product::count(),
                'active_products' => Product::where('is_active', true)->count(),
                'featured_products' => Product::where('is_featured', true)->count(),
                'products_on_sale' => Product::where('sale_price', '>', 0)
                    ->whereColumn('sale_price', '<', 'price')
                    ->count(),
                'out_of_stock' => Product::where('stock', 0)->count(),
                'low_stock' => Product::where('stock', '>', 0)
                    ->where('stock', '<=', 10)
                    ->count(),
                'average_price' => Product::where('is_active', true)->avg('price'),
                'total_views' => Product::sum('views'),
                'total_sales' => Product::sum('sales_count'),
            ];
        });
    }
}