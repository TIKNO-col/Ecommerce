<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\ProductView;
use App\Models\SearchLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class AnalyticsService
{
    /**
     * Get dashboard overview statistics.
     *
     * @param string $period
     * @return array
     */
    public function getDashboardOverview(string $period = '30_days'): array
    {
        $cacheKey = "dashboard_overview_{$period}";
        
        return Cache::remember($cacheKey, 1800, function () use ($period) {
            $dateRange = $this->getDateRange($period);
            $previousDateRange = $this->getPreviousDateRange($period);
            
            // Current period stats
            $currentStats = $this->getPeriodStats($dateRange['start'], $dateRange['end']);
            
            // Previous period stats for comparison
            $previousStats = $this->getPeriodStats($previousDateRange['start'], $previousDateRange['end']);
            
            // Calculate percentage changes
            $changes = $this->calculatePercentageChanges($currentStats, $previousStats);
            
            return [
                'current' => $currentStats,
                'previous' => $previousStats,
                'changes' => $changes,
                'period' => $period,
                'date_range' => $dateRange,
            ];
        });
    }

    /**
     * Get sales analytics.
     *
     * @param string $period
     * @param string $groupBy
     * @return array
     */
    public function getSalesAnalytics(string $period = '30_days', string $groupBy = 'day'): array
    {
        $cacheKey = "sales_analytics_{$period}_{$groupBy}";
        
        return Cache::remember($cacheKey, 1800, function () use ($period, $groupBy) {
            $dateRange = $this->getDateRange($period);
            
            // Sales over time
            $salesOverTime = $this->getSalesOverTime($dateRange['start'], $dateRange['end'], $groupBy);
            
            // Top selling products
            $topProducts = $this->getTopSellingProducts($dateRange['start'], $dateRange['end'], 10);
            
            // Sales by category
            $salesByCategory = $this->getSalesByCategory($dateRange['start'], $dateRange['end']);
            
            // Revenue metrics
            $revenueMetrics = $this->getRevenueMetrics($dateRange['start'], $dateRange['end']);
            
            // Order status distribution
            $orderStatusDistribution = $this->getOrderStatusDistribution($dateRange['start'], $dateRange['end']);
            
            return [
                'sales_over_time' => $salesOverTime,
                'top_products' => $topProducts,
                'sales_by_category' => $salesByCategory,
                'revenue_metrics' => $revenueMetrics,
                'order_status_distribution' => $orderStatusDistribution,
                'period' => $period,
                'group_by' => $groupBy,
            ];
        });
    }

    /**
     * Get customer analytics.
     *
     * @param string $period
     * @return array
     */
    public function getCustomerAnalytics(string $period = '30_days'): array
    {
        $cacheKey = "customer_analytics_{$period}";
        
        return Cache::remember($cacheKey, 1800, function () use ($period) {
            $dateRange = $this->getDateRange($period);
            
            // New customers over time
            $newCustomersOverTime = $this->getNewCustomersOverTime($dateRange['start'], $dateRange['end']);
            
            // Customer lifetime value
            $customerLifetimeValue = $this->getCustomerLifetimeValue();
            
            // Customer segments
            $customerSegments = $this->getCustomerSegments();
            
            // Repeat customer rate
            $repeatCustomerRate = $this->getRepeatCustomerRate($dateRange['start'], $dateRange['end']);
            
            // Top customers
            $topCustomers = $this->getTopCustomers($dateRange['start'], $dateRange['end'], 10);
            
            // Customer geography
            $customerGeography = $this->getCustomerGeography($dateRange['start'], $dateRange['end']);
            
            return [
                'new_customers_over_time' => $newCustomersOverTime,
                'customer_lifetime_value' => $customerLifetimeValue,
                'customer_segments' => $customerSegments,
                'repeat_customer_rate' => $repeatCustomerRate,
                'top_customers' => $topCustomers,
                'customer_geography' => $customerGeography,
                'period' => $period,
            ];
        });
    }

    /**
     * Get product analytics.
     *
     * @param string $period
     * @return array
     */
    public function getProductAnalytics(string $period = '30_days'): array
    {
        $cacheKey = "product_analytics_{$period}";
        
        return Cache::remember($cacheKey, 1800, function () use ($period) {
            $dateRange = $this->getDateRange($period);
            
            // Product performance
            $productPerformance = $this->getProductPerformance($dateRange['start'], $dateRange['end']);
            
            // Category performance
            $categoryPerformance = $this->getCategoryPerformance($dateRange['start'], $dateRange['end']);
            
            // Product views vs sales
            $viewsVsSales = $this->getProductViewsVsSales($dateRange['start'], $dateRange['end']);
            
            // Inventory turnover
            $inventoryTurnover = $this->getInventoryTurnover($dateRange['start'], $dateRange['end']);
            
            // Low performing products
            $lowPerformingProducts = $this->getLowPerformingProducts($dateRange['start'], $dateRange['end']);
            
            return [
                'product_performance' => $productPerformance,
                'category_performance' => $categoryPerformance,
                'views_vs_sales' => $viewsVsSales,
                'inventory_turnover' => $inventoryTurnover,
                'low_performing_products' => $lowPerformingProducts,
                'period' => $period,
            ];
        });
    }

    /**
     * Get conversion analytics.
     *
     * @param string $period
     * @return array
     */
    public function getConversionAnalytics(string $period = '30_days'): array
    {
        $cacheKey = "conversion_analytics_{$period}";
        
        return Cache::remember($cacheKey, 1800, function () use ($period) {
            $dateRange = $this->getDateRange($period);
            
            // Conversion funnel
            $conversionFunnel = $this->getConversionFunnel($dateRange['start'], $dateRange['end']);
            
            // Cart abandonment
            $cartAbandonment = $this->getCartAbandonmentAnalytics($dateRange['start'], $dateRange['end']);
            
            // Search analytics
            $searchAnalytics = $this->getSearchAnalytics($dateRange['start'], $dateRange['end']);
            
            // Traffic sources
            $trafficSources = $this->getTrafficSources($dateRange['start'], $dateRange['end']);
            
            return [
                'conversion_funnel' => $conversionFunnel,
                'cart_abandonment' => $cartAbandonment,
                'search_analytics' => $searchAnalytics,
                'traffic_sources' => $trafficSources,
                'period' => $period,
            ];
        });
    }

    /**
     * Generate comprehensive report.
     *
     * @param string $period
     * @param array $sections
     * @return array
     */
    public function generateComprehensiveReport(string $period = '30_days', array $sections = []): array
    {
        $defaultSections = ['overview', 'sales', 'customers', 'products', 'conversion'];
        $sections = empty($sections) ? $defaultSections : $sections;
        
        $report = [
            'generated_at' => now()->toISOString(),
            'period' => $period,
            'sections' => [],
        ];
        
        if (in_array('overview', $sections)) {
            $report['sections']['overview'] = $this->getDashboardOverview($period);
        }
        
        if (in_array('sales', $sections)) {
            $report['sections']['sales'] = $this->getSalesAnalytics($period);
        }
        
        if (in_array('customers', $sections)) {
            $report['sections']['customers'] = $this->getCustomerAnalytics($period);
        }
        
        if (in_array('products', $sections)) {
            $report['sections']['products'] = $this->getProductAnalytics($period);
        }
        
        if (in_array('conversion', $sections)) {
            $report['sections']['conversion'] = $this->getConversionAnalytics($period);
        }
        
        return $report;
    }

    /**
     * Get period statistics.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getPeriodStats(Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate]);
        $completedOrders = clone $orders;
        $completedOrders = $completedOrders->whereIn('status', ['completed', 'delivered']);
        
        $totalRevenue = $completedOrders->sum('total_amount');
        $totalOrders = $orders->count();
        $completedOrdersCount = $completedOrders->count();
        
        $newCustomers = User::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $averageOrderValue = $completedOrdersCount > 0 ? $totalRevenue / $completedOrdersCount : 0;
        
        $totalProducts = Product::count();
        $activeProducts = Product::where('is_active', true)->count();
        
        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'completed_orders' => $completedOrdersCount,
            'new_customers' => $newCustomers,
            'average_order_value' => $averageOrderValue,
            'total_products' => $totalProducts,
            'active_products' => $activeProducts,
            'conversion_rate' => $this->calculateConversionRate($startDate, $endDate),
        ];
    }

    /**
     * Get sales over time.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string $groupBy
     * @return array
     */
    protected function getSalesOverTime(Carbon $startDate, Carbon $endDate, string $groupBy): array
    {
        $dateFormat = $this->getDateFormat($groupBy);
        
        $sales = Order::selectRaw("DATE_FORMAT(created_at, '{$dateFormat}') as period")
            ->selectRaw('COUNT(*) as orders')
            ->selectRaw('SUM(total_amount) as revenue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'delivered'])
            ->groupBy('period')
            ->orderBy('period')
            ->get();
        
        return $sales->toArray();
    }

    /**
     * Get top selling products.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $limit
     * @return Collection
     */
    protected function getTopSellingProducts(Carbon $startDate, Carbon $endDate, int $limit): Collection
    {
        return OrderItem::select('product_id')
            ->selectRaw('SUM(quantity) as total_quantity')
            ->selectRaw('SUM(price * quantity) as total_revenue')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', ['completed', 'delivered']);
            })
            ->with('product')
            ->groupBy('product_id')
            ->orderBy('total_quantity', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get sales by category.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    protected function getSalesByCategory(Carbon $startDate, Carbon $endDate): Collection
    {
        return OrderItem::select('products.category_id')
            ->selectRaw('SUM(order_items.quantity) as total_quantity')
            ->selectRaw('SUM(order_items.price * order_items.quantity) as total_revenue')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->whereHas('order', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate])
                    ->whereIn('status', ['completed', 'delivered']);
            })
            ->with('product.category')
            ->groupBy('products.category_id')
            ->orderBy('total_revenue', 'desc')
            ->get();
    }

    /**
     * Get revenue metrics.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getRevenueMetrics(Carbon $startDate, Carbon $endDate): array
    {
        $orders = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('status', ['completed', 'delivered']);
        
        $totalRevenue = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        $averageOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        $shippingRevenue = $orders->sum('shipping_cost');
        $taxRevenue = $orders->sum('tax_amount');
        $discountAmount = $orders->sum('discount_amount');
        
        return [
            'total_revenue' => $totalRevenue,
            'shipping_revenue' => $shippingRevenue,
            'tax_revenue' => $taxRevenue,
            'discount_amount' => $discountAmount,
            'net_revenue' => $totalRevenue - $discountAmount,
            'average_order_value' => $averageOrderValue,
            'total_orders' => $totalOrders,
        ];
    }

    /**
     * Get order status distribution.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    protected function getOrderStatusDistribution(Carbon $startDate, Carbon $endDate): Collection
    {
        return Order::select('status')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('SUM(total_amount) as total_amount')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('status')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * Get new customers over time.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    protected function getNewCustomersOverTime(Carbon $startDate, Carbon $endDate): Collection
    {
        return User::selectRaw('DATE(created_at) as date')
            ->selectRaw('COUNT(*) as new_customers')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get customer lifetime value.
     *
     * @return array
     */
    protected function getCustomerLifetimeValue(): array
    {
        $customerValues = User::select('users.id')
            ->selectRaw('COALESCE(SUM(orders.total_amount), 0) as lifetime_value')
            ->selectRaw('COUNT(orders.id) as order_count')
            ->leftJoin('orders', 'users.id', '=', 'orders.user_id')
            ->where('orders.status', 'completed')
            ->groupBy('users.id')
            ->get();
        
        $averageLifetimeValue = $customerValues->avg('lifetime_value');
        $totalCustomers = $customerValues->count();
        
        return [
            'average_lifetime_value' => $averageLifetimeValue,
            'total_customers_with_orders' => $totalCustomers,
            'distribution' => [
                'low' => $customerValues->where('lifetime_value', '<', 100)->count(),
                'medium' => $customerValues->whereBetween('lifetime_value', [100, 500])->count(),
                'high' => $customerValues->where('lifetime_value', '>', 500)->count(),
            ],
        ];
    }

    /**
     * Get customer segments.
     *
     * @return array
     */
    protected function getCustomerSegments(): array
    {
        $segments = [
            'new' => User::whereDoesntHave('orders')->count(),
            'active' => User::whereHas('orders', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })->count(),
            'inactive' => User::whereHas('orders', function ($query) {
                $query->where('created_at', '<', now()->subDays(30));
            })->whereDoesntHave('orders', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })->count(),
            'vip' => User::whereHas('orders', function ($query) {
                $query->selectRaw('SUM(total_amount)')
                    ->groupBy('user_id')
                    ->havingRaw('SUM(total_amount) > 1000');
            })->count(),
        ];
        
        return $segments;
    }

    /**
     * Get repeat customer rate.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function getRepeatCustomerRate(Carbon $startDate, Carbon $endDate): float
    {
        $totalCustomers = Order::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count('user_id');
        
        $repeatCustomers = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();
        
        return $totalCustomers > 0 ? ($repeatCustomers / $totalCustomers) * 100 : 0;
    }

    /**
     * Get top customers.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param int $limit
     * @return Collection
     */
    protected function getTopCustomers(Carbon $startDate, Carbon $endDate, int $limit): Collection
    {
        return User::select('users.*')
            ->selectRaw('SUM(orders.total_amount) as total_spent')
            ->selectRaw('COUNT(orders.id) as order_count')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where('orders.status', 'completed')
            ->groupBy('users.id')
            ->orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get customer geography.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    protected function getCustomerGeography(Carbon $startDate, Carbon $endDate): Collection
    {
        return Order::select('shipping_country')
            ->selectRaw('COUNT(*) as order_count')
            ->selectRaw('SUM(total_amount) as total_revenue')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('shipping_country')
            ->groupBy('shipping_country')
            ->orderBy('order_count', 'desc')
            ->get();
    }

    /**
     * Calculate conversion rate.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return float
     */
    protected function calculateConversionRate(Carbon $startDate, Carbon $endDate): float
    {
        // This is a simplified calculation
        // In a real implementation, you'd track page views, sessions, etc.
        $totalOrders = Order::whereBetween('created_at', [$startDate, $endDate])->count();
        $totalViews = ProductView::whereBetween('created_at', [$startDate, $endDate])->count();
        
        return $totalViews > 0 ? ($totalOrders / $totalViews) * 100 : 0;
    }

    /**
     * Get date range for period.
     *
     * @param string $period
     * @return array
     */
    protected function getDateRange(string $period): array
    {
        $end = now();
        
        switch ($period) {
            case '7_days':
                $start = $end->copy()->subDays(7);
                break;
            case '30_days':
                $start = $end->copy()->subDays(30);
                break;
            case '90_days':
                $start = $end->copy()->subDays(90);
                break;
            case 'this_month':
                $start = $end->copy()->startOfMonth();
                break;
            case 'last_month':
                $start = $end->copy()->subMonth()->startOfMonth();
                $end = $end->copy()->subMonth()->endOfMonth();
                break;
            case 'this_year':
                $start = $end->copy()->startOfYear();
                break;
            default:
                $start = $end->copy()->subDays(30);
        }
        
        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Get previous date range for comparison.
     *
     * @param string $period
     * @return array
     */
    protected function getPreviousDateRange(string $period): array
    {
        $current = $this->getDateRange($period);
        $duration = $current['end']->diffInDays($current['start']);
        
        return [
            'start' => $current['start']->copy()->subDays($duration),
            'end' => $current['start']->copy()->subDay(),
        ];
    }

    /**
     * Calculate percentage changes.
     *
     * @param array $current
     * @param array $previous
     * @return array
     */
    protected function calculatePercentageChanges(array $current, array $previous): array
    {
        $changes = [];
        
        foreach ($current as $key => $value) {
            if (isset($previous[$key]) && is_numeric($value) && is_numeric($previous[$key])) {
                if ($previous[$key] > 0) {
                    $changes[$key] = (($value - $previous[$key]) / $previous[$key]) * 100;
                } else {
                    $changes[$key] = $value > 0 ? 100 : 0;
                }
            } else {
                $changes[$key] = 0;
            }
        }
        
        return $changes;
    }

    /**
     * Get date format for grouping.
     *
     * @param string $groupBy
     * @return string
     */
    protected function getDateFormat(string $groupBy): string
    {
        switch ($groupBy) {
            case 'hour':
                return '%Y-%m-%d %H:00:00';
            case 'day':
                return '%Y-%m-%d';
            case 'week':
                return '%Y-%u';
            case 'month':
                return '%Y-%m';
            case 'year':
                return '%Y';
            default:
                return '%Y-%m-%d';
        }
    }

    /**
     * Clear analytics cache.
     *
     * @return void
     */
    public function clearAnalyticsCache(): void
    {
        $cacheKeys = [
            'dashboard_overview_*',
            'sales_analytics_*',
            'customer_analytics_*',
            'product_analytics_*',
            'conversion_analytics_*',
        ];
        
        foreach ($cacheKeys as $pattern) {
            $keys = Cache::getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }
    }

    /**
     * Get product performance.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getProductPerformance(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for product performance metrics
        return [];
    }

    /**
     * Get category performance.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getCategoryPerformance(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for category performance metrics
        return [];
    }

    /**
     * Get product views vs sales.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getProductViewsVsSales(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for views vs sales comparison
        return [];
    }

    /**
     * Get inventory turnover.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getInventoryTurnover(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for inventory turnover metrics
        return [];
    }

    /**
     * Get low performing products.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return Collection
     */
    protected function getLowPerformingProducts(Carbon $startDate, Carbon $endDate): Collection
    {
        // Implementation for low performing products
        return collect([]);
    }

    /**
     * Get conversion funnel.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getConversionFunnel(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for conversion funnel
        return [];
    }

    /**
     * Get cart abandonment analytics.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getCartAbandonmentAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for cart abandonment analytics
        return [];
    }

    /**
     * Get search analytics.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getSearchAnalytics(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for search analytics
        return [];
    }

    /**
     * Get traffic sources.
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    protected function getTrafficSources(Carbon $startDate, Carbon $endDate): array
    {
        // Implementation for traffic sources
        return [];
    }
}