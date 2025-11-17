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
}
