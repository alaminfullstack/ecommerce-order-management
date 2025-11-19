<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository extends BaseRepository
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function searchProducts(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with('vendor', 'variants')->active();

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['vendor_id'])) {
            $query->byVendor($filters['vendor_id']);
        }

        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        return $query->paginate($perPage);
    }

    public function getLowStockProducts(): Collection
    {
        return $this->model->lowStock()->with('vendor')->get();
    }

    public function getProductWithVariants($id): Product
    {
        return $this->model->with('variants')->findOrFail($id);
    }
    
    public function findWithTrashed($id): Product
    {
        return $this->model->withTrashed()->findOrFail($id);
    }

    public function getInventoryReportQuery(array $filters)
    {
        $query = $this->model->with(['vendor', 'variants']);

        if (isset($filters['product_id'])) {
            $query->where('id', $filters['product_id']);
        }

        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        return $query;
    }

    public function getStockReportQuery(array $filters, bool $includeVariants = false)
    {
        $query = $this->model->with(['vendor']);

        if ($includeVariants) {
            $query->with(['variants']);
        }

        if (isset($filters['product_id'])) {
            $query->where('id', $filters['product_id']);
        }

        if (isset($filters['vendor_id'])) {
            $query->where('vendor_id', $filters['vendor_id']);
        }

        return $query;
    }
}
