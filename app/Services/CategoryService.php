<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /**
     * Get all active categories with hierarchy.
     *
     * @return Collection
     */
    public function getAllCategoriesWithHierarchy(): Collection
    {
        return Cache::remember('categories_hierarchy', 3600, function () {
            return Category::where('is_active', true)
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->orderBy('sort_order')
                        ->orderBy('name');
                }])
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get main navigation categories (for header menu).
     *
     * @param int $limit
     * @return Collection
     */
    public function getMainNavigationCategories(int $limit = 8): Collection
    {
        return Cache::remember('main_navigation_categories', 3600, function () use ($limit) {
            return Category::where('is_active', true)
                ->where('show_in_menu', true)
                ->whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)
                        ->where('show_in_menu', true)
                        ->orderBy('sort_order')
                        ->orderBy('name')
                        ->limit(10); // Limit subcategories in menu
                }])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get featured categories for homepage.
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedCategories(int $limit = 6): Collection
    {
        return Cache::remember('featured_categories', 3600, function () use ($limit) {
            return Category::where('is_active', true)
                ->where('is_featured', true)
                ->withCount(['products' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get category by slug with products.
     *
     * @param string $slug
     * @return Category|null
     */
    public function getCategoryBySlug(string $slug): ?Category
    {
        return Category::where('slug', $slug)
            ->where('is_active', true)
            ->with([
                'parent',
                'children' => function ($query) {
                    $query->where('is_active', true)
                        ->withCount(['products' => function ($q) {
                            $q->where('is_active', true);
                        }])
                        ->orderBy('sort_order')
                        ->orderBy('name');
                }
            ])
            ->withCount(['products' => function ($query) {
                $query->where('is_active', true);
            }])
            ->first();
    }

    /**
     * Get category breadcrumbs.
     *
     * @param Category $category
     * @return Collection
     */
    public function getCategoryBreadcrumbs(Category $category): Collection
    {
        $breadcrumbs = collect();
        $current = $category;
        
        while ($current) {
            $breadcrumbs->prepend([
                'id' => $current->id,
                'name' => $current->name,
                'slug' => $current->slug,
                'url' => route('categories.show', $current->slug),
            ]);
            $current = $current->parent;
        }
        
        return $breadcrumbs;
    }

    /**
     * Get products for a category with filters.
     *
     * @param Category $category
     * @param array $filters
     * @param bool $includeSubcategories
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCategoryProducts(Category $category, array $filters = [], bool $includeSubcategories = true)
    {
        $categoryIds = [$category->id];
        
        if ($includeSubcategories) {
            $subcategoryIds = $this->getAllSubcategoryIds($category);
            $categoryIds = array_merge($categoryIds, $subcategoryIds);
        }
        
        $query = Product::whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('categories.id', $categoryIds);
        })->where('is_active', true);
        
        // Apply additional filters
        $filters['category_ids'] = $categoryIds;
        
        // Use ProductService for filtering
        app(ProductService::class)->applyFiltersToQuery($query, $filters);
        
        return $query->with(['categories'])
            ->paginate($filters['per_page'] ?? 12);
    }

    /**
     * Get all subcategory IDs recursively.
     *
     * @param Category $category
     * @return array
     */
    protected function getAllSubcategoryIds(Category $category): array
    {
        $subcategoryIds = [];
        
        foreach ($category->children as $child) {
            $subcategoryIds[] = $child->id;
            $subcategoryIds = array_merge($subcategoryIds, $this->getAllSubcategoryIds($child));
        }
        
        return $subcategoryIds;
    }

    /**
     * Get popular categories based on product sales.
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularCategories(int $limit = 8): Collection
    {
        return Cache::remember('popular_categories', 3600, function () use ($limit) {
            return Category::where('is_active', true)
                ->withCount(['products' => function ($query) {
                    $query->where('is_active', true);
                }])
                ->withSum(['products' => function ($query) {
                    $query->where('is_active', true);
                }], 'sales_count')
                ->having('products_count', '>', 0)
                ->orderBy('products_sum_sales_count', 'desc')
                ->orderBy('products_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Search categories.
     *
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function searchCategories(string $query, int $limit = 10): Collection
    {
        return Category::where('is_active', true)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            })
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('products_count', 'desc')
            ->orderBy('name')
            ->limit($limit)
            ->get();
    }

    /**
     * Get category filters for product filtering.
     *
     * @param Category|null $currentCategory
     * @return array
     */
    public function getCategoryFilters(?Category $currentCategory = null): array
    {
        $cacheKey = $currentCategory ? "category_filters_{$currentCategory->id}" : 'category_filters_all';
        
        return Cache::remember($cacheKey, 1800, function () use ($currentCategory) {
            $query = Category::where('is_active', true)
                ->withCount(['products' => function ($q) {
                    $q->where('is_active', true);
                }])
                ->having('products_count', '>', 0);
            
            if ($currentCategory) {
                // If we're in a category, show its siblings and children
                $query->where(function ($q) use ($currentCategory) {
                    $q->where('parent_id', $currentCategory->parent_id)
                      ->orWhere('parent_id', $currentCategory->id);
                });
            } else {
                // Show main categories
                $query->whereNull('parent_id');
            }
            
            return $query->orderBy('name')
                ->get()
                ->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'product_count' => $category->products_count,
                        'url' => route('categories.show', $category->slug),
                    ];
                })
                ->toArray();
        });
    }

    /**
     * Get price ranges for a category.
     *
     * @param Category $category
     * @return array
     */
    public function getCategoryPriceRanges(Category $category): array
    {
        $cacheKey = "category_price_ranges_{$category->id}";
        
        return Cache::remember($cacheKey, 3600, function () use ($category) {
            $categoryIds = array_merge([$category->id], $this->getAllSubcategoryIds($category));
            
            $prices = DB::table('products')
                ->join('product_categories', 'products.id', '=', 'product_categories.product_id')
                ->whereIn('product_categories.category_id', $categoryIds)
                ->where('products.is_active', true)
                ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price')
                ->selectRaw('MAX(COALESCE(sale_price, price)) as max_price')
                ->first();
            
            if (!$prices || !$prices->min_price) {
                return [];
            }
            
            $minPrice = floor($prices->min_price);
            $maxPrice = ceil($prices->max_price);
            $range = $maxPrice - $minPrice;
            
            // Create price ranges
            $ranges = [];
            if ($range > 0) {
                $step = max(1, $range / 5); // Create 5 ranges
                
                for ($i = 0; $i < 5; $i++) {
                    $from = $minPrice + ($step * $i);
                    $to = $i === 4 ? $maxPrice : $minPrice + ($step * ($i + 1)) - 1;
                    
                    $ranges[] = [
                        'from' => round($from),
                        'to' => round($to),
                        'label' => '$' . number_format($from) . ' - $' . number_format($to),
                    ];
                }
            }
            
            return $ranges;
        });
    }

    /**
     * Get category statistics.
     *
     * @return array
     */
    public function getCategoryStatistics(): array
    {
        return Cache::remember('category_statistics', 3600, function () {
            return [
                'total_categories' => Category::count(),
                'active_categories' => Category::where('is_active', true)->count(),
                'featured_categories' => Category::where('is_featured', true)->count(),
                'main_categories' => Category::whereNull('parent_id')->count(),
                'subcategories' => Category::whereNotNull('parent_id')->count(),
                'categories_with_products' => Category::whereHas('products', function ($query) {
                    $query->where('is_active', true);
                })->count(),
                'empty_categories' => Category::whereDoesntHave('products', function ($query) {
                    $query->where('is_active', true);
                })->count(),
            ];
        });
    }

    /**
     * Update category product counts.
     *
     * @param Category|null $category
     * @return void
     */
    public function updateCategoryProductCounts(?Category $category = null): void
    {
        if ($category) {
            $this->updateSingleCategoryProductCount($category);
        } else {
            // Update all categories
            Category::chunk(100, function ($categories) {
                foreach ($categories as $category) {
                    $this->updateSingleCategoryProductCount($category);
                }
            });
        }
        
        // Clear related caches
        $this->clearCategoryCaches();
    }

    /**
     * Update product count for a single category.
     *
     * @param Category $category
     * @return void
     */
    protected function updateSingleCategoryProductCount(Category $category): void
    {
        $productCount = $category->products()->where('is_active', true)->count();
        $category->update(['product_count' => $productCount]);
    }

    /**
     * Clear category-related caches.
     *
     * @return void
     */
    public function clearCategoryCaches(): void
    {
        Cache::forget('categories_hierarchy');
        Cache::forget('main_navigation_categories');
        Cache::forget('featured_categories');
        Cache::forget('popular_categories');
        Cache::forget('category_statistics');
        
        // Clear category-specific caches
        $categories = Category::all();
        foreach ($categories as $category) {
            Cache::forget("category_filters_{$category->id}");
            Cache::forget("category_price_ranges_{$category->id}");
        }
        Cache::forget('category_filters_all');
    }

    /**
     * Get category tree for admin interface.
     *
     * @return Collection
     */
    public function getCategoryTree(): Collection
    {
        return Category::with(['children' => function ($query) {
            $query->orderBy('sort_order')->orderBy('name');
        }])
        ->whereNull('parent_id')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();
    }

    /**
     * Get categories for select dropdown.
     *
     * @param bool $includeInactive
     * @return array
     */
    public function getCategoriesForSelect(bool $includeInactive = false): array
    {
        $query = Category::query();
        
        if (!$includeInactive) {
            $query->where('is_active', true);
        }
        
        $categories = $query->orderBy('name')->get();
        
        $options = [];
        foreach ($categories as $category) {
            $prefix = $category->parent_id ? '— ' : '';
            $options[$category->id] = $prefix . $category->name;
        }
        
        return $options;
    }
}