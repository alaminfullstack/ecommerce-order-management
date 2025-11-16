<?php

namespace App\Actions;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class CancelOrderAction
{
    public function execute(Order $order): bool
    {
        if (!$order->canBeCancelled()) {
            throw new \Exception('Order cannot be cancelled in current status');
        }

        return DB::transaction(function () use ($order) {
            // Only restore inventory if order was processing (inventory was already deducted)
            if ($order->isProcessing()) {
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        $item->variant->increaseStock($item->quantity, 'order_cancellation');
                    } else {
                        $item->product->increaseStock($item->quantity, 'order_cancellation');
                    }
                }
            }

            // Update order status
            $order->markAsCancelled();

            return true;
        });
    }
}
