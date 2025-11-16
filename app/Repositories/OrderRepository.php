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
            ->recent()
            ->paginate($perPage);
    }

    public function getOrderWithItems($id): Order
    {
        return $this->model->with(['items.product', 'customer'])->findOrFail($id);
    }

    public function getOrdersByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byStatus($status)
            ->with(['customer', 'items'])
            ->recent()
            ->paginate($perPage);
    }
}
