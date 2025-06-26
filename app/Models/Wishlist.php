<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class Wishlist extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
    ];

    /**
     * Get the user that owns the wishlist item.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the product associated with the wishlist item.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Scope a query to filter by current user.
     */
    public function scopeForCurrentUser($query)
    {
        return $query->where('user_id', Auth::id());
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to include only active products.
     */
    public function scopeWithActiveProducts($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('is_active', true);
        });
    }

    /**
     * Scope a query to include only in-stock products.
     */
    public function scopeWithInStockProducts($query)
    {
        return $query->whereHas('product', function ($q) {
            $q->where('in_stock', true);
        });
    }

    /**
     * Add product to wishlist.
     */
    public static function addProduct($productId, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            throw new \Exception('User must be authenticated to add to wishlist.');
        }
        
        // Check if product exists and is active
        $product = Product::where('id', $productId)
            ->where('is_active', true)
            ->first();
        
        if (!$product) {
            throw new \Exception('Product not found or not available.');
        }
        
        // Check if already in wishlist
        $existingItem = static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
        
        if ($existingItem) {
            return $existingItem;
        }
        
        return static::create([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    /**
     * Remove product from wishlist.
     */
    public static function removeProduct($productId, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            throw new \Exception('User must be authenticated.');
        }
        
        return static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();
    }

    /**
     * Toggle product in wishlist.
     */
    public static function toggleProduct($productId, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            throw new \Exception('User must be authenticated.');
        }
        
        $existingItem = static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
        
        if ($existingItem) {
            $existingItem->delete();
            return ['action' => 'removed', 'in_wishlist' => false];
        } else {
            static::addProduct($productId, $userId);
            return ['action' => 'added', 'in_wishlist' => true];
        }
    }

    /**
     * Check if product is in user's wishlist.
     */
    public static function isInWishlist($productId, $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return false;
        }
        
        return static::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Get user's wishlist items.
     */
    public static function getUserWishlist($userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return collect();
        }
        
        return static::with(['product' => function ($query) {
                $query->where('is_active', true)
                      ->with(['categories']);
            }])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($item) {
                return $item->product !== null;
            });
    }

    /**
     * Get wishlist count for user.
     */
    public static function getCount($userId = null): int
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return 0;
        }
        
        return static::whereHas('product', function ($query) {
                $query->where('is_active', true);
            })
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * Clear user's wishlist.
     */
    public static function clearWishlist($userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            throw new \Exception('User must be authenticated.');
        }
        
        return static::where('user_id', $userId)->delete();
    }

    /**
     * Move wishlist items to cart.
     */
    public static function moveToCart($productIds = null, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            throw new \Exception('User must be authenticated.');
        }
        
        $query = static::with('product')
            ->where('user_id', $userId)
            ->whereHas('product', function ($q) {
                $q->where('is_active', true)
                  ->where('in_stock', true);
            });
        
        if ($productIds) {
            $query->whereIn('product_id', $productIds);
        }
        
        $wishlistItems = $query->get();
        $movedCount = 0;
        $errors = [];
        
        foreach ($wishlistItems as $item) {
            try {
                Cart::addProduct($item->product_id, 1, [], $userId);
                $item->delete();
                $movedCount++;
            } catch (\Exception $e) {
                $errors[] = "Could not move {$item->product->name}: {$e->getMessage()}";
            }
        }
        
        return [
            'moved_count' => $movedCount,
            'errors' => $errors,
        ];
    }

    /**
     * Get wishlist summary.
     */
    public static function getSummary($userId = null): array
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return [
                'count' => 0,
                'total_value' => 0,
                'in_stock_count' => 0,
                'on_sale_count' => 0,
            ];
        }
        
        $wishlistItems = static::with('product')
            ->where('user_id', $userId)
            ->whereHas('product', function ($query) {
                $query->where('is_active', true);
            })
            ->get();
        
        $totalValue = 0;
        $inStockCount = 0;
        $onSaleCount = 0;
        
        foreach ($wishlistItems as $item) {
            if ($item->product) {
                $totalValue += $item->product->current_price;
                
                if ($item->product->in_stock) {
                    $inStockCount++;
                }
                
                if ($item->product->is_on_sale) {
                    $onSaleCount++;
                }
            }
        }
        
        return [
            'count' => $wishlistItems->count(),
            'total_value' => $totalValue,
            'formatted_total_value' => '$' . number_format($totalValue, 2),
            'in_stock_count' => $inStockCount,
            'on_sale_count' => $onSaleCount,
        ];
    }

    /**
     * Get recently added items.
     */
    public static function getRecentItems($limit = 5, $userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return collect();
        }
        
        return static::with(['product' => function ($query) {
                $query->where('is_active', true);
            }])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->filter(function ($item) {
                return $item->product !== null;
            });
    }

    /**
     * Get products that are back in stock.
     */
    public static function getBackInStockItems($userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return collect();
        }
        
        return static::with(['product' => function ($query) {
                $query->where('is_active', true)
                      ->where('in_stock', true);
            }])
            ->where('user_id', $userId)
            ->get()
            ->filter(function ($item) {
                return $item->product !== null;
            });
    }

    /**
     * Get products that are on sale.
     */
    public static function getOnSaleItems($userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return collect();
        }
        
        return static::with(['product' => function ($query) {
                $query->where('is_active', true)
                      ->where('sale_price', '>', 0);
            }])
            ->where('user_id', $userId)
            ->get()
            ->filter(function ($item) {
                return $item->product !== null && $item->product->is_on_sale;
            });
    }

    /**
     * Clean up wishlist (remove inactive products).
     */
    public static function cleanup($userId = null)
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return 0;
        }
        
        return static::whereDoesntHave('product', function ($query) {
                $query->where('is_active', true);
            })
            ->where('user_id', $userId)
            ->delete();
    }
}