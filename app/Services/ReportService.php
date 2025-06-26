<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrdersExport;
use App\Exports\ProductsExport;
use App\Exports\CustomersExport;
use App\Exports\SalesExport;

class ReportService
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Generate sales report.
     *
     * @param array $filters
     * @param string $format
     * @return array
     */
    public function generateSalesReport(array $filters = [], string $format = 'pdf'): array
    {
        try {
            $dateRange = $this->getDateRangeFromFilters($filters);
            
            // Get sales data
            $salesData = $this->getSalesReportData($dateRange, $filters);
            
            // Generate report based on format
            switch ($format) {
                case 'pdf':
                    $filePath = $this->generateSalesReportPDF($salesData, $filters);
                    break;
                case 'excel':
                    $filePath = $this->generateSalesReportExcel($salesData, $filters);
                    break;
                case 'csv':
                    $filePath = $this->generateSalesReportCSV($salesData, $filters);
                    break;
                default:
                    throw new Exception('Unsupported report format.');
            }
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Sales report generation failed', [
                'filters' => $filters,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to generate sales report.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate inventory report.
     *
     * @param array $filters
     * @param string $format
     * @return array
     */
    public function generateInventoryReport(array $filters = [], string $format = 'pdf'): array
    {
        try {
            // Get inventory data
            $inventoryData = $this->getInventoryReportData($filters);
            
            // Generate report based on format
            switch ($format) {
                case 'pdf':
                    $filePath = $this->generateInventoryReportPDF($inventoryData, $filters);
                    break;
                case 'excel':
                    $filePath = $this->generateInventoryReportExcel($inventoryData, $filters);
                    break;
                case 'csv':
                    $filePath = $this->generateInventoryReportCSV($inventoryData, $filters);
                    break;
                default:
                    throw new Exception('Unsupported report format.');
            }
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Inventory report generation failed', [
                'filters' => $filters,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to generate inventory report.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate customer report.
     *
     * @param array $filters
     * @param string $format
     * @return array
     */
    public function generateCustomerReport(array $filters = [], string $format = 'pdf'): array
    {
        try {
            $dateRange = $this->getDateRangeFromFilters($filters);
            
            // Get customer data
            $customerData = $this->getCustomerReportData($dateRange, $filters);
            
            // Generate report based on format
            switch ($format) {
                case 'pdf':
                    $filePath = $this->generateCustomerReportPDF($customerData, $filters);
                    break;
                case 'excel':
                    $filePath = $this->generateCustomerReportExcel($customerData, $filters);
                    break;
                case 'csv':
                    $filePath = $this->generateCustomerReportCSV($customerData, $filters);
                    break;
                default:
                    throw new Exception('Unsupported report format.');
            }
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Customer report generation failed', [
                'filters' => $filters,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to generate customer report.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate financial report.
     *
     * @param array $filters
     * @param string $format
     * @return array
     */
    public function generateFinancialReport(array $filters = [], string $format = 'pdf'): array
    {
        try {
            $dateRange = $this->getDateRangeFromFilters($filters);
            
            // Get financial data
            $financialData = $this->getFinancialReportData($dateRange, $filters);
            
            // Generate report based on format
            switch ($format) {
                case 'pdf':
                    $filePath = $this->generateFinancialReportPDF($financialData, $filters);
                    break;
                case 'excel':
                    $filePath = $this->generateFinancialReportExcel($financialData, $filters);
                    break;
                case 'csv':
                    $filePath = $this->generateFinancialReportCSV($financialData, $filters);
                    break;
                default:
                    throw new Exception('Unsupported report format.');
            }
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Financial report generation failed', [
                'filters' => $filters,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to generate financial report.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate comprehensive analytics report.
     *
     * @param array $filters
     * @param string $format
     * @return array
     */
    public function generateAnalyticsReport(array $filters = [], string $format = 'pdf'): array
    {
        try {
            $period = $filters['period'] ?? '30_days';
            
            // Get analytics data
            $analyticsData = $this->analyticsService->generateComprehensiveReport($period);
            
            // Generate report based on format
            switch ($format) {
                case 'pdf':
                    $filePath = $this->generateAnalyticsReportPDF($analyticsData, $filters);
                    break;
                case 'excel':
                    $filePath = $this->generateAnalyticsReportExcel($analyticsData, $filters);
                    break;
                default:
                    throw new Exception('Analytics report only supports PDF and Excel formats.');
            }
            
            return [
                'success' => true,
                'file_path' => $filePath,
                'download_url' => Storage::url($filePath),
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ];
            
        } catch (Exception $e) {
            Log::error('Analytics report generation failed', [
                'filters' => $filters,
                'format' => $format,
                'error' => $e->getMessage(),
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to generate analytics report.',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get sales report data.
     *
     * @param array $dateRange
     * @param array $filters
     * @return array
     */
    protected function getSalesReportData(array $dateRange, array $filters): array
    {
        $query = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        
        // Apply filters
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        if (isset($filters['customer_id'])) {
            $query->where('user_id', $filters['customer_id']);
        }
        
        if (isset($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }
        
        if (isset($filters['max_amount'])) {
            $query->where('total_amount', '<=', $filters['max_amount']);
        }
        
        $orders = $query->with(['user', 'items.product'])->get();
        
        // Calculate summary statistics
        $summary = [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount'),
            'total_items_sold' => $orders->sum(function ($order) {
                return $order->items->sum('quantity');
            }),
            'total_shipping' => $orders->sum('shipping_cost'),
            'total_tax' => $orders->sum('tax_amount'),
            'total_discounts' => $orders->sum('discount_amount'),
        ];
        
        // Group by status
        $ordersByStatus = $orders->groupBy('status')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
            ];
        });
        
        // Top selling products
        $topProducts = $orders->flatMap->items
            ->groupBy('product_id')
            ->map(function ($items) {
                return [
                    'product' => $items->first()->product,
                    'quantity_sold' => $items->sum('quantity'),
                    'total_revenue' => $items->sum(function ($item) {
                        return $item->price * $item->quantity;
                    }),
                ];
            })
            ->sortByDesc('quantity_sold')
            ->take(10);
        
        return [
            'orders' => $orders,
            'summary' => $summary,
            'orders_by_status' => $ordersByStatus,
            'top_products' => $topProducts,
            'date_range' => $dateRange,
            'filters' => $filters,
        ];
    }

    /**
     * Get inventory report data.
     *
     * @param array $filters
     * @return array
     */
    protected function getInventoryReportData(array $filters): array
    {
        $query = Product::with(['category', 'brand']);
        
        // Apply filters
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }
        
        if (isset($filters['low_stock_only']) && $filters['low_stock_only']) {
            $query->where('stock_quantity', '<=', 10); // Assuming 10 is low stock threshold
        }
        
        if (isset($filters['out_of_stock_only']) && $filters['out_of_stock_only']) {
            $query->where('stock_quantity', '<=', 0);
        }
        
        $products = $query->get();
        
        // Calculate summary statistics
        $summary = [
            'total_products' => $products->count(),
            'total_stock_value' => $products->sum(function ($product) {
                return $product->price * $product->stock_quantity;
            }),
            'low_stock_products' => $products->where('stock_quantity', '<=', 10)->count(),
            'out_of_stock_products' => $products->where('stock_quantity', '<=', 0)->count(),
            'average_stock_per_product' => $products->avg('stock_quantity'),
        ];
        
        // Group by category
        $productsByCategory = $products->groupBy('category.name')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_stock' => $group->sum('stock_quantity'),
                'total_value' => $group->sum(function ($product) {
                    return $product->price * $product->stock_quantity;
                }),
            ];
        });
        
        return [
            'products' => $products,
            'summary' => $summary,
            'products_by_category' => $productsByCategory,
            'filters' => $filters,
        ];
    }

    /**
     * Get customer report data.
     *
     * @param array $dateRange
     * @param array $filters
     * @return array
     */
    protected function getCustomerReportData(array $dateRange, array $filters): array
    {
        $query = User::with(['orders' => function ($query) use ($dateRange) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }]);
        
        // Apply filters
        if (isset($filters['new_customers_only']) && $filters['new_customers_only']) {
            $query->whereBetween('created_at', [$dateRange['start'], $dateRange['end']]);
        }
        
        $customers = $query->get();
        
        // Calculate customer metrics
        $customersWithMetrics = $customers->map(function ($customer) {
            $orders = $customer->orders;
            return [
                'customer' => $customer,
                'total_orders' => $orders->count(),
                'total_spent' => $orders->sum('total_amount'),
                'average_order_value' => $orders->avg('total_amount'),
                'last_order_date' => $orders->max('created_at'),
                'first_order_date' => $orders->min('created_at'),
            ];
        });
        
        // Summary statistics
        $summary = [
            'total_customers' => $customers->count(),
            'new_customers' => $customers->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'active_customers' => $customers->filter(function ($customer) {
                return $customer->orders->count() > 0;
            })->count(),
            'total_customer_value' => $customersWithMetrics->sum('total_spent'),
            'average_customer_value' => $customersWithMetrics->avg('total_spent'),
        ];
        
        // Top customers
        $topCustomers = $customersWithMetrics->sortByDesc('total_spent')->take(10);
        
        return [
            'customers' => $customersWithMetrics,
            'summary' => $summary,
            'top_customers' => $topCustomers,
            'date_range' => $dateRange,
            'filters' => $filters,
        ];
    }

    /**
     * Get financial report data.
     *
     * @param array $dateRange
     * @param array $filters
     * @return array
     */
    protected function getFinancialReportData(array $dateRange, array $filters): array
    {
        $orders = Order::whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->whereIn('status', ['completed', 'delivered'])
            ->get();
        
        // Revenue breakdown
        $revenue = [
            'gross_revenue' => $orders->sum('subtotal'),
            'shipping_revenue' => $orders->sum('shipping_cost'),
            'tax_revenue' => $orders->sum('tax_amount'),
            'total_discounts' => $orders->sum('discount_amount'),
            'net_revenue' => $orders->sum('total_amount'),
        ];
        
        // Monthly breakdown
        $monthlyRevenue = $orders->groupBy(function ($order) {
            return $order->created_at->format('Y-m');
        })->map(function ($group) {
            return [
                'orders' => $group->count(),
                'revenue' => $group->sum('total_amount'),
                'average_order_value' => $group->avg('total_amount'),
            ];
        });
        
        // Payment method breakdown
        $paymentMethods = $orders->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
            ];
        });
        
        return [
            'revenue' => $revenue,
            'monthly_revenue' => $monthlyRevenue,
            'payment_methods' => $paymentMethods,
            'orders' => $orders,
            'date_range' => $dateRange,
            'filters' => $filters,
        ];
    }

    /**
     * Generate sales report PDF.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateSalesReportPDF(array $data, array $filters): string
    {
        $pdf = Pdf::loadView('reports.sales-pdf', $data);
        $fileName = 'sales-report-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        $filePath = 'reports/' . $fileName;
        
        Storage::put($filePath, $pdf->output());
        
        return $filePath;
    }

    /**
     * Generate sales report Excel.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateSalesReportExcel(array $data, array $filters): string
    {
        $fileName = 'sales-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        
        Excel::store(new SalesExport($data), $filePath);
        
        return $filePath;
    }

    /**
     * Generate sales report CSV.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateSalesReportCSV(array $data, array $filters): string
    {
        $fileName = 'sales-report-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $filePath = 'reports/' . $fileName;
        
        $csvData = $this->convertSalesDataToCSV($data);
        Storage::put($filePath, $csvData);
        
        return $filePath;
    }

    /**
     * Generate inventory report PDF.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateInventoryReportPDF(array $data, array $filters): string
    {
        $pdf = Pdf::loadView('reports.inventory-pdf', $data);
        $fileName = 'inventory-report-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        $filePath = 'reports/' . $fileName;
        
        Storage::put($filePath, $pdf->output());
        
        return $filePath;
    }

    /**
     * Generate inventory report Excel.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateInventoryReportExcel(array $data, array $filters): string
    {
        $fileName = 'inventory-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        
        Excel::store(new ProductsExport($data['products']), $filePath);
        
        return $filePath;
    }

    /**
     * Generate inventory report CSV.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateInventoryReportCSV(array $data, array $filters): string
    {
        $fileName = 'inventory-report-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $filePath = 'reports/' . $fileName;
        
        $csvData = $this->convertInventoryDataToCSV($data);
        Storage::put($filePath, $csvData);
        
        return $filePath;
    }

    /**
     * Generate customer report PDF.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateCustomerReportPDF(array $data, array $filters): string
    {
        $pdf = Pdf::loadView('reports.customer-pdf', $data);
        $fileName = 'customer-report-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        $filePath = 'reports/' . $fileName;
        
        Storage::put($filePath, $pdf->output());
        
        return $filePath;
    }

    /**
     * Generate customer report Excel.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateCustomerReportExcel(array $data, array $filters): string
    {
        $fileName = 'customer-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        
        Excel::store(new CustomersExport($data['customers']), $filePath);
        
        return $filePath;
    }

    /**
     * Generate customer report CSV.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateCustomerReportCSV(array $data, array $filters): string
    {
        $fileName = 'customer-report-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $filePath = 'reports/' . $fileName;
        
        $csvData = $this->convertCustomerDataToCSV($data);
        Storage::put($filePath, $csvData);
        
        return $filePath;
    }

    /**
     * Generate financial report PDF.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateFinancialReportPDF(array $data, array $filters): string
    {
        $pdf = Pdf::loadView('reports.financial-pdf', $data);
        $fileName = 'financial-report-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        $filePath = 'reports/' . $fileName;
        
        Storage::put($filePath, $pdf->output());
        
        return $filePath;
    }

    /**
     * Generate financial report Excel.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateFinancialReportExcel(array $data, array $filters): string
    {
        $fileName = 'financial-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        
        // Create a custom export for financial data
        $csvData = $this->convertFinancialDataToCSV($data);
        Storage::put($filePath, $csvData);
        
        return $filePath;
    }

    /**
     * Generate financial report CSV.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateFinancialReportCSV(array $data, array $filters): string
    {
        $fileName = 'financial-report-' . now()->format('Y-m-d-H-i-s') . '.csv';
        $filePath = 'reports/' . $fileName;
        
        $csvData = $this->convertFinancialDataToCSV($data);
        Storage::put($filePath, $csvData);
        
        return $filePath;
    }

    /**
     * Generate analytics report PDF.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateAnalyticsReportPDF(array $data, array $filters): string
    {
        $pdf = Pdf::loadView('reports.analytics-pdf', $data);
        $fileName = 'analytics-report-' . now()->format('Y-m-d-H-i-s') . '.pdf';
        $filePath = 'reports/' . $fileName;
        
        Storage::put($filePath, $pdf->output());
        
        return $filePath;
    }

    /**
     * Generate analytics report Excel.
     *
     * @param array $data
     * @param array $filters
     * @return string
     */
    protected function generateAnalyticsReportExcel(array $data, array $filters): string
    {
        $fileName = 'analytics-report-' . now()->format('Y-m-d-H-i-s') . '.xlsx';
        $filePath = 'reports/' . $fileName;
        
        // Create a comprehensive Excel file with multiple sheets
        $csvData = $this->convertAnalyticsDataToCSV($data);
        Storage::put($filePath, $csvData);
        
        return $filePath;
    }

    /**
     * Convert sales data to CSV format.
     *
     * @param array $data
     * @return string
     */
    protected function convertSalesDataToCSV(array $data): string
    {
        $csv = "Order ID,Customer,Date,Status,Items,Subtotal,Shipping,Tax,Discount,Total\n";
        
        foreach ($data['orders'] as $order) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%d,%.2f,%.2f,%.2f,%.2f,%.2f\n",
                $order->id,
                $order->user ? $order->user->name : 'Guest',
                $order->created_at->format('Y-m-d'),
                $order->status,
                $order->items->sum('quantity'),
                $order->subtotal,
                $order->shipping_cost,
                $order->tax_amount,
                $order->discount_amount,
                $order->total_amount
            );
        }
        
        return $csv;
    }

    /**
     * Convert inventory data to CSV format.
     *
     * @param array $data
     * @return string
     */
    protected function convertInventoryDataToCSV(array $data): string
    {
        $csv = "Product ID,Name,SKU,Category,Price,Stock,Status\n";
        
        foreach ($data['products'] as $product) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%.2f,%d,%s\n",
                $product->id,
                $product->name,
                $product->sku,
                $product->category ? $product->category->name : 'N/A',
                $product->price,
                $product->stock_quantity,
                $product->is_active ? 'Active' : 'Inactive'
            );
        }
        
        return $csv;
    }

    /**
     * Convert customer data to CSV format.
     *
     * @param array $data
     * @return string
     */
    protected function convertCustomerDataToCSV(array $data): string
    {
        $csv = "Customer ID,Name,Email,Registration Date,Total Orders,Total Spent,Average Order Value\n";
        
        foreach ($data['customers'] as $customerData) {
            $customer = $customerData['customer'];
            $csv .= sprintf(
                "%s,%s,%s,%s,%d,%.2f,%.2f\n",
                $customer->id,
                $customer->name,
                $customer->email,
                $customer->created_at->format('Y-m-d'),
                $customerData['total_orders'],
                $customerData['total_spent'],
                $customerData['average_order_value']
            );
        }
        
        return $csv;
    }

    /**
     * Convert financial data to CSV format.
     *
     * @param array $data
     * @return string
     */
    protected function convertFinancialDataToCSV(array $data): string
    {
        $csv = "Metric,Amount\n";
        
        foreach ($data['revenue'] as $metric => $amount) {
            $csv .= sprintf("%s,%.2f\n", ucfirst(str_replace('_', ' ', $metric)), $amount);
        }
        
        return $csv;
    }

    /**
     * Convert analytics data to CSV format.
     *
     * @param array $data
     * @return string
     */
    protected function convertAnalyticsDataToCSV(array $data): string
    {
        // This would be a simplified version
        // In a real implementation, you'd create multiple CSV sections
        $csv = "Section,Metric,Value\n";
        
        foreach ($data['sections'] as $section => $sectionData) {
            if (isset($sectionData['current'])) {
                foreach ($sectionData['current'] as $metric => $value) {
                    $csv .= sprintf("%s,%s,%s\n", ucfirst($section), ucfirst(str_replace('_', ' ', $metric)), $value);
                }
            }
        }
        
        return $csv;
    }

    /**
     * Get date range from filters.
     *
     * @param array $filters
     * @return array
     */
    protected function getDateRangeFromFilters(array $filters): array
    {
        $start = isset($filters['start_date']) ? Carbon::parse($filters['start_date']) : now()->subDays(30);
        $end = isset($filters['end_date']) ? Carbon::parse($filters['end_date']) : now();
        
        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    /**
     * Clean up old report files.
     *
     * @param int $daysOld
     * @return int
     */
    public function cleanupOldReports(int $daysOld = 30): int
    {
        $files = Storage::files('reports');
        $deletedCount = 0;
        
        foreach ($files as $file) {
            $lastModified = Storage::lastModified($file);
            
            if ($lastModified < now()->subDays($daysOld)->timestamp) {
                Storage::delete($file);
                $deletedCount++;
            }
        }
        
        Log::info('Cleaned up old report files', ['deleted_count' => $deletedCount]);
        
        return $deletedCount;
    }
}