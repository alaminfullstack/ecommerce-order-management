<?php

namespace Tests\Unit\Repositories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Repositories\OrderRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected OrderRepository $repository;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new OrderRepository(new Order());
        $this->customer = User::factory()->create();
    }

    public function test_get_customer_orders_returns_paginated_results()
    {
        Order::factory()->count(5)->create(['customer_id' => $this->customer->id]);
        Order::factory()->count(3)->create(); // Orders for other customers

        $result = $this->repository->getCustomerOrders($this->customer->id, 3);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(3, $result->perPage());
        $this->assertCount(3, $result->items());

        // Verify all returned orders belong to the customer
        foreach ($result->items() as $order) {
            $this->assertEquals($this->customer->id, $order->customer_id);
        }
    }

    public function test_get_customer_orders_includes_items_and_product_data()
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);
        $product = Product::factory()->create();
        OrderItem::factory()->count(2)->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $result = $this->repository->getCustomerOrders($this->customer->id);

        $this->assertTrue($result->items()[0]->relationLoaded('items'));
        $this->assertTrue($result->items()[0]->items[0]->relationLoaded('product'));
    }

    public function test_get_order_with_items_includes_items_and_customer_data()
    {
        $order = Order::factory()->create(['customer_id' => $this->customer->id]);
        $product = Product::factory()->create();
        OrderItem::factory()->count(2)->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $result = $this->repository->getOrderWithItems($order->id);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertTrue($result->relationLoaded('items'));
        $this->assertTrue($result->relationLoaded('customer'));
        $this->assertTrue($result->items[0]->relationLoaded('product'));
    }

    public function test_get_order_with_items_throws_exception_for_nonexistent_id()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->getOrderWithItems(999);
    }

    public function test_get_orders_by_status_filters_by_status()
    {
        Order::factory()->count(3)->create(['status' => 'pending']);
        Order::factory()->count(2)->create(['status' => 'processing']);
        Order::factory()->count(1)->create(['status' => 'shipped']);

        $result = $this->repository->getOrdersByStatus('pending');

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertCount(3, $result->items());

        // Verify all returned orders have the specified status
        foreach ($result->items() as $order) {
            $this->assertEquals('pending', $order->status);
        }
    }

    public function test_get_orders_by_status_includes_customer_and_items_data()
    {
        $order = Order::factory()->create(['status' => 'pending']);
        $product = Product::factory()->create();
        OrderItem::factory()->count(2)->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $result = $this->repository->getOrdersByStatus('pending');

        $this->assertTrue($result->items()[0]->relationLoaded('customer'));
        $this->assertTrue($result->items()[0]->relationLoaded('items'));
    }
}
