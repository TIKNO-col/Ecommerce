<?php

namespace App\Services;

use App\Models\Wishlist;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class WishlistService
{
    /**
     * Add product to user's wishlist.
     *
     * @param User $user
     * @param int $productId
     * @return Wishlist
     * @throws ValidationException
     */
    public function addToWishlist(User $user, int $productId): Wishlist
    {
        // Check if product exists and is active
        $product = Product::where('id', $productId)
            ->where('is_active', true)
            ->first();
        
        if (!$product) {
            throw ValidationException::withMessages([
                'product_id' => 'Product not found or is not available.'
            ]);
        }

        // Check if product is already in wishlist
        $existingWishlistItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();
        
        if ($existingWishlistItem) {
            throw ValidationException::withMessages([
                'product_id' => 'Product is already in your wishlist.'
            ]);
        }

        // Add to wishlist
        $wishlistItem = Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        // Clear user's wishlist cache
        $this->clearUserWishlistCache($user->id);

        return $wishlistItem;
    }

    /**
     * Remove product from user's wishlist.
     *
     * @param User $user
     * @param int $productId
     * @return bool
     */
    public function removeFromWishlist(User $user, int $productId): bool
    {
        $deleted = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->delete();

        if ($deleted) {
            // Clear user's wishlist cache
            $this->clearUserWishlistCache($user->id);
        }

        return $deleted > 0;
    }

    /**
     * Toggle product in user's wishlist.
     *
     * @param User $user
     * @param int $productId
     * @return array
     */
    public function toggleWishlist(User $user, int $productId): array
    {
        $existingWishlistItem = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existingWishlistItem) {
            // Remove from wishlist
            $existingWishlistItem->delete();
            $this->clearUserWishlistCache($user->id);
            
            return [
                'action' => 'removed',
                'message' => 'Product removed from wishlist',
                'in_wishlist' => false,
            ];
        } else {
            // Add to wishlist
            try {
                $this->addToWishlist($user, $productId);
                
                return [
                    'action' => 'added',
                    'message' => 'Product added to wishlist',
                    'in_wishlist' => true,
                ];
            } catch (ValidationException $e) {
                return [
                    'action' => 'error',
                    'message' => $e->getMessage(),
                    'in_wishlist' => false,
                ];
            }
        }
    }

    /**
     * Get user's wishlist with pagination.
     *
     * @param User $user
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getUserWishlist(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Wishlist::where('user_id', $user->id)
            ->with(['product' => function ($q) {
                $q->where('is_active', true)
                  ->with(['categories']);
            }])
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            });

        // Category filter
        if (!empty($filters['category'])) {
            $query->whereHas('product.categories', function ($q) use ($filters) {
                $q->where('slug', $filters['category'])
                  ->orWhere('id', $filters['category']);
            });
        }

        // Price range filter
        if (!empty($filters['min_price'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->whereRaw('COALESCE(sale_price, price) >= ?', [$filters['min_price']]);
            });
        }
        if (!empty($filters['max_price'])) {
            $query->whereHas('product', function ($q) use ($filters) {
                $q->whereRaw('COALESCE(sale_price, price) <= ?', [$filters['max_price']]);
            });
        }

        // Availability filter
        if (!empty($filters['in_stock'])) {
            $query->whereHas('product', function ($q) {
                $q->where('stock', '>', 0);
            });
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        
        switch ($sortBy) {
            case 'price_low_high':
                $query->join('products', 'wishlists.product_id', '=', 'products.id')
                      ->orderByRaw('COALESCE(products.sale_price, products.price) ASC');
                break;
            case 'price_high_low':
                $query->join('products', 'wishlists.product_id', '=', 'products.id')
                      ->orderByRaw('COALESCE(products.sale_price, products.price) DESC');
                break;
            case 'name':
                $query->join('products', 'wishlists.product_id', '=', 'products.id')
                      ->orderBy('products.name', $sortDirection);
                break;
            default:
                $query->orderBy($sortBy, $sortDirection);
        }

        return $query->paginate($filters['per_page'] ?? 12);
    }

    /**
     * Get user's wishlist items (simple collection).
     *
     * @param User $user
     * @return Collection
     */
    public function getUserWishlistItems(User $user): Collection
    {
        $cacheKey = "user_wishlist_{$user->id}";
        
        return Cache::remember($cacheKey, 1800, function () use ($user) {
            return Wishlist::where('user_id', $user->id)
                ->with(['product' => function ($q) {
                    $q->where('is_active', true)
                      ->select('id', 'name', 'slug', 'price', 'sale_price', 'images', 'stock');
                }])
                ->whereHas('product', function ($q) {
                    $q->where('is_active', true);
                })
                ->orderBy('created_at', 'desc')
                ->get();
        });
    }

    /**
     * Check if product is in user's wishlist.
     *
     * @param User $user
     * @param int $productId
     * @return bool
     */
    public function isInWishlist(User $user, int $productId): bool
    {
        return Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Get wishlist product IDs for user.
     *
     * @param User $user
     * @return array
     */
    public function getUserWishlistProductIds(User $user): array
    {
        $cacheKey = "user_wishlist_ids_{$user->id}";
        
        return Cache::remember($cacheKey, 1800, function () use ($user) {
            return Wishlist::where('user_id', $user->id)
                ->pluck('product_id')
                ->toArray();
        });
    }

    /**
     * Get wishlist count for user.
     *
     * @param User $user
     * @return int
     */
    public function getWishlistCount(User $user): int
    {
        $cacheKey = "user_wishlist_count_{$user->id}";
        
        return Cache::remember($cacheKey, 1800, function () use ($user) {
            return Wishlist::where('user_id', $user->id)
                ->whereHas('product', function ($q) {
                    $q->where('is_active', true);
                })
                ->count();
        });
    }

    /**
     * Clear user's wishlist.
     *
     * @param User $user
     * @return int
     */
    public function clearWishlist(User $user): int
    {
        $deleted = Wishlist::where('user_id', $user->id)->delete();
        
        if ($deleted) {
            $this->clearUserWishlistCache($user->id);
        }
        
        return $deleted;
    }

    /**
     * Move wishlist items to cart.
     *
     * @param User $user
     * @param array $productIds
     * @param CartService $cartService
     * @return array
     */
    public function moveToCart(User $user, array $productIds, CartService $cartService): array
    {
        $results = [
            'moved' => [],
            'failed' => [],
            'out_of_stock' => [],
        ];

        $wishlistItems = Wishlist::where('user_id', $user->id)
            ->whereIn('product_id', $productIds)
            ->with('product')
            ->get();

        foreach ($wishlistItems as $wishlistItem) {
            $product = $wishlistItem->product;
            
            if (!$product || !$product->is_active) {
                $results['failed'][] = $product ? $product->name : 'Unknown product';
                continue;
            }
            
            if ($product->stock <= 0) {
                $results['out_of_stock'][] = $product->name;
                continue;
            }

            try {
                // Add to cart
                $cartService->addToCart($user, $product->id, 1);
                
                // Remove from wishlist
                $wishlistItem->delete();
                
                $results['moved'][] = $product->name;
            } catch (\Exception $e) {
                $results['failed'][] = $product->name;
            }
        }

        // Clear wishlist cache
        $this->clearUserWishlistCache($user->id);

        return $results;
    }

    /**
     * Share wishlist (get shareable data).
     *
     * @param User $user
     * @return array
     */
    public function shareWishlist(User $user): array
    {
        $wishlistItems = $this->getUserWishlistItems($user);
        
        $shareData = [
            'user_name' => $user->name,
            'total_items' => $wishlistItems->count(),
            'total_value' => 0,
            'items' => [],
            'share_url' => route('wishlist.public', ['user' => $user->id, 'token' => $this->generateShareToken($user)]),
        ];

        foreach ($wishlistItems as $item) {
            if ($item->product) {
                $price = $item->product->sale_price > 0 ? $item->product->sale_price : $item->product->price;
                $shareData['total_value'] += $price;
                
                $shareData['items'][] = [
                    'name' => $item->product->name,
                    'price' => $price,
                    'image' => $item->product->image,
                    'url' => route('products.show', $item->product->slug),
                ];
            }
        }

        return $shareData;
    }

    /**
     * Get public wishlist for sharing.
     *
     * @param int $userId
     * @param string $token
     * @return array|null
     */
    public function getPublicWishlist(int $userId, string $token): ?array
    {
        $user = User::find($userId);
        
        if (!$user || !$this->verifyShareToken($user, $token)) {
            return null;
        }

        return $this->shareWishlist($user);
    }

    /**
     * Get wishlist statistics for user.
     *
     * @param User $user
     * @return array
     */
    public function getWishlistStatistics(User $user): array
    {
        $wishlistItems = $this->getUserWishlistItems($user);
        
        $statistics = [
            'total_items' => $wishlistItems->count(),
            'total_value' => 0,
            'average_price' => 0,
            'in_stock_items' => 0,
            'out_of_stock_items' => 0,
            'on_sale_items' => 0,
            'categories' => [],
        ];

        $categoryCount = [];
        $prices = [];

        foreach ($wishlistItems as $item) {
            if ($item->product) {
                $product = $item->product;
                $price = $product->sale_price > 0 ? $product->sale_price : $product->price;
                
                $statistics['total_value'] += $price;
                $prices[] = $price;
                
                if ($product->stock > 0) {
                    $statistics['in_stock_items']++;
                } else {
                    $statistics['out_of_stock_items']++;
                }
                
                if ($product->sale_price > 0 && $product->sale_price < $product->price) {
                    $statistics['on_sale_items']++;
                }
                
                // Count categories
                foreach ($product->categories as $category) {
                    $categoryCount[$category->name] = ($categoryCount[$category->name] ?? 0) + 1;
                }
            }
        }

        if (count($prices) > 0) {
            $statistics['average_price'] = array_sum($prices) / count($prices);
        }

        // Sort categories by count
        arsort($categoryCount);
        $statistics['categories'] = array_slice($categoryCount, 0, 5, true);

        return $statistics;
    }

    /**
     * Get recommended products based on wishlist.
     *
     * @param User $user
     * @param int $limit
     * @return Collection
     */
    public function getRecommendedProducts(User $user, int $limit = 8): Collection
    {
        $wishlistProductIds = $this->getUserWishlistProductIds($user);
        
        if (empty($wishlistProductIds)) {
            return collect();
        }

        // Get categories from wishlist products
        $categoryIds = DB::table('product_categories')
            ->whereIn('product_id', $wishlistProductIds)
            ->pluck('category_id')
            ->unique();

        // Get recommended products from same categories
        return Product::where('is_active', true)
            ->whereNotIn('id', $wishlistProductIds)
            ->whereHas('categories', function ($query) use ($categoryIds) {
                $query->whereIn('categories.id', $categoryIds);
            })
            ->with(['categories'])
            ->orderBy('rating', 'desc')
            ->orderBy('sales_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate share token for user.
     *
     * @param User $user
     * @return string
     */
    protected function generateShareToken(User $user): string
    {
        return hash('sha256', $user->id . $user->email . config('app.key'));
    }

    /**
     * Verify share token.
     *
     * @param User $user
     * @param string $token
     * @return bool
     */
    protected function verifyShareToken(User $user, string $token): bool
    {
        return hash_equals($this->generateShareToken($user), $token);
    }

    /**
     * Clear user's wishlist cache.
     *
     * @param int $userId
     * @return void
     */
    protected function clearUserWishlistCache(int $userId): void
    {
        Cache::forget("user_wishlist_{$userId}");
        Cache::forget("user_wishlist_ids_{$userId}");
        Cache::forget("user_wishlist_count_{$userId}");
    }

    /**
     * Clean up inactive products from wishlists.
     *
     * @return int
     */
    public function cleanupInactiveProducts(): int
    {
        $deleted = Wishlist::whereHas('product', function ($query) {
            $query->where('is_active', false);
        })->delete();

        // Clear all wishlist caches
        $userIds = User::pluck('id');
        foreach ($userIds as $userId) {
            $this->clearUserWishlistCache($userId);
        }

        return $deleted;
    }
}