<?php

namespace App\Services;

use App\Models\ShippingMethod;
use App\Models\ShippingZone;
use App\Models\ShippingRate;
use App\Models\Order;
use App\Models\Product;
use App\Models\Address;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Exception;

class ShippingService
{
    /**
     * Calculate shipping costs for cart items.
     *
     * @param array $cartItems
     * @param Address $shippingAddress
     * @param string|null $couponCode
     * @return array
     */
    public function calculateShippingCosts(array $cartItems, Address $shippingAddress, ?string $couponCode = null): array
    {
        try {
            // Get shipping zone for address
            $shippingZone = $this->getShippingZoneForAddress($shippingAddress);
            
            if (!$shippingZone) {
                return [
                    'success' => false,
                    'message' => 'Shipping not available to this location.',
                    'methods' => [],
                ];
            }
            
            // Calculate cart totals
            $cartTotals = $this->calculateCartTotals($cartItems);
            
            // Get available shipping methods for zone
            $shippingMethods = $this->getAvailableShippingMethods($shippingZone, $cartTotals);
            
            // Calculate costs for each method
            $methodsWithCosts = [];
            
            foreach ($shippingMethods as $method) {
                $cost = $this->calculateMethodCost($method, $cartTotals, $shippingZone);
                
                // Apply free shipping coupon if applicable
                if ($couponCode && $this->isFreeShippingCoupon($couponCode)) {
                    $cost = 0;
                }
                
                $methodsWithCosts[] = [
                    'id' => $method->id,
                    'name' => $method->name,
                    'description' => $method->description,
                    'cost' => $cost,
                    'estimated_delivery_days' => $method->estimated_delivery_days,
                    'tracking_available' => $method->tracking_available,
                ];
            }
            
            // Sort by cost (cheapest first)
            usort($methodsWithCosts, function ($a, $b) {
                return $a['cost'] <=> $b['cost'];
            });
            
            return [
                'success' => true,
                'methods' => $methodsWithCosts,
                'zone' => $shippingZone->name,
                'cart_totals' => $cartTotals,
            ];
            
        } catch (Exception $e) {
            Log::error('Shipping calculation failed', [
                'address' => $shippingAddress->toArray(),
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Unable to calculate shipping costs.',
                'methods' => [],
            ];
        }
    }

    /**
     * Get shipping zone for address.
     *
     * @param Address $address
     * @return ShippingZone|null
     */
    public function getShippingZoneForAddress(Address $address): ?ShippingZone
    {
        $cacheKey = "shipping_zone_{$address->country}_{$address->state}_{$address->postal_code}";
        
        return Cache::remember($cacheKey, 3600, function () use ($address) {
            // Try to find by postal code first (most specific)
            $zone = ShippingZone::where('is_active', true)
                ->where('countries', 'like', '%"' . $address->country . '"%')
                ->where(function ($query) use ($address) {
                    $query->whereNull('postal_codes')
                        ->orWhere('postal_codes', 'like', '%"' . $address->postal_code . '"%');
                })
                ->where(function ($query) use ($address) {
                    $query->whereNull('states')
                        ->orWhere('states', 'like', '%"' . $address->state . '"%');
                })
                ->orderBy('priority', 'desc')
                ->first();
            
            // If no specific zone found, try country-wide zone
            if (!$zone) {
                $zone = ShippingZone::where('is_active', true)
                    ->where('countries', 'like', '%"' . $address->country . '"%')
                    ->whereNull('states')
                    ->whereNull('postal_codes')
                    ->first();
            }
            
            return $zone;
        });
    }

    /**
     * Get available shipping methods for zone.
     *
     * @param ShippingZone $zone
     * @param array $cartTotals
     * @return Collection
     */
    public function getAvailableShippingMethods(ShippingZone $zone, array $cartTotals): Collection
    {
        return ShippingMethod::where('is_active', true)
            ->whereHas('zones', function ($query) use ($zone) {
                $query->where('shipping_zone_id', $zone->id);
            })
            ->where(function ($query) use ($cartTotals) {
                // Check minimum order value
                $query->whereNull('minimum_order_value')
                    ->orWhere('minimum_order_value', '<=', $cartTotals['subtotal']);
            })
            ->where(function ($query) use ($cartTotals) {
                // Check maximum order value
                $query->whereNull('maximum_order_value')
                    ->orWhere('maximum_order_value', '>=', $cartTotals['subtotal']);
            })
            ->where(function ($query) use ($cartTotals) {
                // Check weight limits
                $query->whereNull('maximum_weight')
                    ->orWhere('maximum_weight', '>=', $cartTotals['weight']);
            })
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Calculate shipping cost for specific method.
     *
     * @param ShippingMethod $method
     * @param array $cartTotals
     * @param ShippingZone $zone
     * @return float
     */
    public function calculateMethodCost(ShippingMethod $method, array $cartTotals, ShippingZone $zone): float
    {
        $cost = 0;
        
        switch ($method->calculation_type) {
            case 'fixed':
                $cost = $method->base_cost;
                break;
                
            case 'percentage':
                $cost = ($cartTotals['subtotal'] * $method->percentage_rate) / 100;
                break;
                
            case 'weight_based':
                $cost = $this->calculateWeightBasedCost($method, $cartTotals['weight'], $zone);
                break;
                
            case 'item_based':
                $cost = $cartTotals['item_count'] * $method->per_item_cost;
                break;
                
            case 'tiered':
                $cost = $this->calculateTieredCost($method, $cartTotals, $zone);
                break;
                
            case 'free':
                $cost = 0;
                break;
        }
        
        // Apply minimum and maximum cost limits
        if ($method->minimum_cost && $cost < $method->minimum_cost) {
            $cost = $method->minimum_cost;
        }
        
        if ($method->maximum_cost && $cost > $method->maximum_cost) {
            $cost = $method->maximum_cost;
        }
        
        return round($cost, 2);
    }

    /**
     * Calculate weight-based shipping cost.
     *
     * @param ShippingMethod $method
     * @param float $weight
     * @param ShippingZone $zone
     * @return float
     */
    protected function calculateWeightBasedCost(ShippingMethod $method, float $weight, ShippingZone $zone): float
    {
        $rates = ShippingRate::where('shipping_method_id', $method->id)
            ->where('shipping_zone_id', $zone->id)
            ->where('weight_from', '<=', $weight)
            ->where(function ($query) use ($weight) {
                $query->whereNull('weight_to')
                    ->orWhere('weight_to', '>=', $weight);
            })
            ->orderBy('weight_from', 'desc')
            ->first();
        
        if ($rates) {
            return $rates->cost;
        }
        
        // Fallback to base cost + weight multiplier
        return $method->base_cost + ($weight * ($method->per_kg_cost ?? 0));
    }

    /**
     * Calculate tiered shipping cost.
     *
     * @param ShippingMethod $method
     * @param array $cartTotals
     * @param ShippingZone $zone
     * @return float
     */
    protected function calculateTieredCost(ShippingMethod $method, array $cartTotals, ShippingZone $zone): float
    {
        $rates = ShippingRate::where('shipping_method_id', $method->id)
            ->where('shipping_zone_id', $zone->id)
            ->where('order_value_from', '<=', $cartTotals['subtotal'])
            ->where(function ($query) use ($cartTotals) {
                $query->whereNull('order_value_to')
                    ->orWhere('order_value_to', '>=', $cartTotals['subtotal']);
            })
            ->orderBy('order_value_from', 'desc')
            ->first();
        
        return $rates ? $rates->cost : $method->base_cost;
    }

    /**
     * Calculate cart totals for shipping.
     *
     * @param array $cartItems
     * @return array
     */
    public function calculateCartTotals(array $cartItems): array
    {
        $subtotal = 0;
        $weight = 0;
        $itemCount = 0;
        $requiresShipping = false;
        
        foreach ($cartItems as $item) {
            $product = Product::find($item['product_id']);
            
            if ($product) {
                $subtotal += $item['price'] * $item['quantity'];
                $weight += ($product->weight ?? 0) * $item['quantity'];
                $itemCount += $item['quantity'];
                
                if ($product->requires_shipping) {
                    $requiresShipping = true;
                }
            }
        }
        
        return [
            'subtotal' => $subtotal,
            'weight' => $weight,
            'item_count' => $itemCount,
            'requires_shipping' => $requiresShipping,
        ];
    }

    /**
     * Track shipment.
     *
     * @param Order $order
     * @return array
     */
    public function trackShipment(Order $order): array
    {
        if (!$order->tracking_number) {
            return [
                'success' => false,
                'message' => 'No tracking number available for this order.',
            ];
        }
        
        $shippingMethod = ShippingMethod::find($order->shipping_method_id);
        
        if (!$shippingMethod || !$shippingMethod->tracking_available) {
            return [
                'success' => false,
                'message' => 'Tracking not available for this shipping method.',
            ];
        }
        
        // Here you would integrate with actual shipping provider APIs
        // For now, return mock tracking data
        return [
            'success' => true,
            'tracking_number' => $order->tracking_number,
            'status' => $this->getTrackingStatus($order),
            'estimated_delivery' => $order->estimated_delivery_date,
            'tracking_url' => $this->generateTrackingUrl($order->tracking_number, $shippingMethod),
            'events' => $this->getTrackingEvents($order),
        ];
    }

    /**
     * Generate shipping label.
     *
     * @param Order $order
     * @return array
     */
    public function generateShippingLabel(Order $order): array
    {
        try {
            $shippingMethod = ShippingMethod::find($order->shipping_method_id);
            
            if (!$shippingMethod) {
                throw new Exception('Shipping method not found.');
            }
            
            // Here you would integrate with shipping provider APIs to generate actual labels
            // For now, return mock data
            $trackingNumber = $this->generateTrackingNumber($shippingMethod);
            
            // Update order with tracking number
            $order->update([
                'tracking_number' => $trackingNumber,
                'shipped_at' => now(),
                'status' => 'shipped',
            ]);
            
            return [
                'success' => true,
                'tracking_number' => $trackingNumber,
                'label_url' => $this->generateLabelUrl($order),
                'estimated_delivery' => $this->calculateEstimatedDelivery($order),
            ];
            
        } catch (Exception $e) {
            Log::error('Shipping label generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to generate shipping label.',
            ];
        }
    }

    /**
     * Get shipping statistics.
     *
     * @return array
     */
    public function getShippingStatistics(): array
    {
        $cacheKey = 'shipping_statistics';
        
        return Cache::remember($cacheKey, 1800, function () {
            $totalOrders = Order::whereNotNull('shipping_method_id')->count();
            $shippedOrders = Order::where('status', 'shipped')->count();
            $deliveredOrders = Order::where('status', 'delivered')->count();
            
            $averageShippingCost = Order::whereNotNull('shipping_cost')
                ->avg('shipping_cost');
            
            $totalShippingRevenue = Order::sum('shipping_cost');
            
            $popularMethods = Order::select('shipping_method_id')
                ->selectRaw('COUNT(*) as usage_count')
                ->whereNotNull('shipping_method_id')
                ->groupBy('shipping_method_id')
                ->orderBy('usage_count', 'desc')
                ->with('shippingMethod')
                ->take(5)
                ->get();
            
            $averageDeliveryTime = Order::whereNotNull('delivered_at')
                ->whereNotNull('shipped_at')
                ->selectRaw('AVG(DATEDIFF(delivered_at, shipped_at)) as avg_days')
                ->value('avg_days');
            
            return [
                'total_orders' => $totalOrders,
                'shipped_orders' => $shippedOrders,
                'delivered_orders' => $deliveredOrders,
                'delivery_rate' => $shippedOrders > 0 ? ($deliveredOrders / $shippedOrders) * 100 : 0,
                'average_shipping_cost' => round($averageShippingCost, 2),
                'total_shipping_revenue' => $totalShippingRevenue,
                'popular_methods' => $popularMethods,
                'average_delivery_time' => round($averageDeliveryTime, 1),
            ];
        });
    }

    /**
     * Validate shipping address.
     *
     * @param Address $address
     * @return array
     */
    public function validateShippingAddress(Address $address): array
    {
        $errors = [];
        
        // Check required fields
        if (empty($address->street_address)) {
            $errors[] = 'Street address is required.';
        }
        
        if (empty($address->city)) {
            $errors[] = 'City is required.';
        }
        
        if (empty($address->country)) {
            $errors[] = 'Country is required.';
        }
        
        if (empty($address->postal_code)) {
            $errors[] = 'Postal code is required.';
        }
        
        // Check if shipping is available to this location
        $shippingZone = $this->getShippingZoneForAddress($address);
        
        if (!$shippingZone) {
            $errors[] = 'Shipping is not available to this location.';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'shipping_zone' => $shippingZone,
        ];
    }

    /**
     * Check if coupon provides free shipping.
     *
     * @param string $couponCode
     * @return bool
     */
    protected function isFreeShippingCoupon(string $couponCode): bool
    {
        // This would integrate with CouponService
        // For now, return false
        return false;
    }

    /**
     * Generate tracking number.
     *
     * @param ShippingMethod $method
     * @return string
     */
    protected function generateTrackingNumber(ShippingMethod $method): string
    {
        $prefix = strtoupper(substr($method->name, 0, 3));
        $timestamp = now()->format('ymd');
        $random = str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefix . $timestamp . $random;
    }

    /**
     * Get tracking status.
     *
     * @param Order $order
     * @return string
     */
    protected function getTrackingStatus(Order $order): string
    {
        switch ($order->status) {
            case 'shipped':
                return 'In Transit';
            case 'delivered':
                return 'Delivered';
            case 'returned':
                return 'Returned';
            default:
                return 'Processing';
        }
    }

    /**
     * Generate tracking URL.
     *
     * @param string $trackingNumber
     * @param ShippingMethod $method
     * @return string
     */
    protected function generateTrackingUrl(string $trackingNumber, ShippingMethod $method): string
    {
        // This would be specific to each shipping provider
        return "https://tracking.example.com/track/{$trackingNumber}";
    }

    /**
     * Get tracking events.
     *
     * @param Order $order
     * @return array
     */
    protected function getTrackingEvents(Order $order): array
    {
        // Mock tracking events - in real implementation, this would come from shipping provider API
        return [
            [
                'date' => $order->created_at->format('Y-m-d H:i:s'),
                'status' => 'Order Placed',
                'location' => 'Warehouse',
                'description' => 'Order has been placed and is being processed.',
            ],
            [
                'date' => $order->shipped_at?->format('Y-m-d H:i:s'),
                'status' => 'Shipped',
                'location' => 'Origin Facility',
                'description' => 'Package has been shipped.',
            ],
        ];
    }

    /**
     * Generate label URL.
     *
     * @param Order $order
     * @return string
     */
    protected function generateLabelUrl(Order $order): string
    {
        return route('admin.orders.shipping-label', $order->id);
    }

    /**
     * Calculate estimated delivery date.
     *
     * @param Order $order
     * @return string|null
     */
    protected function calculateEstimatedDelivery(Order $order): ?string
    {
        $shippingMethod = ShippingMethod::find($order->shipping_method_id);
        
        if (!$shippingMethod || !$shippingMethod->estimated_delivery_days) {
            return null;
        }
        
        return now()->addDays($shippingMethod->estimated_delivery_days)->format('Y-m-d');
    }

    /**
     * Clear shipping cache.
     *
     * @return void
     */
    public function clearShippingCache(): void
    {
        Cache::forget('shipping_statistics');
        
        // Clear zone cache for all addresses
        $cacheKeys = Cache::getRedis()->keys('*shipping_zone_*');
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }
}