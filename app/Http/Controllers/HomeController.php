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
        // Get featured categories (limit to 8)
        $featuredCategories = Category::active()
            ->parents()
            ->ordered()
            ->limit(8)
            ->get();

        // Get featured products (limit to 12)
        $featuredProducts = Product::active()
            ->featured()
            ->inStock()
            ->with(['categories'])
            ->limit(12)
            ->get();

        // Get new arrivals (products created in last 30 days, limit to 8)
        $newArrivals = Product::active()
            ->inStock()
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['categories'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        // Get products on sale (limit to 8)
        $saleProducts = Product::active()
            ->inStock()
            ->onSale()
            ->with(['categories'])
            ->limit(8)
            ->get();

        // Get top rated products (limit to 6)
        $topRatedProducts = Product::active()
            ->inStock()
            ->topRated()
            ->with(['categories'])
            ->limit(6)
            ->get();

        // Get popular products (most viewed, limit to 6)
        $popularProducts = Product::active()
            ->inStock()
            ->popular()
            ->with(['categories'])
            ->limit(6)
            ->get();

        // Hero section data
        $heroProducts = Product::active()
            ->featured()
            ->inStock()
            ->with(['categories'])
            ->limit(3)
            ->get();

        // Statistics for homepage
        $stats = [
            'total_products' => Product::active()->count(),
            'total_categories' => Category::active()->count(),
            'happy_customers' => 10000, // This could come from orders or reviews
            'years_experience' => 5,
        ];

        // Testimonials (this could come from a testimonials table)
        $testimonials = [
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
            ->with(['categories']);

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

        // Get categories for filter
        $categories = Category::active()
            ->parents()
            ->ordered()
            ->get();

        // Get price range for filters
        $priceRange = Product::active()
            ->selectRaw('MIN(COALESCE(sale_price, price)) as min_price, MAX(COALESCE(sale_price, price)) as max_price')
            ->first();

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

        $suggestions = $products->concat($categories)->take(8);

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