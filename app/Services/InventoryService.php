<?php

namespace App\Services;

use App\Models\Product;
use App\Models\InventoryMovement;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Exception;

class InventoryService
{
    protected $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Update product stock.
     *
     * @param Product $product
     * @param int $quantity
     * @param string $type
     * @param string|null $reason
     * @param User|null $user
     * @return bool
     */
    public function updateStock(Product $product, int $quantity, string $type, ?string $reason = null, ?User $user = null): bool
    {
        try {
            DB::beginTransaction();
            
            $oldStock = $product->stock_quantity;
            
            // Calculate new stock based on movement type
            switch ($type) {
                case 'increase':
                case 'restock':
                case 'return':
                    $newStock = $oldStock + $quantity;
                    break;
                    
                case 'decrease':
                case 'sale':
                case 'damage':
                case 'loss':
                    $newStock = max(0, $oldStock - $quantity);
                    break;
                    
                case 'adjustment':
                    $newStock = $quantity; // Direct stock adjustment
                    break;
                    
                default:
                    throw new Exception("Invalid stock movement type: {$type}");
            }
            
            // Update product stock
            $product->update(['stock_quantity' => $newStock]);
            
            // Log inventory movement
            $this->logInventoryMovement([
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'reason' => $reason,
                'user_id' => $user?->id,
            ]);
            
            // Check for low stock alerts
            $this->checkLowStockAlert($product);
            
            // Check for out of stock
            if ($newStock <= 0 && $oldStock > 0) {
                $this->handleOutOfStock($product);
            }
            
            // Check for back in stock
            if ($newStock > 0 && $oldStock <= 0) {
                $this->handleBackInStock($product);
            }
            
            DB::commit();
            
            // Clear product cache
            Cache::forget("product_{$product->id}");
            Cache::forget("product_stock_{$product->id}");
            
            Log::info('Stock updated successfully', [
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
            ]);
            
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to update stock', [
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Reserve stock for order.
     *
     * @param array $items
     * @param string $orderId
     * @return bool
     */
    public function reserveStock(array $items, string $orderId): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    throw new Exception("Product not found: {$item['product_id']}");
                }
                
                if ($product->stock_quantity < $item['quantity']) {
                    throw new Exception("Insufficient stock for product: {$product->name}");
                }
                
                // Update stock
                $this->updateStock(
                    $product,
                    $item['quantity'],
                    'sale',
                    "Reserved for order: {$orderId}"
                );
            }
            
