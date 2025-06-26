<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;

class CouponService
{
    /**
     * Validate and apply coupon to cart.
     *
     * @param string $code
     * @param array $cartItems
     * @param User|null $user
     * @return array
     */
    public function validateAndApplyCoupon(string $code, array $cartItems, ?User $user = null): array
    {
        try {
            // Find coupon by code
            $coupon = Coupon::where('code', strtoupper($code))
                ->where('is_active', true)
                ->first();
            
            if (!$coupon) {
                return [
                    'valid' => false,
                    'message' => 'Invalid coupon code.',
                ];
            }
            
            // Validate coupon
            $validation = $this->validateCoupon($coupon, $cartItems, $user);
            
            if (!$validation['valid']) {
                return $validation;
            }
            
            // Calculate discount
            $discount = $this->calculateDiscount($coupon, $cartItems);
            
            return [
                'valid' => true,
                'coupon' => $coupon,
                'discount' => $discount,
                'message' => "Coupon '{$coupon->code}' applied successfully!",
            ];
            
        } catch (Exception $e) {
            Log::error('Coupon validation failed', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'valid' => false,
                'message' => 'An error occurred while validating the coupon.',
            ];
        }
    }

    /**
     * Validate coupon conditions.
     *
     * @param Coupon $coupon
     * @param array $cartItems
     * @param User|null $user
     * @return array
     */
    public function validateCoupon(Coupon $coupon, array $cartItems, ?User $user = null): array
    {
        // Check if coupon is active
        if (!$coupon->is_active) {
            return [
                'valid' => false,
                'message' => 'This coupon is no longer active.',
            ];
        }
        
        // Check date validity
        $now = now();
        
        if ($coupon->starts_at && $now->lt($coupon->starts_at)) {
            return [
                'valid' => false,
                'message' => 'This coupon is not yet valid.',
            ];
        }
        
        if ($coupon->expires_at && $now->gt($coupon->expires_at)) {
            return [
                'valid' => false,
                'message' => 'This coupon has expired.',
            ];
        }
        
        // Check usage limits
        if ($coupon->usage_limit && $coupon->usage_count >= $coupon->usage_limit) {
            return [
                'valid' => false,
                'message' => 'This coupon has reached its usage limit.',
            ];
        }
        
        // Check user-specific usage limit
        if ($user && $coupon->usage_limit_per_user) {
            $userUsageCount = CouponUsage::where('coupon_id', $coupon->id)
                ->where('user_id', $user->id)
                ->count();
            
            if ($userUsageCount >= $coupon->usage_limit_per_user) {
                return [
                    'valid' => false,
                    'message' => 'You have already used this coupon the maximum number of times.',
                ];
            }
        }
        
        // Check minimum order amount
        $cartTotal = $this->calculateCartTotal($cartItems);
        
        if ($coupon->minimum_amount && $cartTotal < $coupon->minimum_amount) {
            return [
                'valid' => false,
                'message' => "Minimum order amount of {$coupon->minimum_amount} required.",
            ];
        }
        
        // Check maximum order amount
        if ($coupon->maximum_amount && $cartTotal > $coupon->maximum_amount) {
            return [
                'valid' => false,
                'message' => "Maximum order amount of {$coupon->maximum_amount} exceeded.",
            ];
        }
        
        // Check user eligibility
        if (!$this->isUserEligible($coupon, $user)) {
            return [
                'valid' => false,
                'message' => 'You are not eligible for this coupon.',
            ];
        }
        
        // Check product/category restrictions
        if (!$this->areProductsEligible($coupon, $cartItems)) {
            return [
                'valid' => false,
                'message' => 'This coupon is not applicable to the items in your cart.',
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'Coupon is valid.',
        ];
    }

    /**
     * Calculate discount amount.
     *
     * @param Coupon $coupon
     * @param array $cartItems
     * @return array
     */
    public function calculateDiscount(Coupon $coupon, array $cartItems): array
    {
        $eligibleItems = $this->getEligibleItems($coupon, $cartItems);
        $eligibleTotal = $this->calculateItemsTotal($eligibleItems);
        
        $discountAmount = 0;
        
        switch ($coupon->type) {
            case 'percentage':
                $discountAmount = ($eligibleTotal * $coupon->value) / 100;
                break;
                
            case 'fixed':
                $discountAmount = min($coupon->value, $eligibleTotal);
                break;
                
            case 'free_shipping':
                // This would be handled in shipping calculation
                $discountAmount = 0;
                break;
                
            case 'buy_x_get_y':
                $discountAmount = $this->calculateBuyXGetYDiscount($coupon, $eligibleItems);
                break;
        }
        
        // Apply maximum discount limit
        if ($coupon->maximum_discount_amount) {
            $discountAmount = min($discountAmount, $coupon->maximum_discount_amount);
        }
        
        return [
            'amount' => round($discountAmount, 2),
            'type' => $coupon->type,
            'eligible_total' => $eligibleTotal,
            'eligible_items' => $eligibleItems,
        ];
    }

    /**
     * Apply coupon to order.
     *
     * @param Coupon $coupon
     * @param Order $order
     * @param User|null $user
     * @return bool
     */
    public function applyCouponToOrder(Coupon $coupon, Order $order, ?User $user = null): bool
    {
        try {
            DB::beginTransaction();
            
            // Record coupon usage
            CouponUsage::create([
                'coupon_id' => $coupon->id,
                'user_id' => $user?->id,
                'order_id' => $order->id,
                'discount_amount' => $order->discount_amount,
            ]);
            
            // Increment coupon usage count
            $coupon->increment('usage_count');
            
            DB::commit();
            
            Log::info('Coupon applied to order', [
                'coupon_id' => $coupon->id,
                'order_id' => $order->id,
                'discount_amount' => $order->discount_amount,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to apply coupon to order', [
                'coupon_id' => $coupon->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Generate unique coupon code.
     *
     * @param int $length
     * @return string
     */
    public function generateCouponCode(int $length = 8): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        do {
            $code = '';
            for ($i = 0; $i < $length; $i++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
        } while (Coupon::where('code', $code)->exists());
        
        return $code;
    }

    /**
     * Create bulk coupons.
     *
     * @param array $couponData
     * @param int $quantity
     * @return Collection
     */
    public function createBulkCoupons(array $couponData, int $quantity): Collection
    {
        $coupons = collect();
        
        for ($i = 0; $i < $quantity; $i++) {
            $couponData['code'] = $this->generateCouponCode();
            $coupon = Coupon::create($couponData);
            $coupons->push($coupon);
        }
        
        return $coupons;
    }

    /**
     * Get user's available coupons.
     *
     * @param User $user
     * @return Collection
     */
    public function getUserAvailableCoupons(User $user): Collection
    {
        $now = now();
        
        return Coupon::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', $now);
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereRaw('usage_count < usage_limit');
            })
            ->where(function ($query) use ($user) {
                // Check if user hasn't exceeded per-user limit
                $query->whereNull('usage_limit_per_user')
                    ->orWhereNotExists(function ($subQuery) use ($user) {
                        $subQuery->select(DB::raw(1))
                            ->from('coupon_usages')
                            ->whereRaw('coupon_usages.coupon_id = coupons.id')
                            ->where('user_id', $user->id)
                            ->havingRaw('COUNT(*) >= coupons.usage_limit_per_user');
                    });
            })
            ->where(function ($query) use ($user) {
                // Check user restrictions
                $query->whereNull('user_restrictions')
                    ->orWhere('user_restrictions', 'like', '%"' . $user->id . '"%')
                    ->orWhere('user_restrictions', 'like', '%"' . $user->email . '"%');
            })
            ->orderBy('expires_at', 'asc')
            ->get();
    }

    /**
     * Get coupon statistics.
     *
     * @param Coupon|null $coupon
     * @return array
     */
    public function getCouponStatistics(?Coupon $coupon = null): array
    {
        if ($coupon) {
            // Statistics for specific coupon
            $usages = CouponUsage::where('coupon_id', $coupon->id)->get();
            
            return [
                'total_usage' => $usages->count(),
                'total_discount' => $usages->sum('discount_amount'),
                'unique_users' => $usages->unique('user_id')->count(),
                'usage_rate' => $coupon->usage_limit ? ($usages->count() / $coupon->usage_limit) * 100 : 0,
                'average_discount' => $usages->avg('discount_amount'),
                'recent_usages' => $usages->sortByDesc('created_at')->take(10),
            ];
        }
        
        // General coupon statistics
        $cacheKey = 'coupon_statistics';
        
        return Cache::remember($cacheKey, 1800, function () {
            $totalCoupons = Coupon::count();
            $activeCoupons = Coupon::where('is_active', true)->count();
            $expiredCoupons = Coupon::where('expires_at', '<', now())->count();
            $usedCoupons = Coupon::where('usage_count', '>', 0)->count();
            
            $totalUsages = CouponUsage::count();
            $totalDiscount = CouponUsage::sum('discount_amount');
            
            $usagesToday = CouponUsage::whereDate('created_at', today())->count();
            $discountToday = CouponUsage::whereDate('created_at', today())->sum('discount_amount');
            
            $usagesThisMonth = CouponUsage::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            $discountThisMonth = CouponUsage::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('discount_amount');
            
            return [
                'total_coupons' => $totalCoupons,
                'active_coupons' => $activeCoupons,
                'expired_coupons' => $expiredCoupons,
                'used_coupons' => $usedCoupons,
                'total_usages' => $totalUsages,
                'total_discount' => $totalDiscount,
                'usages_today' => $usagesToday,
                'discount_today' => $discountToday,
                'usages_this_month' => $usagesThisMonth,
                'discount_this_month' => $discountThisMonth,
                'average_discount_per_usage' => $totalUsages > 0 ? $totalDiscount / $totalUsages : 0,
            ];
        });
    }

    /**
     * Check if user is eligible for coupon.
     *
     * @param Coupon $coupon
     * @param User|null $user
     * @return bool
     */
    protected function isUserEligible(Coupon $coupon, ?User $user = null): bool
    {
        // If no user restrictions, everyone is eligible
        if (!$coupon->user_restrictions) {
            return true;
        }
        
        // If no user provided but restrictions exist, not eligible
        if (!$user) {
            return false;
        }
        
        $restrictions = json_decode($coupon->user_restrictions, true);
        
        // Check user IDs
        if (isset($restrictions['user_ids']) && in_array($user->id, $restrictions['user_ids'])) {
            return true;
        }
        
        // Check email addresses
        if (isset($restrictions['emails']) && in_array($user->email, $restrictions['emails'])) {
            return true;
        }
        
        // Check user groups/roles
        if (isset($restrictions['user_groups'])) {
            foreach ($restrictions['user_groups'] as $group) {
                if ($user->hasRole($group)) {
                    return true;
                }
            }
        }
        
        // Check new customers
        if (isset($restrictions['new_customers']) && $restrictions['new_customers']) {
            $orderCount = Order::where('user_id', $user->id)->count();
            if ($orderCount === 0) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if products are eligible for coupon.
     *
     * @param Coupon $coupon
     * @param array $cartItems
     * @return bool
     */
    protected function areProductsEligible(Coupon $coupon, array $cartItems): bool
    {
        // If no product restrictions, all products are eligible
        if (!$coupon->product_restrictions) {
            return true;
        }
        
        $restrictions = json_decode($coupon->product_restrictions, true);
        $eligibleItems = $this->getEligibleItems($coupon, $cartItems);
        
        return count($eligibleItems) > 0;
    }

    /**
     * Get eligible items for coupon.
     *
     * @param Coupon $coupon
     * @param array $cartItems
     * @return array
     */
    protected function getEligibleItems(Coupon $coupon, array $cartItems): array
    {
        // If no restrictions, all items are eligible
        if (!$coupon->product_restrictions) {
            return $cartItems;
        }
        
        $restrictions = json_decode($coupon->product_restrictions, true);
        $eligibleItems = [];
        
        foreach ($cartItems as $item) {
            $product = Product::find($item['product_id']);
            
            if (!$product) {
                continue;
            }
            
            $isEligible = false;
            
            // Check specific products
            if (isset($restrictions['product_ids']) && in_array($product->id, $restrictions['product_ids'])) {
                $isEligible = true;
            }
            
            // Check categories
            if (isset($restrictions['category_ids']) && in_array($product->category_id, $restrictions['category_ids'])) {
                $isEligible = true;
            }
            
            // Check brands
            if (isset($restrictions['brand_ids']) && in_array($product->brand_id, $restrictions['brand_ids'])) {
                $isEligible = true;
            }
            
            // Check excluded products
            if (isset($restrictions['excluded_product_ids']) && in_array($product->id, $restrictions['excluded_product_ids'])) {
                $isEligible = false;
            }
            
            // Check excluded categories
            if (isset($restrictions['excluded_category_ids']) && in_array($product->category_id, $restrictions['excluded_category_ids'])) {
                $isEligible = false;
            }
            
            if ($isEligible) {
                $eligibleItems[] = $item;
            }
        }
        
        return $eligibleItems;
    }

    /**
     * Calculate cart total.
     *
     * @param array $cartItems
     * @return float
     */
    protected function calculateCartTotal(array $cartItems): float
    {
        return $this->calculateItemsTotal($cartItems);
    }

    /**
     * Calculate items total.
     *
     * @param array $items
     * @return float
     */
    protected function calculateItemsTotal(array $items): float
    {
        $total = 0;
        
        foreach ($items as $item) {
            $total += $item['price'] * $item['quantity'];
        }
        
        return $total;
    }

    /**
     * Calculate Buy X Get Y discount.
     *
     * @param Coupon $coupon
     * @param array $eligibleItems
     * @return float
     */
    protected function calculateBuyXGetYDiscount(Coupon $coupon, array $eligibleItems): float
    {
        $buyXGetYData = json_decode($coupon->buy_x_get_y_data, true);
        
        if (!$buyXGetYData) {
            return 0;
        }
        
        $buyQuantity = $buyXGetYData['buy_quantity'] ?? 1;
        $getQuantity = $buyXGetYData['get_quantity'] ?? 1;
        $discountPercentage = $buyXGetYData['discount_percentage'] ?? 100;
        
        $totalQuantity = array_sum(array_column($eligibleItems, 'quantity'));
        $freeItems = intval($totalQuantity / $buyQuantity) * $getQuantity;
        
        // Sort items by price (ascending) to discount cheapest items
        usort($eligibleItems, function ($a, $b) {
            return $a['price'] <=> $b['price'];
        });
        
        $discountAmount = 0;
        $remainingFreeItems = $freeItems;
        
        foreach ($eligibleItems as $item) {
            if ($remainingFreeItems <= 0) {
                break;
            }
            
            $itemsToDiscount = min($remainingFreeItems, $item['quantity']);
            $discountAmount += ($item['price'] * $itemsToDiscount * $discountPercentage) / 100;
            $remainingFreeItems -= $itemsToDiscount;
        }
        
        return $discountAmount;
    }

    /**
     * Expire old coupons.
     *
     * @return int
     */
    public function expireOldCoupons(): int
    {
        $expiredCount = Coupon::where('expires_at', '<', now())
            ->where('is_active', true)
            ->update(['is_active' => false]);
        
        Log::info('Expired old coupons', ['count' => $expiredCount]);
        
        return $expiredCount;
    }

    /**
     * Clear coupon cache.
     *
     * @return void
     */
    public function clearCouponCache(): void
    {
        Cache::forget('coupon_statistics');
    }
}