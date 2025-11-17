<?php

namespace Tests\Unit\Actions;

use App\Actions\ProcessOrderAction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessOrderActionTest extends TestCase
{
    use RefreshDatabase;

    protected ProcessOrderAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new ProcessOrderAction();
    }

    public function test_execute_processes_pending_order_successfully()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->pending()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $result = $this->action->execute($order);

        $this->assertTrue($result);

        // Refresh the order from the database
        $order->refresh();
        $this->assertEquals('processing', $order->status);

        // Refresh the product from the database
        $product->refresh();
        $this->assertEquals(7, $product->stock); // 10 - 3
    }

    public function test_execute_processes_pending_order_with_variants_successfully()
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10
        ]);
        $order = Order::factory()->pending()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $result = $this->action->execute($order);

        $this->assertTrue($result);

        // Refresh the order from the database
        $order->refresh();
        $this->assertEquals('processing', $order->status);

        // Refresh the variant from the database
        $variant->refresh();
        $this->assertEquals(7, $variant->stock); // 10 - 3
    }

    public function test_execute_throws_exception_for_non_pending_order()
    {
        $order = Order::factory()->processing()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending orders can be processed');

        $this->action->execute($order);
    }

    public function test_execute_throws_exception_for_insufficient_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 2]);
        $order = Order::factory()->pending()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5, // More than available stock
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient stock for product: {$product->name}");

        $this->action->execute($order);
    }

    public function test_execute_throws_exception_for_insufficient_variant_stock()
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 2
        ]);
        $order = Order::factory()->pending()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 5, // More than available stock
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Insufficient stock for product: {$product->name} - {$variant->name}");

        $this->action->execute($order);
    }

    public function test_execute_handles_multiple_items_correctly()
    {
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 15]);
        $order = Order::factory()->pending()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 3,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 5,
        ]);

        $result = $this->action->execute($order);

        $this->assertTrue($result);

        // Refresh the products from the database
        $product1->refresh();
        $product2->refresh();
        $this->assertEquals(7, $product1->stock); // 10 - 3
        $this->assertEquals(10, $product2->stock); // 15 - 5

        // Refresh the order from the database
        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }
}
