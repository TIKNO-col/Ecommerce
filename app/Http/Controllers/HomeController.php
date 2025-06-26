<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    /**
     * Show the application homepage.
     */
    public function index(): View
    {
        // Cache key for homepage data
        $cacheKey = 'homepage_data_' . auth()->id() ?? 'guest';
        
        // Cache homepage data for 30 minutes
        $data = cache()->remember($cacheKey, 1800, function () {
            // Get featured categories (limit to 6) with optimized loading
            $featuredCategories = Category::active()
                ->featured()
                ->with(['products' => function ($query) {
                    $query->active()->inStock()->select('id', 'name', 'slug', 'price', 'sale_price', 'images')
                          ->limit(3);
                }])
                ->select('id', 'name', 'slug', 'description', 'image')
                ->limit(6)
                ->get();

            // Get featured products (limit to 8) with optimized loading
            $featuredProducts = Product::active()
                ->featured()
                ->inStock()
                ->with(['categories:id,name,slug'])
                ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'rating', 'reviews_count')
                ->limit(8)
                ->get();

            // Get new arrivals (products from last 30 days, limit to 8)
            $newArrivals = Product::active()
                ->inStock()
                ->newArrivals()
                ->with(['categories:id,name,slug'])
                ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'rating', 'reviews_count')
                ->limit(8)
                ->get();

            // Get sale products (products with discount, limit to 6)
            $saleProducts = Product::active()
                ->inStock()
                ->onSale()
                ->with(['categories:id,name,slug'])
                ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'rating', 'reviews_count')
                ->limit(6)
                ->get();

            // Get top rated products (limit to 6)
            $topRatedProducts = Product::active()
                ->inStock()
                ->topRated()
                ->with(['categories:id,name,slug'])
                ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'rating', 'reviews_count')
                ->limit(6)
                ->get();

            // Get popular products (most viewed, limit to 6)
            $popularProducts = Product::active()
                ->inStock()
                ->popular()
                ->with(['categories:id,name,slug'])
                ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'rating', 'reviews_count')
                ->limit(6)
                ->get();
                
            return compact('featuredCategories', 'featuredProducts', 'newArrivals', 'saleProducts', 'topRatedProducts', 'popularProducts');
        });
        
        // Extract cached data
        extract($data);

        // Hero section data (cached separately for 1 hour)
        $heroProducts = cache()->remember('hero_products', 3600, function () {
            return Product::active()
                ->featured()
                ->inStock()
                ->with(['categories:id,name,slug'])
                ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'description')
                ->limit(3)
                ->get();
        });

        // Statistics for homepage (cached for 6 hours)
        $stats = cache()->remember('homepage_stats', 21600, function () {
            return [
                'total_products' => Product::active()->count(),
                'total_categories' => Category::active()->count(),
                'happy_customers' => 10000, // This could come from orders or reviews
                'years_experience' => 5,
            ];
        });

        // Testimonials (cached for 24 hours)
        $testimonials = cache()->remember('homepage_testimonials', 86400, function () {
            return [
                [
                    'name' => 'Sarah Johnson',
                    'rating' => 5,
                    'comment' => 'Amazing quality products and fast shipping. Highly recommended!',
                    'avatar' => 'https://ui-avatars.com/api/?name=Sarah+Johnson&background=5D3FD3&color=fff&size=60',
                    'verified' => true,
                ],
                [
                    'name' => 'Mike Chen',
                    'rating' => 5,
                    'comment' => 'Great customer service and excellent product variety.',
                    'avatar' => 'https://ui-avatars.com/api/?name=Mike+Chen&background=FF7D00&color=fff&size=60',
                    'verified' => true,
                ],
                [
                    'name' => 'Emma Davis',
                    'rating' => 4,
                    'comment' => 'Love the user-friendly website and quick delivery.',
                    'avatar' => 'https://ui-avatars.com/api/?name=Emma+Davis&background=00CED1&color=fff&size=60',
                    'verified' => true,
                ],
            ];
        });

        return view('home', compact(
            'featuredCategories',
            'featuredProducts',
            'newArrivals',
            'saleProducts',
            'topRatedProducts',
            'popularProducts',
            'heroProducts',
            'stats',
            'testimonials'
        ))->with('newProducts', $newArrivals);
    }

    /**
     * Search products.
     */
    public function search(Request $request): View
    {
        $query = $request->get('q', '');
        $category = $request->get('category');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $sort = $request->get('sort', 'relevance');
        $perPage = $request->get('per_page', 12);

        $products = Product::active()
            ->inStock()
            ->with(['categories:id,name,slug'])
            ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'description', 'average_rating', 'views_count', 'created_at');

        // Apply search query
        if (!empty($query)) {
            $products->search($query);
        }

        // Apply category filter
        if ($category) {
            $products->whereHas('categories', function ($q) use ($category) {
                $q->where('categories.id', $category)
                  ->orWhere('categories.slug', $category);
            });
        }

        // Apply price range filter
        if ($minPrice || $maxPrice) {
            $products->priceRange($minPrice, $maxPrice);
        }

        // Apply sorting
        switch ($sort) {
            case 'price_low':
                $products->orderByRaw('COALESCE(sale_price, price) ASC');
                break;
            case 'price_high':
                $products->orderByRaw('COALESCE(sale_price, price) DESC');
                break;
            case 'newest':
                $products->orderBy('created_at', 'desc');
                break;
            case 'rating':
                $products->orderBy('average_rating', 'desc');
                break;
            case 'popular':
                $products->orderBy('views_count', 'desc');
                break;
            default: // relevance
                if (!empty($query)) {
                    // For relevance, we could implement a more sophisticated scoring system
                    $products->orderByRaw('CASE 
                        WHEN name LIKE ? THEN 1 
                        WHEN description LIKE ? THEN 2 
                        ELSE 3 END', [
                        '%' . $query . '%',
                        '%' . $query . '%'
                    ]);
                } else {
                    $products->orderBy('created_at', 'desc');
                }
                break;
        }

        $results = $products->paginate($perPage);
        $results->appends($request->query());

        // Get categories for filter (cached for 2 hours)
        $categories = cache()->remember('search_categories', 7200, function () {
            return Category::active()
                ->parents()
                ->ordered()
                ->select('id', 'name', 'slug')
                ->get();
        });

        // Get price range for filters (cached for 1 hour)
        $priceRange = cache()->remember('product_price_range', 3600, function () {
            return Product::active()
                ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
                ->first();
        });

        return view('search', compact(
            'results',
            'query',
            'category',
            'minPrice',
            'maxPrice',
            'sort',
            'categories',
            'priceRange'
        ));
    }

    /**
     * Get search suggestions for autocomplete.
     */
    public function searchSuggestions(Request $request)
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        // Cache suggestions for 15 minutes based on query
        $cacheKey = 'search_suggestions_' . md5(strtolower($query));
        
        $suggestions = cache()->remember($cacheKey, 900, function () use ($query) {
            // Get product suggestions
            $products = Product::active()
                ->search($query)
                ->limit(5)
                ->get(['id', 'name', 'slug', 'price', 'sale_price'])
                ->map(function ($product) {
                    return [
                        'type' => 'product',
                        'id' => $product->id,
                        'name' => $product->name,
                        'url' => route('products.show', $product->slug),
                        'price' => $product->formatted_current_price,
                    ];
                });

            // Get category suggestions
            $categories = Category::active()
                ->where('name', 'LIKE', '%' . $query . '%')
                ->limit(3)
                ->get(['id', 'name', 'slug'])
                ->map(function ($category) {
                    return [
                        'type' => 'category',
                        'id' => $category->id,
                        'name' => $category->name,
                        'url' => route('categories.show', $category->slug),
                    ];
                });

            return $products->concat($categories)->take(8);
        });

        return response()->json($suggestions);
    }

    /**
     * Show about page.
     */
    public function about(): View
    {
        $stats = [
            'years_experience' => 5,
            'happy_customers' => 10000,
            'products_sold' => 50000,
            'countries_served' => 25,
        ];

        $team = [
            [
                'name' => 'John Doe',
                'position' => 'CEO & Founder',
                'image' => 'https://ui-avatars.com/api/?name=John+Doe&background=5D3FD3&color=fff&size=200',
                'bio' => 'Passionate about bringing quality products to customers worldwide.',
            ],
            [
                'name' => 'Jane Smith',
                'position' => 'Head of Operations',
                'image' => 'https://ui-avatars.com/api/?name=Jane+Smith&background=FF7D00&color=fff&size=200',
                'bio' => 'Ensures smooth operations and excellent customer experience.',
            ],
            [
                'name' => 'David Wilson',
                'position' => 'Product Manager',
                'image' => 'https://ui-avatars.com/api/?name=David+Wilson&background=00CED1&color=fff&size=200',
                'bio' => 'Curates the best products for our diverse customer base.',
            ],
        ];

        return view('about', compact('stats', 'team'));
    }

    /**
     * Show contact page and handle form submission.
     */
    public function contact(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'subject' => 'required|string|max:255',
                'message' => 'required|string|max:2000',
            ]);

            // Here you would typically send an email or store the message
            // For now, we'll just return a success response
            
            return back()->with('success', '¡Gracias por tu mensaje! Te responderemos pronto.');
        }

        return view('contact');
    }
}