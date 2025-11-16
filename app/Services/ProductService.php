<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Support\Facades\DB;
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
            $product = $this->productRepository->update($id, $data);

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
        return (bool) $this->productRepository->delete($id);
    }

    public function importProductsFromCsv(string $filePath): array
    {
        $imported = 0;
        $errors = [];

        if (($handle = fopen($filePath, 'r')) !== false) {
            $header = fgetcsv($handle);

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    $data = array_combine($header, $row);
                    $this->productRepository->create($data);
                    $imported++;
                } catch (\Exception $e) {
                    $errors[] = "Row {$imported}: " . $e->getMessage();
                }
            }

            fclose($handle);
        }

        return [
            'imported' => $imported,
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
