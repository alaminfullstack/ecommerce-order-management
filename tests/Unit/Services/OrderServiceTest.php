<?php

namespace Tests\Unit\Services;

use App\Actions\CancelOrderAction;
use App\Actions\ProcessOrderAction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Repositories\OrderRepository;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OrderService $service;
    protected OrderRepository $repository;
    protected ProcessOrderAction $processOrderAction;
    protected CancelOrderAction $cancelOrderAction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(OrderRepository::class);
        $this->processOrderAction = Mockery::mock(ProcessOrderAction::class);
        $this->cancelOrderAction = Mockery::mock(CancelOrderAction::class);

        $this->service = new OrderService(
            $this->repository,
            $this->processOrderAction,
            $this->cancelOrderAction
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_order_creates_order_with_items()
    {
        $customer = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 12.00
        ]);

        $orderData = [
            'customer_id' => $customer->id,
            'shipping_address' => 'Test Address',
            'billing_address' => 'Test Address',
            'notes' => 'Test notes',
            'shipping' => 5.00,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 1,
                    'variant_details' => 'Color: Red',
                ],
            ],
        ];

        $order = new Order($orderData);
        $order->id = 1;
        $order->setRelation('items', collect());

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->andReturn($order);

        $result = $this->service->createOrder($orderData);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals($order->id, $result->id);
    }

    public function test_confirm_order_processes_order_and_dispatches_jobs()
    {
        Queue::fake();

        $orderId = 1;
        $order = Order::factory()->make(['id' => $orderId]);
        $order->setRelation('items', collect());
        $order->setRelation('customer', new User());

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        $this->processOrderAction
            ->shouldReceive('execute')
            ->once()
            ->with($order)
            ->andReturn(true);

        $result = $this->service->confirmOrder($orderId);

        $this->assertInstanceOf(Order::class, $result);

        Queue::assertPushed(\App\Jobs\GenerateInvoiceJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id;
        });

        Queue::assertPushed(\App\Jobs\SendOrderEmailJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id && $job->type === 'confirmed';
        });
    }

    public function test_update_order_status_to_processing_marks_order_and_dispatches_job()
    {
        Queue::fake();

        $orderId = 1;
        $order = Order::factory()->make(['id' => $orderId]);

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        $result = $this->service->updateOrderStatus($orderId, 'processing');

        $this->assertInstanceOf(Order::class, $result);

        Queue::assertPushed(\App\Jobs\SendOrderEmailJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id && $job->type === 'processing';
        });
    }

    public function test_update_order_status_to_shipped_marks_order_and_dispatches_job()
    {
        Queue::fake();

        $orderId = 1;
        $order = Order::factory()->make(['id' => $orderId]);

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        $result = $this->service->updateOrderStatus($orderId, 'shipped');

        $this->assertInstanceOf(Order::class, $result);

        Queue::assertPushed(\App\Jobs\SendOrderEmailJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id && $job->type === 'shipped';
        });
    }

    public function test_update_order_status_to_delivered_marks_order_and_dispatches_job()
    {
        Queue::fake();

        $orderId = 1;
        $order = Order::factory()->make(['id' => $orderId]);

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        $result = $this->service->updateOrderStatus($orderId, 'delivered');

        $this->assertInstanceOf(Order::class, $result);

        Queue::assertPushed(\App\Jobs\SendOrderEmailJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id && $job->type === 'delivered';
        });
    }

    public function test_update_order_status_to_cancelled_cancels_order()
    {
        Queue::fake();

        $orderId = 1;
        $order = Order::factory()->make(['id' => $orderId]);

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        $this->cancelOrderAction
            ->shouldReceive('execute')
            ->once()
            ->with($order)
            ->andReturn(true);

        $result = $this->service->updateOrderStatus($orderId, 'cancelled');

        $this->assertInstanceOf(Order::class, $result);

        Queue::assertPushed(\App\Jobs\SendOrderEmailJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id && $job->type === 'cancelled';
        });
    }

    public function test_cancel_order_cancels_order_and_dispatches_job()
    {
        Queue::fake();

        $orderId = 1;
        $order = Order::factory()->make(['id' => $orderId]);

        $this->repository
            ->shouldReceive('find')
            ->once()
            ->with($orderId)
            ->andReturn($order);

        $this->cancelOrderAction
            ->shouldReceive('execute')
            ->once()
            ->with($order)
            ->andReturn(true);

        $result = $this->service->cancelOrder($orderId);

        $this->assertInstanceOf(Order::class, $result);

        Queue::assertPushed(\App\Jobs\SendOrderEmailJob::class, function ($job) use ($order) {
            return $job->order->id === $order->id && $job->type === 'cancelled';
        });
    }
}
