<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories.
     */
    public function index(): View
    {
        $categories = Category::active()
            ->parents()
            ->ordered()
            ->withCount(['products' => function ($query) {
                $query->active();
            }])
            ->get();

        return view('categories.index', compact('categories'));
    }

    /**
     * Display the specified category.
     */
    public function show(string $slug, Request $request): View
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sort = $request->get('sort', 'newest');
        $perPage = $request->get('per_page', 12);
        $inStock = $request->get('in_stock');
        $onSale = $request->get('on_sale');
        $featured = $request->get('featured');
        $subcategory = $request->get('subcategory');

        // Get products query
        $productsQuery = $category->products()
            ->active()
            ->with(['categories']);

        // If subcategory is specified, filter by it
        if ($subcategory) {
            $subcategoryModel = Category::where('slug', $subcategory)
                ->where('parent_id', $category->id)
                ->where('is_active', true)
                ->first();
            
            if ($subcategoryModel) {
                $productsQuery = $subcategoryModel->products()
                    ->active()
                    ->with(['categories']);
            }
        }

        // Apply filters
        if ($minPrice || $maxPrice) {
            $productsQuery->priceRange($minPrice, $maxPrice);
        }

        if ($inStock) {
            $productsQuery->inStock();
        }

        if ($onSale) {
            $productsQuery->onSale();
        }

        if ($featured) {
            $productsQuery->featured();
        }

        // Apply sorting
        switch ($sort) {
            case 'price_low':
                $productsQuery->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $productsQuery->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name_asc':
                $productsQuery->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $productsQuery->orderBy('name', 'desc');
                break;
            case 'rating':
                $productsQuery->orderBy('average_rating', 'desc');
                break;
            case 'popular':
                $productsQuery->orderBy('views_count', 'desc');
                break;
            case 'oldest':
                $productsQuery->orderBy('created_at', 'asc');
                break;
            default: // newest
                $productsQuery->orderBy('created_at', 'desc');
                break;
        }

        $products = $productsQuery->paginate($perPage);
        $products->appends($request->query());

        // Get subcategories
        $subcategories = $category->children()
            ->active()
            ->ordered()
            ->withCount(['products' => function ($query) {
                $query->active();
            }])
            ->get();

        // Get price range for filters
        $priceRange = $category->products()
            ->active()
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        // Get breadcrumb
        $breadcrumb = $category->breadcrumb;

        // Get featured products from this category
        $featuredProducts = $category->products()
            ->active()
            ->featured()
            ->inStock()
            ->limit(4)
            ->get();

        // Get category statistics
        $stats = [
            'total_products' => $category->products()->active()->count(),
            'in_stock_products' => $category->products()->active()->inStock()->count(),
            'on_sale_products' => $category->products()->active()->onSale()->count(),
            'avg_price' => $category->products()->active()->avg('price'),
        ];

        return view('categories.show', compact(
            'category',
            'products',
            'subcategories',
            'priceRange',
            'breadcrumb',
            'featuredProducts',
            'stats',
            'minPrice',
            'maxPrice',
            'sort',
            'inStock',
            'onSale',
            'featured',
            'subcategory'
        ));
    }

    /**
     * Get categories for navigation menu.
     */
    public function navigation(): JsonResponse
    {
        $categories = Category::active()
            ->parents()
            ->ordered()
            ->with(['children' => function ($query) {
                $query->active()->ordered()->limit(10);
            }])
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'icon' => $category->icon,
                    'image_url' => $category->image_url,
                    'url' => route('categories.show', $category->slug),
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                            'url' => route('categories.show', $child->slug),
                        ];
                    }),
                ];
            });

        return response()->json($categories);
    }

    /**
     * Get category tree for filters.
     */
    public function tree(): JsonResponse
    {
        $categories = Category::active()
            ->parents()
            ->ordered()
            ->withCount(['products' => function ($query) {
                $query->active();
            }])
            ->with(['children' => function ($query) {
                $query->active()
                      ->ordered()
                      ->withCount(['products' => function ($q) {
                          $q->active();
                      }]);
            }])
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'products_count' => $category->products_count,
                    'url' => route('categories.show', $category->slug),
                    'children' => $category->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                            'products_count' => $child->products_count,
                            'url' => route('categories.show', $child->slug),
                        ];
                    }),
                ];
            });

        return response()->json($categories);
    }

    /**
     * Get popular categories.
     */
    public function popular(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 8);
        
        $categories = Category::active()
            ->parents()
            ->withCount(['products' => function ($query) {
                $query->active();
            }])
            ->orderBy('products_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'icon' => $category->icon,
                    'image_url' => $category->image_url,
                    'products_count' => $category->products_count,
                    'url' => route('categories.show', $category->slug),
                ];
            });

        return response()->json($categories);
    }

    /**
     * Search categories.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $categories = Category::active()
            ->where('name', 'LIKE', '%' . $query . '%')
            ->orWhere('description', 'LIKE', '%' . $query . '%')
            ->withCount(['products' => function ($q) {
                $q->active();
            }])
            ->orderBy('products_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                    'products_count' => $category->products_count,
                    'url' => route('categories.show', $category->slug),
                ];
            });

        return response()->json($categories);
    }

    /**
     * Get category breadcrumb.
     */
    public function breadcrumb(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json($category->breadcrumb);
    }

    /**
     * Get category with products for homepage sections.
     */
    public function withProducts(string $slug, Request $request): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $limit = $request->get('limit', 8);
        $sort = $request->get('sort', 'newest');

        $productsQuery = $category->products()
            ->active()
            ->inStock()
            ->with(['categories']);

        // Apply sorting
        switch ($sort) {
            case 'featured':
                $productsQuery->featured();
                break;
            case 'popular':
                $productsQuery->orderBy('views_count', 'desc');
                break;
            case 'rating':
                $productsQuery->orderBy('average_rating', 'desc');
                break;
            case 'price_low':
                $productsQuery->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $productsQuery->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            default: // newest
                $productsQuery->orderBy('created_at', 'desc');
                break;
        }

        $products = $productsQuery->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->formatted_current_price,
                    'original_price' => $product->is_on_sale ? $product->formatted_price : null,
                    'discount_percentage' => $product->discount_percentage,
                    'image' => $product->main_image_url,
                    'rating' => $product->average_rating,
                    'reviews_count' => $product->reviews_count,
                    'url' => route('products.show', $product->slug),
                    'in_stock' => $product->in_stock,
                ];
            });

        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'url' => route('categories.show', $category->slug),
            ],
            'products' => $products,
        ]);
    }

    /**
     * Get category filters (price range, brands, etc.).
     */
    public function filters(string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // Get price range
        $priceRange = $category->products()
            ->active()
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        // Get available attributes (this would be extended based on your product attributes)
        $attributes = [];

        // Get subcategories
        $subcategories = $category->children()
            ->active()
            ->ordered()
            ->withCount(['products' => function ($query) {
                $query->active();
            }])
            ->get()
            ->map(function ($subcategory) {
                return [
                    'id' => $subcategory->id,
                    'name' => $subcategory->name,
                    'slug' => $subcategory->slug,
                    'products_count' => $subcategory->products_count,
                ];
            });

        return response()->json([
            'price_range' => [
                'min' => $priceRange->min_price ?? 0,
                'max' => $priceRange->max_price ?? 1000,
            ],
            'subcategories' => $subcategories,
            'attributes' => $attributes,
        ]);
    }
}