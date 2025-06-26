<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Review;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request): View
    {
        $category = $request->get('category');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sort = $request->get('sort', 'newest');
        $perPage = $request->get('per_page', 12);
        $inStock = $request->get('in_stock');
        $onSale = $request->get('on_sale');
        $featured = $request->get('featured');

        $products = Product::active()
            ->with(['categories']);

        // Apply filters
        if ($category) {
            $products->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.slug', $category);
            });
        }

        if ($minPrice || $maxPrice) {
            $products->priceRange($minPrice, $maxPrice);
        }

        if ($inStock) {
            $products->inStock();
        }

        if ($onSale) {
            $products->onSale();
        }

        if ($featured) {
            $products->featured();
        }

        // Apply sorting
        switch ($sort) {
            case 'price_low':
                $products->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $products->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name_asc':
                $products->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $products->orderBy('name', 'desc');
                break;
            case 'rating':
                $products->orderBy('average_rating', 'desc');
                break;
            case 'popular':
                $products->orderBy('views_count', 'desc');
                break;
            case 'oldest':
                $products->orderBy('created_at', 'asc');
                break;
            default: // newest
                $products->orderBy('created_at', 'desc');
                break;
        }

        $results = $products->paginate($perPage);
        $results->appends($request->query());

        // Get categories for filter
        $categories = Category::active()
            ->parents()
            ->ordered()
            ->get();

        // Get price range for filters
        $priceRange = Product::active()
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        // Get current category if specified
        $currentCategory = null;
        if ($category) {
            $currentCategory = Category::where('slug', $category)->first();
        }

        return view('products.index', compact(
            'results',
            'categories',
            'priceRange',
            'currentCategory',
            'category',
            'minPrice',
            'maxPrice',
            'sort',
            'inStock',
            'onSale',
            'featured'
        ))->with('products', $results);
    }

    /**
     * Display the specified product.
     */
    public function show(string $slug): View
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['categories', 'reviews' => function ($query) {
                $query->approved()
                      ->with('user')
                      ->orderBy('created_at', 'desc');
            }])
            ->firstOrFail();

        // Increment views count
        $product->incrementViews();

        // Get related products
        $relatedProducts = $product->getRelatedProducts(8);

        // Get reviews summary
        $reviewsSummary = [
            'total' => $product->reviews->count(),
            'average' => $product->average_rating,
            'distribution' => [],
        ];

        // Calculate rating distribution
        for ($i = 5; $i >= 1; $i--) {
            $count = $product->reviews->where('rating', $i)->count();
            $percentage = $reviewsSummary['total'] > 0 ? ($count / $reviewsSummary['total']) * 100 : 0;
            $reviewsSummary['distribution'][$i] = [
                'count' => $count,
                'percentage' => round($percentage, 1),
            ];
        }

        // Check if user has this product in wishlist
        $inWishlist = false;
        if (Auth::check()) {
            $inWishlist = Wishlist::isInWishlist($product->id);
        }

        // Check if user can review this product
        $canReview = false;
        $userReview = null;
        if (Auth::check()) {
            // Check if user has purchased this product and it's delivered
            $canReview = $product->canBeReviewedByUser(Auth::id());
            
            // Get user's existing review if any
            $userReview = Review::where('product_id', $product->id)
                ->where('user_id', Auth::id())
                ->first();
        }

        // Get breadcrumb
        $breadcrumb = [];
        if ($product->categories->isNotEmpty()) {
            $category = $product->categories->first();
            $breadcrumb = $category->breadcrumb;
        }
        $breadcrumb[] = ['name' => $product->name, 'url' => null];

        return view('products.show', compact(
            'product',
            'relatedProducts',
            'reviewsSummary',
            'inWishlist',
            'canReview',
            'userReview',
            'breadcrumb'
        ));
    }

    /**
     * Get product quick view data.
     */
    public function quickView(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['categories'])
            ->firstOrFail();

        $inWishlist = false;
        if (Auth::check()) {
            $inWishlist = Wishlist::isInWishlist($product->id);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'price' => $product->price,
            'sale_price' => $product->sale_price,
            'current_price' => $product->current_price,
            'formatted_price' => $product->formatted_price,
            'formatted_sale_price' => $product->formatted_sale_price,
            'formatted_current_price' => $product->formatted_current_price,
            'is_on_sale' => $product->is_on_sale,
            'discount_percentage' => $product->discount_percentage,
            'in_stock' => $product->in_stock,
            'stock_status' => $product->stock_status,
            'images' => $product->image_urls,
            'main_image' => $product->main_image_url,
            'rating' => $product->average_rating,
            'rating_stars' => $product->rating_stars,
            'reviews_count' => $product->reviews_count,
            'categories' => $product->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ];
            }),
            'in_wishlist' => $inWishlist,
            'url' => route('products.show', $product->slug),
        ]);
    }

    /**
     * Get products by category.
     */
    public function byCategory(string $categorySlug, Request $request): View
    {
        $category = Category::where('slug', $categorySlug)
            ->where('is_active', true)
            ->firstOrFail();

        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sort = $request->get('sort', 'newest');
        $perPage = $request->get('per_page', 12);
        $inStock = $request->get('in_stock');
        $onSale = $request->get('on_sale');
        $featured = $request->get('featured');

        $products = $category->products()
            ->active()
            ->with(['categories']);

        // Apply filters
        if ($minPrice || $maxPrice) {
            $products->priceRange($minPrice, $maxPrice);
        }

        if ($inStock) {
            $products->inStock();
        }

        if ($onSale) {
            $products->onSale();
        }

        if ($featured) {
            $products->featured();
        }

        // Apply sorting
        switch ($sort) {
            case 'price_low':
                $products->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $products->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'name_asc':
                $products->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $products->orderBy('name', 'desc');
                break;
            case 'rating':
                $products->orderBy('average_rating', 'desc');
                break;
            case 'popular':
                $products->orderBy('views_count', 'desc');
                break;
            case 'oldest':
                $products->orderBy('created_at', 'asc');
                break;
            default: // newest
                $products->orderBy('created_at', 'desc');
                break;
        }

        $results = $products->paginate($perPage);
        $results->appends($request->query());

        // Get subcategories
        $subcategories = $category->children()
            ->active()
            ->ordered()
            ->get();

        // Get price range for filters
        $priceRange = $category->products()
            ->active()
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

        // Get breadcrumb
        $breadcrumb = $category->breadcrumb;

        return view('products.category', compact(
            'category',
            'results',
            'subcategories',
            'priceRange',
            'breadcrumb',
            'minPrice',
            'maxPrice',
            'sort',
            'inStock',
            'onSale',
            'featured'
        ));
    }

    /**
     * Get featured products for homepage.
     */
    public function featured(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 8);
        
        $products = Product::active()
            ->featured()
            ->inStock()
            ->with(['categories'])
            ->limit($limit)
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

        return response()->json($products);
    }

    /**
     * Get new arrivals.
     */
    public function newArrivals(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 8);
        $days = $request->get('days', 30);
        
        $products = Product::active()
            ->inStock()
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['categories'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
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
                    'created_at' => $product->created_at->format('M j, Y'),
                ];
            });

        return response()->json($products);
    }

    /**
     * Get products on sale.
     */
    public function onSale(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 8);
        
        $products = Product::active()
            ->inStock()
            ->onSale()
            ->with(['categories'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->formatted_current_price,
                    'original_price' => $product->formatted_price,
                    'discount_percentage' => $product->discount_percentage,
                    'image' => $product->main_image_url,
                    'rating' => $product->average_rating,
                    'reviews_count' => $product->reviews_count,
                    'url' => route('products.show', $product->slug),
                    'in_stock' => $product->in_stock,
                ];
            });

        return response()->json($products);
    }

    /**
     * Get new products.
     */
    public function newProducts(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 8);
        
        $products = Product::active()
            ->inStock()
            ->with(['categories'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->formatted_current_price,
                    'original_price' => $product->formatted_price,
                    'discount_percentage' => $product->discount_percentage,
                    'image' => $product->main_image_url,
                    'rating' => $product->average_rating,
                    'reviews_count' => $product->reviews_count,
                    'url' => route('products.show', $product->slug),
                    'in_stock' => $product->in_stock,
                    'is_new' => $product->created_at->diffInDays(now()) <= 30,
                ];
            });

        return response()->json($products);
    }

    /**
     * Compare products.
     */
    public function compare(Request $request): View
    {
        $productIds = $request->get('products', []);
        
        if (empty($productIds) || count($productIds) > 4) {
            return redirect()->route('products.index')
                ->with('error', 'Please select 1-4 products to compare.');
        }

        $products = Product::whereIn('id', $productIds)
            ->where('is_active', true)
            ->with(['categories'])
            ->get();

        if ($products->count() !== count($productIds)) {
            return redirect()->route('products.index')
                ->with('error', 'Some products were not found.');
        }

        return view('products.compare', compact('products'));
    }
}