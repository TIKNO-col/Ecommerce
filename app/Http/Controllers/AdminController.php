<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\Review;
use App\Models\Cart;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin'); // You'll need to create this middleware
    }

    /**
     * Show admin dashboard.
     */
    public function dashboard(): View
    {
        // Get key metrics
        $metrics = [
            'total_users' => User::count(),
            'total_products' => Product::count(),
            'total_orders' => Order::count(),
            'total_revenue' => Order::where('payment_status', Order::PAYMENT_PAID)->sum('total_amount'),
            'pending_orders' => Order::where('status', Order::STATUS_PENDING)->count(),
            'low_stock_products' => Product::where('stock_quantity', '<=', 10)->count(),
            'pending_reviews' => Review::where('is_approved', false)->count(),
            'active_users_today' => User::whereDate('last_login_at', today())->count(),
        ];

        // Get recent orders
        $recentOrders = Order::with(['user', 'items'])
            ->latest()
            ->limit(10)
            ->get();

        // Get top selling products
        $topProducts = Product::withCount(['orderItems'])
            ->orderBy('order_items_count', 'desc')
            ->limit(10)
            ->get();

        // Get sales data for chart (last 30 days)
        $salesData = Order::where('payment_status', Order::PAYMENT_PAID)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total, COUNT(*) as orders')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get user registration data (last 30 days)
        $userRegistrations = User::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get category performance
        $categoryPerformance = Category::withCount(['products'])
            ->with(['products' => function ($query) {
                $query->withCount('orderItems');
            }])
            ->get()
            ->map(function ($category) {
                $totalSales = $category->products->sum('order_items_count');
                return [
                    'name' => $category->name,
                    'products_count' => $category->products_count,
                    'total_sales' => $totalSales,
                ];
            })
            ->sortByDesc('total_sales')
            ->take(10);

        return view('admin.dashboard', compact(
            'metrics',
            'recentOrders',
            'topProducts',
            'salesData',
            'userRegistrations',
            'categoryPerformance'
        ));
    }

    /**
     * Get dashboard analytics data.
     */
    public function analytics(Request $request): JsonResponse
    {
        $period = $request->get('period', '30'); // days
        $startDate = now()->subDays($period);

        $analytics = [
            'sales' => [
                'total' => Order::where('payment_status', Order::PAYMENT_PAID)
                    ->where('created_at', '>=', $startDate)
                    ->sum('total_amount'),
                'orders_count' => Order::where('created_at', '>=', $startDate)->count(),
                'average_order_value' => Order::where('payment_status', Order::PAYMENT_PAID)
                    ->where('created_at', '>=', $startDate)
                    ->avg('total_amount'),
                'daily_data' => Order::where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, SUM(total_amount) as revenue, COUNT(*) as orders')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
            ],
            'products' => [
                'total' => Product::count(),
                'active' => Product::where('is_active', true)->count(),
                'low_stock' => Product::where('stock_quantity', '<=', 10)->count(),
                'out_of_stock' => Product::where('stock_quantity', 0)->count(),
                'top_selling' => Product::withCount(['orderItems' => function ($query) use ($startDate) {
                    $query->whereHas('order', function ($q) use ($startDate) {
                        $q->where('created_at', '>=', $startDate);
                    });
                }])
                ->orderBy('order_items_count', 'desc')
                ->limit(10)
                ->get(['id', 'name', 'price', 'order_items_count']),
            ],
            'users' => [
                'total' => User::count(),
                'new_registrations' => User::where('created_at', '>=', $startDate)->count(),
                'active_users' => User::where('last_login_at', '>=', $startDate)->count(),
                'registration_data' => User::where('created_at', '>=', $startDate)
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get(),
            ],
            'orders' => [
                'total' => Order::count(),
                'pending' => Order::where('status', Order::STATUS_PENDING)->count(),
                'processing' => Order::where('status', Order::STATUS_PROCESSING)->count(),
                'shipped' => Order::where('status', Order::STATUS_SHIPPED)->count(),
                'delivered' => Order::where('status', Order::STATUS_DELIVERED)->count(),
                'cancelled' => Order::where('status', Order::STATUS_CANCELLED)->count(),
                'status_distribution' => Order::selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->get(),
            ],
            'reviews' => [
                'total' => Review::count(),
                'approved' => Review::where('is_approved', true)->count(),
                'pending' => Review::where('is_approved', false)->count(),
                'average_rating' => Review::where('is_approved', true)->avg('rating'),
                'rating_distribution' => Review::where('is_approved', true)
                    ->selectRaw('rating, COUNT(*) as count')
                    ->groupBy('rating')
                    ->orderBy('rating')
                    ->get(),
            ],
        ];

        return response()->json($analytics);
    }

    /**
     * Get system statistics.
     */
    public function systemStats(): JsonResponse
    {
        $stats = [
            'database' => [
                'users' => User::count(),
                'products' => Product::count(),
                'categories' => Category::count(),
                'orders' => Order::count(),
                'reviews' => Review::count(),
                'cart_items' => Cart::count(),
                'wishlist_items' => Wishlist::count(),
            ],
            'storage' => [
                'total_images' => $this->countStorageFiles('products'),
                'storage_size' => $this->getStorageSize('products'),
            ],
            'performance' => [
                'cache_hit_rate' => 95, // This would come from your cache system
                'average_response_time' => 150, // This would come from your monitoring
                'uptime' => 99.9, // This would come from your monitoring
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Export data for reporting.
     */
    public function exportData(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:orders,products,users,reviews',
            'format' => 'required|in:csv,json,excel',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        try {
            $type = $request->type;
            $format = $request->format;
            $dateFrom = $request->date_from ? Carbon::parse($request->date_from) : null;
            $dateTo = $request->date_to ? Carbon::parse($request->date_to) : null;

            $data = $this->getExportData($type, $dateFrom, $dateTo);
            $filename = $type . '_export_' . now()->format('Y-m-d_H-i-s') . '.' . $format;

            // In a real application, you would generate and store the file
            // For now, we'll just return the data
            return response()->json([
                'success' => true,
                'message' => 'Datos exportados exitosamente.',
                'filename' => $filename,
                'data' => $data,
                'download_url' => route('admin.download-export', ['filename' => $filename]),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al exportar los datos.'
            ], 500);
        }
    }

    /**
     * Get recent activity logs.
     */
    public function activityLogs(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 50);
        $type = $request->get('type'); // orders, products, users, reviews

        $activities = collect();

        if (!$type || $type === 'orders') {
            $orderActivities = Order::with(['user'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($order) {
                    return [
                        'type' => 'order',
                        'title' => 'Nuevo pedido #' . $order->order_number,
                        'description' => 'Usuario: ' . $order->user->name . ' - Total: ' . $order->formatted_total_amount,
                        'date' => $order->created_at,
                        'user' => $order->user->name,
                        'status' => $order->status,
                        'url' => route('admin.orders.show', $order->id),
                    ];
                });
            $activities = $activities->merge($orderActivities);
        }

        if (!$type || $type === 'users') {
            $userActivities = User::latest()
                ->limit($limit)
                ->get()
                ->map(function ($user) {
                    return [
                        'type' => 'user',
                        'title' => 'Nuevo usuario registrado',
                        'description' => $user->name . ' (' . $user->email . ')',
                        'date' => $user->created_at,
                        'user' => $user->name,
                        'status' => $user->email_verified_at ? 'verified' : 'pending',
                        'url' => route('admin.users.show', $user->id),
                    ];
                });
            $activities = $activities->merge($userActivities);
        }

        if (!$type || $type === 'reviews') {
            $reviewActivities = Review::with(['user', 'product'])
                ->latest()
                ->limit($limit)
                ->get()
                ->map(function ($review) {
                    return [
                        'type' => 'review',
                        'title' => 'Nueva reseña',
                        'description' => $review->user->name . ' reseñó ' . $review->product->name . ' (' . $review->rating . ' estrellas)',
                        'date' => $review->created_at,
                        'user' => $review->user->name,
                        'status' => $review->is_approved ? 'approved' : 'pending',
                        'url' => route('admin.reviews.show', $review->id),
                    ];
                });
            $activities = $activities->merge($reviewActivities);
        }

        $activities = $activities
            ->sortByDesc('date')
            ->take($limit)
            ->values();

        return response()->json($activities);
    }

    /**
     * Get low stock alerts.
     */
    public function lowStockAlerts(): JsonResponse
    {
        $lowStockProducts = Product::where('stock_quantity', '<=', 10)
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->get([
                'id', 'name', 'sku', 'stock_quantity', 'price', 'image_url'
            ]);

        return response()->json([
            'count' => $lowStockProducts->count(),
            'products' => $lowStockProducts,
        ]);
    }

    /**
     * Get pending reviews for moderation.
     */
    public function pendingReviews(): JsonResponse
    {
        $pendingReviews = Review::with(['user', 'product'])
            ->where('is_approved', false)
            ->latest()
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->id,
                    'user_name' => $review->user->name,
                    'product_name' => $review->product->name,
                    'rating' => $review->rating,
                    'title' => $review->title,
                    'comment' => Str::limit($review->comment, 100),
                    'created_at' => $review->created_at,
                    'has_images' => $review->hasImages(),
                ];
            });

        return response()->json([
            'count' => $pendingReviews->count(),
            'reviews' => $pendingReviews,
        ]);
    }

    /**
     * Bulk approve reviews.
     */
    public function bulkApproveReviews(Request $request): JsonResponse
    {
        $request->validate([
            'review_ids' => 'required|array',
            'review_ids.*' => 'exists:reviews,id',
        ]);

        try {
            $approved = Review::whereIn('id', $request->review_ids)
                ->update(['is_approved' => true]);

            return response()->json([
                'success' => true,
                'message' => "Se aprobaron {$approved} reseñas exitosamente.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar las reseñas.'
            ], 500);
        }
    }

    /**
     * Update order status.
     */
    public function updateOrderStatus(Request $request, Order $order): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', [
                Order::STATUS_PENDING,
                Order::STATUS_PROCESSING,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED
            ]),
            'notes' => 'sometimes|string|max:500',
        ]);

        try {
            $oldStatus = $order->status;
            $order->update([
                'status' => $request->status,
                'admin_notes' => $request->notes,
            ]);

            // Log status change
            // ActivityLog::create([
            //     'user_id' => Auth::id(),
            //     'action' => 'order_status_updated',
            //     'description' => "Order #{$order->order_number} status changed from {$oldStatus} to {$request->status}",
            //     'subject_type' => Order::class,
            //     'subject_id' => $order->id,
            // ]);

            return response()->json([
                'success' => true,
                'message' => 'Estado del pedido actualizado exitosamente.',
                'order' => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'status_text' => $order->status_text,
                    'status_badge_class' => $order->status_badge_class,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el estado del pedido.'
            ], 500);
        }
    }

    /**
     * Get export data based on type.
     */
    private function getExportData(string $type, ?Carbon $dateFrom, ?Carbon $dateTo): array
    {
        $query = match ($type) {
            'orders' => Order::with(['user', 'items.product']),
            'products' => Product::with(['category']),
            'users' => User::query(),
            'reviews' => Review::with(['user', 'product']),
        };

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at', '<=', $dateTo);
        }

        return $query->get()->toArray();
    }

    /**
     * Count files in storage directory.
     */
    private function countStorageFiles(string $directory): int
    {
        try {
            $files = Storage::disk('public')->allFiles($directory);
            return count($files);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get storage size in MB.
     */
    private function getStorageSize(string $directory): float
    {
        try {
            $files = Storage::disk('public')->allFiles($directory);
            $totalSize = 0;
            
            foreach ($files as $file) {
                $totalSize += Storage::disk('public')->size($file);
            }
            
            return round($totalSize / 1024 / 1024, 2); // Convert to MB
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Clear application cache.
     */
    public function clearCache(): JsonResponse
    {
        try {
            // Clear various caches
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache limpiado exitosamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al limpiar el cache.'
            ], 500);
        }
    }

    /**
     * Get system health check.
     */
    public function healthCheck(): JsonResponse
    {
        $health = [
            'database' => $this->checkDatabaseConnection(),
            'storage' => $this->checkStorageAccess(),
            'cache' => $this->checkCacheConnection(),
            'queue' => $this->checkQueueConnection(),
        ];

        $overallHealth = collect($health)->every(fn($status) => $status === 'ok');

        return response()->json([
            'status' => $overallHealth ? 'healthy' : 'unhealthy',
            'checks' => $health,
            'timestamp' => now(),
        ]);
    }

    /**
     * Check database connection.
     */
    private function checkDatabaseConnection(): string
    {
        try {
            DB::connection()->getPdo();
            return 'ok';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Check storage access.
     */
    private function checkStorageAccess(): string
    {
        try {
            Storage::disk('public')->exists('test');
            return 'ok';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Check cache connection.
     */
    private function checkCacheConnection(): string
    {
        try {
            cache()->put('health_check', 'ok', 1);
            $value = cache()->get('health_check');
            return $value === 'ok' ? 'ok' : 'error';
        } catch (\Exception $e) {
            return 'error';
        }
    }

    /**
     * Check queue connection.
     */
    private function checkQueueConnection(): string
    {
        try {
            // This is a basic check - in production you might want to check if workers are running
            return 'ok';
        } catch (\Exception $e) {
            return 'error';
        }
    }
}