<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Category;
use App\Models\Brand;
use App\Models\SearchLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Exception;

class SearchService
{
    protected $productService;
    protected $categoryService;
    
    public function __construct(ProductService $productService, CategoryService $categoryService)
    {
        $this->productService = $productService;
        $this->categoryService = $categoryService;
    }

    /**
     * Perform advanced search with filters.
     *
     * @param string $query
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        try {
            // Log search query
            $this->logSearch($query, $filters);
            
            // Build search query
            $searchQuery = $this->buildSearchQuery($query, $filters);
            
            // Apply sorting
            $sortBy = $filters['sort_by'] ?? 'relevance';
            $searchQuery = $this->applySorting($searchQuery, $sortBy, $query);
            
            // Get paginated results
            $results = $searchQuery->paginate($perPage);
            
            // Add search metadata
            $results->appends(request()->query());
            
            return $results;
            
        } catch (Exception $e) {
            Log::error('Search failed', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage(),
            ]);
            
            // Return empty paginator on error
            return new LengthAwarePaginator([], 0, $perPage);
        }
    }

    /**
     * Get search suggestions for autocomplete.
     *
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function getSuggestions(string $query, int $limit = 10): array
    {
        if (strlen($query) < 2) {
            return [];
        }
        
        $cacheKey = "search_suggestions_" . md5(strtolower($query)) . "_{$limit}";
        
        return Cache::remember($cacheKey, 300, function () use ($query, $limit) {
            $suggestions = [];
            
            // Product suggestions
            $productSuggestions = Product::where('name', 'LIKE', "%{$query}%")
                ->where('is_active', true)
                ->select('name', 'slug', 'image')
                ->limit($limit)
                ->get()
                ->map(function ($product) {
                    return [
                        'type' => 'product',
                        'title' => $product->name,
                        'url' => route('products.show', $product->slug),
                        'image' => $product->image,
                    ];
                });
            
            // Category suggestions
            $categorySuggestions = Category::where('name', 'LIKE', "%{$query}%")
                ->where('is_active', true)
                ->select('name', 'slug')
                ->limit(5)
                ->get()
                ->map(function ($category) {
                    return [
                        'type' => 'category',
                        'title' => $category->name,
                        'url' => route('categories.show', $category->slug),
                        'image' => null,
                    ];
                });
            
            // Brand suggestions
            $brandSuggestions = Brand::where('name', 'LIKE', "%{$query}%")
                ->where('is_active', true)
                ->select('name', 'slug')
                ->limit(3)
                ->get()
                ->map(function ($brand) {
                    return [
                        'type' => 'brand',
                        'title' => $brand->name,
                        'url' => route('brands.show', $brand->slug),
                        'image' => null,
                    ];
                });
            
            // Popular search suggestions
            $popularSuggestions = $this->getPopularSearchTerms($query, 3);
            
            return [
                'products' => $productSuggestions->toArray(),
                'categories' => $categorySuggestions->toArray(),
                'brands' => $brandSuggestions->toArray(),
                'popular' => $popularSuggestions,
            ];
        });
    }

    /**
     * Get search filters for the current query.
     *
     * @param string $query
     * @param array $currentFilters
     * @return array
     */
    public function getSearchFilters(string $query, array $currentFilters = []): array
    {
        $cacheKey = "search_filters_" . md5($query . serialize($currentFilters));
        
        return Cache::remember($cacheKey, 600, function () use ($query, $currentFilters) {
            // Base query for getting filter options
            $baseQuery = $this->buildBaseSearchQuery($query);
            
            // Get available categories
            $categoryIds = (clone $baseQuery)->distinct()->pluck('category_id');
            $categories = Category::whereIn('id', $categoryIds)
                ->where('is_active', true)
                ->select('id', 'name', 'slug')
                ->get();
            
            // Get available brands
            $brandIds = (clone $baseQuery)->distinct()->pluck('brand_id');
            $brands = Brand::whereIn('id', $brandIds)
                ->where('is_active', true)
                ->select('id', 'name', 'slug')
                ->get();
            
            // Get price range
            $priceRange = (clone $baseQuery)->selectRaw('MIN(price) as min_price, MAX(price) as max_price')
                ->first();
            
            // Get available attributes (sizes, colors, etc.)
            $attributes = $this->getAvailableAttributes($baseQuery);
            
            return [
                'categories' => $categories->toArray(),
                'brands' => $brands->toArray(),
                'price_range' => [
                    'min' => $priceRange->min_price ?? 0,
                    'max' => $priceRange->max_price ?? 1000,
                ],
                'attributes' => $attributes,
                'sort_options' => [
                    'relevance' => 'Relevance',
                    'price_asc' => 'Price: Low to High',
                    'price_desc' => 'Price: High to Low',
                    'name_asc' => 'Name: A to Z',
                    'name_desc' => 'Name: Z to A',
                    'newest' => 'Newest First',
                    'rating' => 'Highest Rated',
                    'popularity' => 'Most Popular',
                ],
            ];
        });
    }

