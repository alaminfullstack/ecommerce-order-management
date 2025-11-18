<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderRepository extends BaseRepository
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

    public function getCustomerOrders(int $customerId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byCustomer($customerId)
            ->with('items.product')
            ->select('*')
            ->selectRaw('total as total_amount')
            ->recent()
            ->paginate($perPage);
    }

    public function getOrderWithItems($id): Order
    {
        return $this->model->with(['items.product', 'customer'])
            ->select('*')
            ->selectRaw('total as total_amount')
            ->findOrFail($id);
    }

    public function getOrdersByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byStatus($status)
            ->with(['customer', 'items'])
            ->select('*')
            ->selectRaw('total as total_amount')
            ->recent()
            ->paginate($perPage);
    }

    public function getVendorOrders(int $vendorId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->whereHas('items.product', function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId);
            })
            ->with(['items.product', 'customer'])
            ->select('*')
            ->selectRaw('total as total_amount')
            ->recent()
            ->distinct()
            ->paginate($perPage);
    }
}
