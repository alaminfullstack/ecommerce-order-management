<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Jobs\CheckLowStockJob;


class ProductService
{
    private $productRepository;

    public function __construct(
        ProductRepository $productRepository
    ) {
        $this->productRepository = $productRepository;
    }

    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = $this->productRepository->create($data);

            if (isset($data['variants']) && is_array($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $product->variants()->create($variantData);
                }
            }

            return $product->load('variants');
        });
    }

    public function updateProduct(int $id, array $data): Product
    {
        return DB::transaction(function () use ($id, $data) {
            $product = $this->productRepository->findWithTrashed($id);
            
            if (!$product) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Product not found');
            }
            
            // Check if user is a vendor and owns the product
            if (Auth::user()->role === 'vendor' && $product->vendor_id !== Auth::id()) {
                throw new \Illuminate\Auth\Access\AuthorizationException('You can only update your own products');
            }
            
            // Check if user is a customer
            if (Auth::user()->role === 'customer') {
                throw new \Illuminate\Auth\Access\AuthorizationException('Customers cannot update products');
            }
            
            $product->update($data);

            if (isset($data['variants']) && is_array($data['variants'])) {
                // Simple variant update logic
                foreach ($data['variants'] as $variantData) {
                    if (isset($variantData['id'])) {
                        $product->variants()->where('id', $variantData['id'])->update($variantData);
                    } else {
                        $product->variants()->create($variantData);
                    }
                }
            }

            return $product->fresh('variants');
        });
    }

    public function deleteProduct(int $id): bool
    {
        $product = $this->productRepository->findWithTrashed($id);
        
        if (!$product) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Product not found');
        }
        
        // Check if user is a vendor and owns the product
        if (Auth::user()->role === 'vendor' && $product->vendor_id !== Auth::id()) {
            throw new \Illuminate\Auth\Access\AuthorizationException('You can only delete your own products');
        }
        
        // Check if user is a customer
        if (Auth::user()->role === 'customer') {
            throw new \Illuminate\Auth\Access\AuthorizationException('Customers cannot delete products');
        }
        
        $product->forceDelete();
        return true;
    }

    public function importProductsFromCsv(string $filePath): array
    {
        $imported = 0;
        $errors = [];
        
        // Ensure the file exists
        if (!file_exists($filePath)) {
            throw new \Exception('Import file not found');
        }

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    $data = array_combine($header, $row);
                    $data['vendor_id'] = Auth::id();
                    $this->productRepository->create($data);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$imported}: " . $e->getMessage();
                }
            }

            fclose($handle);
        }

        return [
            'total_processed' => $imported + count($errors),
            'successful_imports' => $imported,
            'failed_imports' => count($errors),
            'errors' => $errors,
        ];
    }

    public function checkLowStock(): void
    {
        $lowStockProducts = $this->productRepository->getLowStockProducts();

        foreach ($lowStockProducts as $product) {
            dispatch(new CheckLowStockJob($product));
        }
    }

    public function generateInventoryReport(array $filters, int $perPage = 15): array
    {
        $query = $this->productRepository->getInventoryReportQuery($filters);

        // Get paginated products
        $products = $query->paginate($perPage);

        // Get inventory logs for date range filtering
        $inventoryLogsQuery = \App\Models\InventoryLog::with(['inventoriable', 'user']);

        if (isset($filters['product_id'])) {
            $inventoryLogsQuery->where(function($q) use ($filters) {
                $q->where(function($subQuery) use ($filters) {
                    $subQuery->where('inventoriable_type', 'App\\Models\\Product')
                        ->where('inventoriable_id', $filters['product_id']);
                })->orWhere(function($subQuery) use ($filters) {
                    $subQuery->where('inventoriable_type', 'App\\Models\\ProductVariant')
                        ->whereHas('inventoriable', function($variantQuery) use ($filters) {
                            $variantQuery->where('product_id', $filters['product_id']);
                        });
                });
            });
        }

        if (isset($filters['vendor_id'])) {
            $inventoryLogsQuery->where(function($q) use ($filters) {
                $q->where(function($subQuery) use ($filters) {
                    $subQuery->where('inventoriable_type', 'App\\Models\\Product')
                        ->whereHas('inventoriable', function($productQuery) use ($filters) {
                            $productQuery->where('vendor_id', $filters['vendor_id']);
                        });
                })->orWhere(function($subQuery) use ($filters) {
                    $subQuery->where('inventoriable_type', 'App\\Models\\ProductVariant')
                        ->whereHas('inventoriable.product', function($productQuery) use ($filters) {
                            $productQuery->where('vendor_id', $filters['vendor_id']);
                        });
                });
            });
        }

        if (isset($filters['start_date'])) {
            $inventoryLogsQuery->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $inventoryLogsQuery->whereDate('created_at', '<=', $filters['end_date']);
        }

        $inventoryLogs = $inventoryLogsQuery->orderBy('created_at', 'desc')->get();

        // Calculate summary statistics
        $summary = [
            'total_products' => $query->count(),
            'low_stock_products' => $query->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count(),
            'total_stock_value' => $query->sum(DB::raw('stock_quantity * price')),
            'inventory_changes' => $inventoryLogs->count(),
        ];

        return [
            'summary' => $summary,
            'products' => $products,
            'inventory_logs' => $inventoryLogs,
        ];
    }
}