    /**
     * Get popular search terms.
     *
     * @param string|null $query
     * @param int $limit
     * @return array
     */
    public function getPopularSearchTerms(?string $query = null, int $limit = 10): array
    {
        $cacheKey = "popular_search_terms_" . md5($query ?? '') . "_{$limit}";
        
        return Cache::remember($cacheKey, 3600, function () use ($query, $limit) {
            $searchQuery = SearchLog::select('query', DB::raw('COUNT(*) as search_count'))
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('query')
                ->orderBy('search_count', 'desc');
            
            if ($query) {
                $searchQuery->where('query', 'LIKE', "%{$query}%");
            }
            
            return $searchQuery->limit($limit)
                ->pluck('query')
                ->map(function ($term) {
                    return [
                        'type' => 'popular',
                        'title' => $term,
                        'url' => route('search', ['q' => $term]),
                        'image' => null,
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get trending searches.
     *
     * @param int $limit
     * @return Collection
     */
    public function getTrendingSearches(int $limit = 10): Collection
    {
        $cacheKey = "trending_searches_{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($limit) {
            return SearchLog::select('query', DB::raw('COUNT(*) as search_count'))
                ->where('created_at', '>=', now()->subHours(24))
                ->groupBy('query')
                ->orderBy('search_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get search analytics.
     *
     * @param int $days
     * @return array
     */
    public function getSearchAnalytics(int $days = 30): array
    {
        $cacheKey = "search_analytics_{$days}";
        
        return Cache::remember($cacheKey, 3600, function () use ($days) {
            $startDate = now()->subDays($days);
            
            // Total searches
            $totalSearches = SearchLog::where('created_at', '>=', $startDate)->count();
            
            // Unique searches
            $uniqueSearches = SearchLog::where('created_at', '>=', $startDate)
                ->distinct('query')
                ->count();
            
            // Searches with results
            $searchesWithResults = SearchLog::where('created_at', '>=', $startDate)
                ->where('results_count', '>', 0)
                ->count();
            
            // Top search terms
            $topSearchTerms = SearchLog::select('query', DB::raw('COUNT(*) as search_count'))
                ->where('created_at', '>=', $startDate)
                ->groupBy('query')
                ->orderBy('search_count', 'desc')
                ->limit(10)
                ->get();
            
            // Searches by day
            $searchesByDay = SearchLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            // Zero result searches
            $zeroResultSearches = SearchLog::select('query', DB::raw('COUNT(*) as search_count'))
                ->where('created_at', '>=', $startDate)
                ->where('results_count', 0)
                ->groupBy('query')
                ->orderBy('search_count', 'desc')
                ->limit(10)
                ->get();
            
            return [
                'total_searches' => $totalSearches,
                'unique_searches' => $uniqueSearches,
                'searches_with_results' => $searchesWithResults,
                'success_rate' => $totalSearches > 0 ? round(($searchesWithResults / $totalSearches) * 100, 2) : 0,
                'top_search_terms' => $topSearchTerms,
                'searches_by_day' => $searchesByDay,
                'zero_result_searches' => $zeroResultSearches,
            ];
        });
    }

    /**
     * Build search query with filters.
     *
     * @param string $query
     * @param array $filters
     * @return Builder
     */
    protected function buildSearchQuery(string $query, array $filters): Builder
    {
        $searchQuery = $this->buildBaseSearchQuery($query);
        
        // Apply filters
        if (!empty($filters['categories'])) {
            $searchQuery->whereIn('category_id', $filters['categories']);
        }
        
        if (!empty($filters['brands'])) {
            $searchQuery->whereIn('brand_id', $filters['brands']);
        }
        
        if (!empty($filters['min_price'])) {
            $searchQuery->where('price', '>=', $filters['min_price']);
        }
        
        if (!empty($filters['max_price'])) {
            $searchQuery->where('price', '<=', $filters['max_price']);
        }
        
        if (!empty($filters['in_stock'])) {
            $searchQuery->where('stock_quantity', '>', 0);
        }
        
        if (!empty($filters['on_sale'])) {
            $searchQuery->whereNotNull('sale_price')
                ->where('sale_price', '<', DB::raw('price'));
        }
        
        if (!empty($filters['rating'])) {
            $searchQuery->where('average_rating', '>=', $filters['rating']);
        }
        
        // Apply attribute filters (size, color, etc.)
        if (!empty($filters['attributes'])) {
            foreach ($filters['attributes'] as $attributeName => $attributeValues) {
                if (!empty($attributeValues)) {
                    $searchQuery->whereHas('attributes', function ($query) use ($attributeName, $attributeValues) {
                        $query->where('name', $attributeName)
                            ->whereIn('value', $attributeValues);
                    });
                }
            }
        }
        
        return $searchQuery;
    }

    /**
     * Build base search query.
     *
     * @param string $query
     * @return Builder
     */
    protected function buildBaseSearchQuery(string $query): Builder
    {
        return Product::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                    ->orWhere('description', 'LIKE', "%{$query}%")
                    ->orWhere('short_description', 'LIKE', "%{$query}%")
                    ->orWhere('sku', 'LIKE', "%{$query}%")
                    ->orWhereHas('category', function ($categoryQuery) use ($query) {
                        $categoryQuery->where('name', 'LIKE', "%{$query}%");
                    })
                    ->orWhereHas('brand', function ($brandQuery) use ($query) {
                        $brandQuery->where('name', 'LIKE', "%{$query}%");
                    })
                    ->orWhereHas('tags', function ($tagQuery) use ($query) {
                        $tagQuery->where('name', 'LIKE', "%{$query}%");
                    });
            })
            ->with(['category', 'brand', 'images', 'reviews']);
    }

    /**
     * Apply sorting to search query.
     *
     * @param Builder $query
     * @param string $sortBy
     * @param string $searchTerm
     * @return Builder
     */
    protected function applySorting(Builder $query, string $sortBy, string $searchTerm): Builder
    {
        switch ($sortBy) {
            case 'price_asc':
                return $query->orderBy('price', 'asc');
                
            case 'price_desc':
                return $query->orderBy('price', 'desc');
                
            case 'name_asc':
                return $query->orderBy('name', 'asc');
                
            case 'name_desc':
                return $query->orderBy('name', 'desc');
                
            case 'newest':
                return $query->orderBy('created_at', 'desc');
                
            case 'rating':
                return $query->orderBy('average_rating', 'desc');
                
            case 'popularity':
                return $query->orderBy('views_count', 'desc');
                
            case 'relevance':
            default:
                // Sort by relevance (exact matches first, then partial matches)
                return $query->orderByRaw(
                    "CASE 
                        WHEN name = ? THEN 1
                        WHEN name LIKE ? THEN 2
                        WHEN description LIKE ? THEN 3
                        ELSE 4
                    END",
                    [$searchTerm, "{$searchTerm}%", "%{$searchTerm}%"]
                )->orderBy('views_count', 'desc');
        }
    }

    /**
     * Get available attributes for filtering.
     *
     * @param Builder $query
     * @return array
     */
    protected function getAvailableAttributes(Builder $query): array
    {
        $productIds = (clone $query)->pluck('id');
        
        if ($productIds->isEmpty()) {
            return [];
        }
        
        $attributes = DB::table('product_attributes')
            ->whereIn('product_id', $productIds)
            ->select('name', 'value')
            ->distinct()
            ->get()
            ->groupBy('name')
            ->map(function ($group) {
                return $group->pluck('value')->unique()->values();
            })
            ->toArray();
        
        return $attributes;
    }

    /**
     * Log search query.
     *
     * @param string $query
     * @param array $filters
     * @return void
     */
    protected function logSearch(string $query, array $filters = []): void
    {
        try {
            // Get results count for logging
            $resultsCount = $this->buildSearchQuery($query, $filters)->count();
            
            SearchLog::create([
                'query' => $query,
                'filters' => json_encode($filters),
                'results_count' => $resultsCount,
                'user_id' => auth()->id(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
        } catch (Exception $e) {
            Log::error('Failed to log search', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Clear search cache.
     *
     * @return void
     */
    public function clearSearchCache(): void
    {
        $patterns = [
            'search_suggestions_*',
            'search_filters_*',
            'popular_search_terms_*',
            'trending_searches_*',
            'search_analytics_*',
        ];
        
        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Get search suggestions for admin.
     *
     * @return array
     */
    public function getSearchSuggestionsForAdmin(): array
    {
        return [
            'popular_terms' => $this->getPopularSearchTerms(null, 20),
            'trending_terms' => $this->getTrendingSearches(10),
            'zero_result_terms' => SearchLog::select('query', DB::raw('COUNT(*) as search_count'))
                ->where('results_count', 0)
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('query')
                ->orderBy('search_count', 'desc')
                ->limit(10)
                ->get(),
        ];
    }
}