            DB::commit();
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to reserve stock', [
                'order_id' => $orderId,
                'items' => $items,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Release reserved stock (e.g., when order is cancelled).
     *
     * @param array $items
     * @param string $orderId
     * @return bool
     */
    public function releaseStock(array $items, string $orderId): bool
    {
        try {
            DB::beginTransaction();
            
            foreach ($items as $item) {
                $product = Product::find($item['product_id']);
                
                if (!$product) {
                    continue; // Skip if product not found
                }
                
                // Return stock
                $this->updateStock(
                    $product,
                    $item['quantity'],
                    'return',
                    "Released from cancelled order: {$orderId}"
                );
            }
            
            DB::commit();
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to release stock', [
                'order_id' => $orderId,
                'items' => $items,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Get low stock products.
     *
     * @param int|null $threshold
     * @return Collection
     */
    public function getLowStockProducts(?int $threshold = null): Collection
    {
        $threshold = $threshold ?? config('inventory.low_stock_threshold', 10);
        
        return Product::where('is_active', true)
            ->where('stock_quantity', '<=', $threshold)
            ->where('stock_quantity', '>', 0)
            ->with(['category', 'brand'])
            ->orderBy('stock_quantity', 'asc')
            ->get();
    }

    /**
     * Get out of stock products.
     *
     * @return Collection
     */
    public function getOutOfStockProducts(): Collection
    {
        return Product::where('is_active', true)
            ->where('stock_quantity', '<=', 0)
            ->with(['category', 'brand'])
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Get inventory movements for a product.
     *
     * @param Product $product
     * @param int $limit
     * @return Collection
     */
    public function getProductInventoryHistory(Product $product, int $limit = 50): Collection
    {
        return InventoryMovement::where('product_id', $product->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get inventory statistics.
     *
     * @return array
     */
    public function getInventoryStatistics(): array
    {
        $cacheKey = 'inventory_statistics';
        
        return Cache::remember($cacheKey, 300, function () {
            $totalProducts = Product::where('is_active', true)->count();
            $inStockProducts = Product::where('is_active', true)
                ->where('stock_quantity', '>', 0)
                ->count();
            $outOfStockProducts = Product::where('is_active', true)
                ->where('stock_quantity', '<=', 0)
                ->count();
            $lowStockProducts = Product::where('is_active', true)
                ->where('stock_quantity', '<=', config('inventory.low_stock_threshold', 10))
                ->where('stock_quantity', '>', 0)
                ->count();
            
            $totalStockValue = Product::where('is_active', true)
                ->selectRaw('SUM(stock_quantity * price) as total_value')
                ->first()
                ->total_value ?? 0;
            
            $totalStockQuantity = Product::where('is_active', true)
                ->sum('stock_quantity');
            
            return [
                'total_products' => $totalProducts,
                'in_stock_products' => $inStockProducts,
                'out_of_stock_products' => $outOfStockProducts,
                'low_stock_products' => $lowStockProducts,
                'stock_percentage' => $totalProducts > 0 ? round(($inStockProducts / $totalProducts) * 100, 2) : 0,
                'total_stock_value' => $totalStockValue,
                'total_stock_quantity' => $totalStockQuantity,
                'average_stock_per_product' => $totalProducts > 0 ? round($totalStockQuantity / $totalProducts, 2) : 0,
            ];
        });
    }

    /**
     * Bulk update stock.
     *
     * @param array $updates
     * @param User|null $user
     * @return array
     */
    public function bulkUpdateStock(array $updates, ?User $user = null): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];
        
        foreach ($updates as $update) {
            try {
                $product = Product::find($update['product_id']);
                
                if (!$product) {
                    $results['failed'][] = [
                        'product_id' => $update['product_id'],
                        'error' => 'Product not found',
                    ];
                    continue;
                }
                
                $success = $this->updateStock(
                    $product,
                    $update['quantity'],
                    $update['type'] ?? 'adjustment',
                    $update['reason'] ?? 'Bulk update',
                    $user
                );
                
                if ($success) {
                    $results['success'][] = [
                        'product_id' => $product->id,
                        'name' => $product->name,
                        'new_stock' => $product->fresh()->stock_quantity,
                    ];
                } else {
                    $results['failed'][] = [
                        'product_id' => $product->id,
                        'error' => 'Failed to update stock',
                    ];
                }
                
            } catch (Exception $e) {
                $results['failed'][] = [
                    'product_id' => $update['product_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Generate stock report.
     *
     * @param array $filters
     * @return array
     */
    public function generateStockReport(array $filters = []): array
    {
        $query = Product::where('is_active', true)
            ->with(['category', 'brand']);
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        if (!empty($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }
        
        if (isset($filters['stock_status'])) {
            switch ($filters['stock_status']) {
                case 'in_stock':
                    $query->where('stock_quantity', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('stock_quantity', '<=', 0);
                    break;
                case 'low_stock':
                    $query->where('stock_quantity', '<=', config('inventory.low_stock_threshold', 10))
                        ->where('stock_quantity', '>', 0);
                    break;
            }
        }
        
        $products = $query->orderBy('name')->get();
        
        $report = [
            'generated_at' => now(),
            'filters' => $filters,
            'summary' => [
                'total_products' => $products->count(),
                'total_stock_quantity' => $products->sum('stock_quantity'),
                'total_stock_value' => $products->sum(function ($product) {
                    return $product->stock_quantity * $product->price;
                }),
                'in_stock_count' => $products->where('stock_quantity', '>', 0)->count(),
                'out_of_stock_count' => $products->where('stock_quantity', '<=', 0)->count(),
                'low_stock_count' => $products->where('stock_quantity', '<=', config('inventory.low_stock_threshold', 10))
                    ->where('stock_quantity', '>', 0)->count(),
            ],
            'products' => $products->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'category' => $product->category->name ?? 'N/A',
                    'brand' => $product->brand->name ?? 'N/A',
                    'stock_quantity' => $product->stock_quantity,
                    'price' => $product->price,
                    'stock_value' => $product->stock_quantity * $product->price,
                    'status' => $this->getStockStatus($product),
                ];
            }),
        ];
        
        return $report;
    }

    /**
     * Check and create low stock alert.
     *
     * @param Product $product
     * @return void
     */
    protected function checkLowStockAlert(Product $product): void
    {
        $threshold = config('inventory.low_stock_threshold', 10);
        
        if ($product->stock_quantity <= $threshold && $product->stock_quantity > 0) {
            // Check if alert already exists
            $existingAlert = StockAlert::where('product_id', $product->id)
                ->where('type', 'low_stock')
                ->where('is_resolved', false)
                ->first();
            
            if (!$existingAlert) {
                StockAlert::create([
                    'product_id' => $product->id,
                    'type' => 'low_stock',
                    'threshold' => $threshold,
                    'current_stock' => $product->stock_quantity,
                    'message' => "Low stock alert: {$product->name} has only {$product->stock_quantity} items left.",
                ]);
                
                // Send notification to admins
                $this->notifyAdminsOfLowStock($product);
            }
        }
    }

    /**
     * Handle out of stock situation.
     *
     * @param Product $product
     * @return void
     */
    protected function handleOutOfStock(Product $product): void
    {
        // Create out of stock alert
        StockAlert::create([
            'product_id' => $product->id,
            'type' => 'out_of_stock',
            'threshold' => 0,
            'current_stock' => $product->stock_quantity,
            'message' => "Out of stock: {$product->name} is now out of stock.",
        ]);
        
        // Notify admins
        $this->notifyAdminsOfOutOfStock($product);
        
        // Update product status if needed
        if (config('inventory.auto_disable_out_of_stock', false)) {
            $product->update(['is_active' => false]);
        }
    }

    /**
     * Handle back in stock situation.
     *
     * @param Product $product
     * @return void
     */
    protected function handleBackInStock(Product $product): void
    {
        // Resolve out of stock alerts
        StockAlert::where('product_id', $product->id)
            ->where('type', 'out_of_stock')
            ->where('is_resolved', false)
            ->update(['is_resolved' => true]);
        
        // Get users waiting for this product
        $waitingUsers = $this->getUsersWaitingForProduct($product);
        
        if ($waitingUsers->isNotEmpty()) {
            $this->notificationService->sendProductBackInStockNotification($product, $waitingUsers);
        }
        
        // Re-enable product if it was auto-disabled
        if (!$product->is_active && config('inventory.auto_enable_back_in_stock', false)) {
            $product->update(['is_active' => true]);
        }
    }

    /**
     * Log inventory movement.
     *
     * @param array $data
     * @return void
     */
    protected function logInventoryMovement(array $data): void
    {
        InventoryMovement::create($data);
    }

    /**
     * Get stock status for a product.
     *
     * @param Product $product
     * @return string
     */
    protected function getStockStatus(Product $product): string
    {
        if ($product->stock_quantity <= 0) {
            return 'out_of_stock';
        }
        
        if ($product->stock_quantity <= config('inventory.low_stock_threshold', 10)) {
            return 'low_stock';
        }
        
        return 'in_stock';
    }

    /**
     * Notify admins of low stock.
     *
     * @param Product $product
     * @return void
     */
    protected function notifyAdminsOfLowStock(Product $product): void
    {
        $admins = User::where('is_admin', true)->get();
        
        foreach ($admins as $admin) {
            $this->notificationService->createNotification([
                'user_id' => $admin->id,
                'type' => 'low_stock_alert',
                'title' => 'Low Stock Alert',
                'message' => "{$product->name} is running low on stock ({$product->stock_quantity} remaining).",
                'data' => json_encode(['product_id' => $product->id]),
            ]);
        }
    }

    /**
     * Notify admins of out of stock.
     *
     * @param Product $product
     * @return void
     */
    protected function notifyAdminsOfOutOfStock(Product $product): void
    {
        $admins = User::where('is_admin', true)->get();
        
        foreach ($admins as $admin) {
            $this->notificationService->createNotification([
                'user_id' => $admin->id,
                'type' => 'out_of_stock_alert',
                'title' => 'Out of Stock Alert',
                'message' => "{$product->name} is now out of stock.",
                'data' => json_encode(['product_id' => $product->id]),
            ]);
        }
    }

    /**
     * Get users waiting for a product to be back in stock.
     *
     * @param Product $product
     * @return Collection
     */
    protected function getUsersWaitingForProduct(Product $product): Collection
    {
        // This would typically come from a "notify when available" feature
        // For now, return empty collection
        return collect([]);
        
        // TODO: Implement "notify when available" feature
        // return $product->waitingUsers()->get();
    }

    /**
     * Clear inventory cache.
     *
     * @return void
     */
    public function clearInventoryCache(): void
    {
        Cache::forget('inventory_statistics');
        
        // Clear product-specific cache
        $products = Product::pluck('id');
        foreach ($products as $productId) {
            Cache::forget("product_{$productId}");
            Cache::forget("product_stock_{$productId}");
        }
    }
}