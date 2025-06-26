<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the user's wishlist.
     */
    public function index(): View
    {
        $wishlistItems = Wishlist::getItems();
        
        // Get recommended products based on wishlist items
        $recommendedProducts = collect();
        if ($wishlistItems->isNotEmpty()) {
            $categoryIds = $wishlistItems->pluck('product.categories')
                ->flatten()
                ->pluck('id')
                ->unique();
            
            $recommendedProducts = Product::active()
                ->inStock()
                ->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('categories.id', $categoryIds);
                })
                ->whereNotIn('id', $wishlistItems->pluck('product_id'))
                ->featured()
                ->limit(4)
                ->get();
        }

        // Get wishlist statistics
        $stats = [
            'total_items' => $wishlistItems->count(),
            'in_stock_items' => $wishlistItems->where('product.in_stock', true)->count(),
            'on_sale_items' => $wishlistItems->where('product.is_on_sale', true)->count(),
            'total_value' => $wishlistItems->sum(function ($item) {
                return $item->product->current_price;
            }),
        ];

        return view('wishlist.index', compact('wishlistItems', 'recommendedProducts', 'stats'));
    }

    /**
     * Add product to wishlist.
     */
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            
            // Check if product is active
            if (!$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este producto no está disponible.'
                ], 400);
            }

            $added = Wishlist::addItem($request->product_id);
            
            if (!$added) {
                return response()->json([
                    'success' => false,
                    'message' => 'El producto ya está en tu lista de deseos.',
                    'in_wishlist' => true,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Producto añadido a tu lista de deseos.',
                'in_wishlist' => true,
                'wishlist_count' => Wishlist::getItemsCount(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al añadir el producto a la lista de deseos.'
            ], 500);
        }
    }

    /**
     * Remove product from wishlist.
     */
    public function remove(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        try {
            $removed = Wishlist::removeItem($request->product_id);
            
            if (!$removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'El producto no está en tu lista de deseos.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado de tu lista de deseos.',
                'in_wishlist' => false,
                'wishlist_count' => Wishlist::getItemsCount(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto de la lista de deseos.'
            ], 500);
        }
    }

    /**
     * Toggle product in wishlist.
     */
    public function toggle(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            
            // Check if product is active
            if (!$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este producto no está disponible.'
                ], 400);
            }

            $result = Wishlist::toggleItem($request->product_id);
            
            return response()->json([
                'success' => true,
                'message' => $result['added'] 
                    ? 'Producto añadido a tu lista de deseos.' 
                    : 'Producto eliminado de tu lista de deseos.',
                'in_wishlist' => $result['added'],
                'wishlist_count' => Wishlist::getItemsCount(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la lista de deseos.'
            ], 500);
        }
    }

    /**
     * Clear all items from wishlist.
     */
    public function clear(): JsonResponse
    {
        try {
            Wishlist::clear();

            return response()->json([
                'success' => true,
                'message' => 'Lista de deseos vaciada.',
                'wishlist_count' => 0,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al vaciar la lista de deseos.'
            ], 500);
        }
    }

    /**
     * Move item from wishlist to cart.
     */
    public function moveToCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'sometimes|integer|min:1|max:99',
        ]);

        try {
            $product = Product::findOrFail($request->product_id);
            $quantity = $request->get('quantity', 1);
            
            // Check if product is in wishlist
            if (!Wishlist::hasItem($request->product_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El producto no está en tu lista de deseos.'
                ], 404);
            }

            // Check if product is available
            if (!$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este producto no está disponible.'
                ], 400);
            }

            if (!$product->in_stock || $product->stock_quantity < $quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay suficiente stock disponible.'
                ], 400);
            }

            // Add to cart
            $cartItem = Cart::addItem($request->product_id, $quantity);
            
            // Remove from wishlist
            Wishlist::removeItem($request->product_id);

            return response()->json([
                'success' => true,
                'message' => 'Producto movido al carrito.',
                'in_wishlist' => false,
                'wishlist_count' => Wishlist::getItemsCount(),
                'cart_count' => Cart::getItemsCount(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mover el producto al carrito.'
            ], 500);
        }
    }

    /**
     * Move all available items from wishlist to cart.
     */
    public function moveAllToCart(): JsonResponse
    {
        try {
            $result = Wishlist::moveToCart();

            $message = "Se movieron {$result['moved']} productos al carrito.";
            if ($result['unavailable'] > 0) {
                $message .= " {$result['unavailable']} productos no están disponibles.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'moved_items' => $result['moved'],
                'unavailable_items' => $result['unavailable'],
                'wishlist_count' => Wishlist::getItemsCount(),
                'cart_count' => Cart::getItemsCount(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al mover los productos al carrito.'
            ], 500);
        }
    }

    /**
     * Get wishlist items count.
     */
    public function count(): JsonResponse
    {
        $count = Wishlist::getItemsCount();
        
        return response()->json(['count' => $count]);
    }

    /**
     * Check if product is in wishlist.
     */
    public function check(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $inWishlist = Wishlist::hasItem($request->product_id);
        
        return response()->json(['in_wishlist' => $inWishlist]);
    }

    /**
     * Get wishlist summary.
     */
    public function summary(): JsonResponse
    {
        $summary = Wishlist::getSummary();
        
        return response()->json($summary);
    }

    /**
     * Get recent wishlist items.
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 5);
        $recentItems = Wishlist::getRecentItems($limit);
        
        $items = $recentItems->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_slug' => $item->product->slug,
                'product_image' => $item->product->main_image_url,
                'product_price' => $item->product->formatted_current_price,
                'product_url' => route('products.show', $item->product->slug),
                'in_stock' => $item->product->in_stock,
                'is_on_sale' => $item->product->is_on_sale,
                'added_at' => $item->created_at->diffForHumans(),
            ];
        });

        return response()->json($items);
    }

    /**
     * Get back in stock items.
     */
    public function backInStock(): JsonResponse
    {
        $items = Wishlist::getBackInStockItems();
        
        $backInStockItems = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_slug' => $item->product->slug,
                'product_image' => $item->product->main_image_url,
                'product_price' => $item->product->formatted_current_price,
                'product_url' => route('products.show', $item->product->slug),
                'stock_quantity' => $item->product->stock_quantity,
            ];
        });

        return response()->json($backInStockItems);
    }

    /**
     * Get on sale items from wishlist.
     */
    public function onSale(): JsonResponse
    {
        $items = Wishlist::getOnSaleItems();
        
        $onSaleItems = $items->map(function ($item) {
            return [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product->name,
                'product_slug' => $item->product->slug,
                'product_image' => $item->product->main_image_url,
                'product_price' => $item->product->formatted_current_price,
                'original_price' => $item->product->formatted_price,
                'discount_percentage' => $item->product->discount_percentage,
                'product_url' => route('products.show', $item->product->slug),
            ];
        });

        return response()->json($onSaleItems);
    }

    /**
     * Share wishlist (generate shareable link).
     */
    public function share(): JsonResponse
    {
        try {
            // Generate a shareable token for the wishlist
            $token = bin2hex(random_bytes(16));
            
            // Store the token in user's profile or a separate table
            // For now, we'll just return a mock response
            
            return response()->json([
                'success' => true,
                'share_url' => route('wishlist.public', $token),
                'message' => 'Enlace de compartir generado.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el enlace de compartir.'
            ], 500);
        }
    }

    /**
     * View public wishlist.
     */
    public function public(string $token): View
    {
        // This would fetch the wishlist by token
        // For now, return a placeholder view
        abort(404, 'Funcionalidad de lista de deseos pública no implementada.');
    }
}