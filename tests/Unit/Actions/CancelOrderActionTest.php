<?php

namespace Tests\Unit\Actions;

use App\Actions\CancelOrderAction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancelOrderActionTest extends TestCase
{
    use RefreshDatabase;

    protected CancelOrderAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = new CancelOrderAction();
    }

    public function test_execute_cancels_pending_order_successfully()
    {
        $order = Order::factory()->pending()->create();

        $result = $this->action->execute($order);

        $this->assertTrue($result);

        // Refresh the order from the database
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    public function test_execute_cancels_processing_order_and_restores_stock()
    {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $order = Order::factory()->processing()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ]);

        $result = $this->action->execute($order);

        $this->assertTrue($result);

        // Refresh the order from the database
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);

        // Refresh the product from the database
        $product->refresh();
        $this->assertEquals(13, $product->stock); // 10 + 3
    }

    public function test_execute_cancels_processing_order_with_variants_and_restores_stock()
    {
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 10
        ]);
        $order = Order::factory()->processing()->create();
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
        $this->assertEquals('cancelled', $order->status);

        // Refresh the variant from the database
        $variant->refresh();
        $this->assertEquals(13, $variant->stock); // 10 + 3
    }

    public function test_execute_throws_exception_for_shipped_order()
    {
        $order = Order::factory()->shipped()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order cannot be cancelled in current status');

        $this->action->execute($order);
    }

    public function test_execute_throws_exception_for_delivered_order()
    {
        $order = Order::factory()->delivered()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Order cannot be cancelled in current status');

        $this->action->execute($order);
    }

    public function test_execute_handles_multiple_items_correctly()
    {
        $product1 = Product::factory()->create(['stock_quantity' => 10]);
        $product2 = Product::factory()->create(['stock_quantity' => 15]);
        $order = Order::factory()->processing()->create();
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
        $this->assertEquals(13, $product1->stock); // 10 + 3
        $this->assertEquals(20, $product2->stock); // 15 + 5

        // Refresh the order from the database
        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    public function test_execute_does_not_restore_stock_for_pending_order()
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
        $this->assertEquals('cancelled', $order->status);

        // Refresh the product from the database
        $product->refresh();
        $this->assertEquals(10, $product->stock); // Stock should not change
    }
}
