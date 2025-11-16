<?php

namespace App\Actions;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ProcessOrderAction
{
    public function execute(Order $order): bool
    {
        if (!$order->isPending()) {
            throw new \Exception('Only pending orders can be processed');
        }

        return DB::transaction(function () use ($order) {
            // Deduct inventory for each item
            foreach ($order->items as $item) {
                if ($item->product_variant_id) {
                    $success = $item->variant->decreaseStock($item->quantity, 'order_confirmation');
                } else {
                    $success = $item->product->decreaseStock($item->quantity, 'order_confirmation');
                }

                if (!$success) {
                    throw new \Exception("Insufficient stock for product: {$item->product_name}");
                }
            }

            // Update order status
            $order->markAsProcessing();

            return true;
        });
    }
}
