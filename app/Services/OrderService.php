<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\OrderRepository;
use App\Actions\ProcessOrderAction;
use App\Actions\CancelOrderAction;
use App\Jobs\GenerateInvoiceJob;
use App\Jobs\SendOrderEmailJob;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private $orderRepository;
    private $processOrderAction;
    private $cancelOrderAction;

    public function __construct(
         OrderRepository $orderRepository,
         ProcessOrderAction $processOrderAction,
         CancelOrderAction $cancelOrderAction
    ) {
        $this->orderRepository = $orderRepository;
        $this->processOrderAction = $processOrderAction;
        $this->cancelOrderAction = $cancelOrderAction;
    }

    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Calculate totals
            $subtotal = 0;
            $itemsData = [];

            foreach ($data['items'] as $item) {
                if (isset($item['variant_id'])) {
                    $variant = ProductVariant::findOrFail($item['variant_id']);
                    $price = $variant->price;
                    $sku = $variant->sku;
                    $name = $variant->product->name . ' - ' . $variant->name;
                } else {
                    $product = Product::findOrFail($item['product_id']);
                    $price = $product->price;
                    $sku = $product->sku;
                    $name = $product->name;
                }

                $itemSubtotal = $price * $item['quantity'];
                $subtotal += $itemSubtotal;

                $itemsData[] = [
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['variant_id'] ?? null,
                    'product_name' => $name,
                    'product_sku' => $sku,
                    'price' => $price,
                    'quantity' => $item['quantity'],
                    'subtotal' => $itemSubtotal,
                    'variant_details' => $item['variant_details'] ?? null,
                ];
            }

            $tax = $subtotal * 0.1; // 10% tax
            $shipping = $data['shipping'] ?? 0;
            $total = $subtotal + $tax + $shipping;

            // Create order
            $order = $this->orderRepository->create([
                'customer_id' => $data['customer_id'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping' => $shipping,
                'total' => $total,
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Create order items
            $order->items()->createMany($itemsData);

            return $order->load('items.product');
        });
    }

    public function confirmOrder(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->orderRepository->find($orderId);
            $this->processOrderAction->execute($order);

            // Dispatch jobs
            dispatch(new GenerateInvoiceJob($order));
            dispatch(new SendOrderEmailJob($order, 'confirmed'));

            return $order->fresh(['items.product', 'customer']);
        });
    }

    public function updateOrderStatus(int $orderId, string $status): Order
    {
        $order = $this->orderRepository->find($orderId);

        switch ($status) {
            case 'processing':
                $order->markAsProcessing();
                dispatch(new SendOrderEmailJob($order, 'processing'));
                break;
            case 'shipped':
                $order->markAsShipped();
                dispatch(new SendOrderEmailJob($order, 'shipped'));
                break;
            case 'delivered':
                $order->markAsDelivered();
                dispatch(new SendOrderEmailJob($order, 'delivered'));
                break;
            case 'cancelled':
                $this->cancelOrder($orderId);
                break;
        }

        return $order->fresh();
    }

    public function cancelOrder(int $orderId): Order
    {
        return DB::transaction(function () use ($orderId) {
            $order = $this->orderRepository->find($orderId);
            $this->cancelOrderAction->execute($order);

            dispatch(new SendOrderEmailJob($order, 'cancelled'));

            return $order->fresh();
        });
    }
}
