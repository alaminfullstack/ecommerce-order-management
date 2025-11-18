<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Don't seed the database for OrderTest to avoid interference
        // with test isolation
    }

    protected function createTestOrder($customer, $products = null)
    {
        if (!$products) {
            $products = Product::factory()->count(2)->create();
        }

        $order = Order::create([
            'customer_id' => $customer->id,
            'status' => 'pending',
            'total' => 0,
            'shipping_address' => '123 Test Street, Test City, State 12345'
        ]);

        $totalAmount = 0;
        foreach ($products as $product) {
            $quantity = 1;
            $price = $product->price;
            $subtotal = $price * $quantity;
            $totalAmount += $subtotal;

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'quantity' => $quantity,
                'price' => $price,
                'subtotal' => $subtotal
            ]);
        }

        $order->update(['total' => $totalAmount]);
        
        return $order;
    }

    protected function authenticateUser(string $role = 'customer')
    {
        $user = User::factory()->create(['role' => $role]);
        $token = Auth::guard('api')->login($user);
        
        return [
            'user' => $user,
            'token' => $token
        ];
    }



    /** @test */
    public function customer_can_create_order()
    {
        $auth = $this->authenticateUser('customer');
        $products = Product::factory()->count(2)->create();
        
        $orderData = [
            'items' => [
                [
                    'product_id' => $products[0]->id,
                    'quantity' => 2,
                    'price' => $products[0]->price
                ],
                [
                    'product_id' => $products[1]->id,
                    'quantity' => 1,
                    'price' => $products[1]->price
                ]
            ],
            'shipping_address' => '123 Main St, Anytown, State 12345'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/orders', $orderData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'order' => [
                         'id', 'customer_id', 'status', 'total_amount',
                         'items' => [
                             '*' => [
                                 'id', 'product_id', 'quantity', 
                                 'price', 'subtotal'
                             ]
                         ],
                         'created_at'
                     ]
                 ]);

        $this->assertDatabaseHas('orders', [
            'customer_id' => $auth['user']->id,
            'status' => 'pending'
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $products[0]->id,
            'quantity' => 2
        ]);
    }

    /** @test */
    public function customer_cannot_create_order_with_invalid_product()
    {
        $auth = $this->authenticateUser('customer');
        
        $orderData = [
            'items' => [
                [
                    'product_id' => 99999, // Non-existent product
                    'quantity' => 2,
                    'price' => 99.99
                ]
            ],
            'shipping_address' => '123 Main St, Anytown, State 12345'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/orders', $orderData);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['items.0.product_id']]);
    }

    /** @test */
    public function customer_can_view_own_orders()
    {
        $auth = $this->authenticateUser('customer');
        
        // Create orders for this customer
        $this->createTestOrder($auth['user']);
        $this->createTestOrder($auth['user']);
        
        // Create order for another customer
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $this->createTestOrder($otherCustomer);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->getJson('/api/v1/orders');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id', 'customer_id', 'status', 'total_amount',
                             'created_at'
                         ]
                     ]
                 ]);

        // Verify we only see our own orders
        $orders = $response->json('data');
        $this->assertCount(2, $orders); // Only 2 orders for this customer
        foreach ($orders as $order) {
            $this->assertEquals($auth['user']->id, $order['customer_id']);
        }
    }

    /** @test */
    public function vendor_can_view_orders_for_their_products()
    {
        $auth = $this->authenticateUser('vendor');
        
        // Create products for this vendor
        $vendorProducts = Product::factory()->count(2)->create(['vendor_id' => $auth['user']->id]);
        
        // Create a customer and orders
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createTestOrder($customer, $vendorProducts);

        // Create order with products from another vendor
        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $otherProducts = Product::factory()->count(2)->create(['vendor_id' => $otherVendor->id]);
        $this->createTestOrder($customer, $otherProducts);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->getJson('/api/v1/orders');

        $response->assertStatus(200);
        
        // Verify we only see orders containing our products
        $orders = $response->json('data');
        $vendorOrderIds = $order->id; // Only the first order should be visible
        
        foreach ($orders as $order) {
            $this->assertEquals($vendorOrderIds, $order['id']);
        }
    }

    /** @test */
    public function admin_can_view_all_orders()
    {
        $auth = $this->authenticateUser('admin');
        
        // Create orders for different customers
        $customer1 = User::factory()->create(['role' => 'customer']);
        $customer2 = User::factory()->create(['role' => 'customer']);
        
        $order1 = $this->createTestOrder($customer1);
        $order2 = $this->createTestOrder($customer2);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->getJson('/api/v1/orders');

        $response->assertStatus(200);
        
        // Admin should see all orders
        $orders = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($orders)); // Should see at least 2 orders
    }

    /** @test */
    public function customer_can_view_order_details()
    {
        $auth = $this->authenticateUser('customer');
        $order = $this->createTestOrder($auth['user']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->getJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'customer_id', 'status', 'total_amount',
                     'items' => [
                         '*' => [
                             'id', 'product_id', 'quantity', 
                             'price', 'subtotal', 'product'
                         ]
                     ],
                     'created_at'
                 ])
                 ->assertJsonFragment(['id' => $order->id]);
    }

    /** @test */
    public function customer_cannot_view_others_orders()
    {
        $auth = $this->authenticateUser('customer');
        
        // Create order for another customer
        $otherCustomer = User::factory()->create(['role' => 'customer']);
        $otherOrder = $this->createTestOrder($otherCustomer);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->getJson('/api/v1/orders/' . $otherOrder->id);

        $response->assertStatus(403)
                 ->assertJson(['error' => 'Unauthorized']);
    }

    /** @test */
    public function customer_cannot_confirm_cancelled_order()
    {
        $auth = $this->authenticateUser('customer');
        $order = $this->createTestOrder($auth['user']);
        
        // Cancel the order first
        $order->update(['status' => 'cancelled']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->patchJson('/api/v1/orders/' . $order->id . '/confirm');

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Cannot confirm cancelled order']);
    }

    /** @test */
    public function admin_can_update_order_status()
    {
        $auth = $this->authenticateUser('admin');
        $order = $this->createTestOrder(
            User::factory()->create(['role' => 'customer'])
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->patchJson('/api/v1/orders/' . $order->id . '/status', [
            'status' => 'processing'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'order'
                 ])
                 ->assertJsonFragment([
                     'message' => 'Order status updated successfully',
                     'status' => 'processing'
                 ]);

        $order->refresh();
        $this->assertEquals('processing', $order->status);
    }

    /** @test */
    public function vendor_cannot_update_order_status()
    {
        $auth = $this->authenticateUser('vendor');
        $order = $this->createTestOrder(
            User::factory()->create(['role' => 'customer'])
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->patchJson('/api/v1/orders/' . $order->id . '/status', [
            'status' => 'processing'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_can_cancel_pending_order()
    {
        $auth = $this->authenticateUser('customer');
        $order = $this->createTestOrder($auth['user']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->deleteJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Order cancelled successfully']);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    /** @test */
    public function customer_cannot_cancel_shipped_order()
    {
        $auth = $this->authenticateUser('customer');
        $order = $this->createTestOrder($auth['user']);
        
        // Change status to shipped
        $order->update(['status' => 'shipped']);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->deleteJson('/api/v1/orders/' . $order->id);

        $response->assertStatus(400)
                 ->assertJson(['error' => 'Cannot cancel order that is already shipped']);
    }

    /** @test */
    public function admin_can_cancel_any_order()
    {
        $auth = $this->authenticateUser('admin');
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->createTestOrder($customer);
        
        // Order starts as 'pending', which can be cancelled

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->patchJson('/api/v1/orders/' . $order->id . '/status', [
            'status' => 'cancelled'
        ]);

        $response->assertStatus(200);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
    }

    /** @test */
    public function validation_fails_for_order_without_items()
    {
        $auth = $this->authenticateUser('customer');
        
        $orderData = [
            'items' => [], // Empty items
            'shipping_address' => '123 Main St, Anytown, State 12345'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/orders', $orderData);

        $response->assertStatus(422)
                 ->assertJsonStructure(['errors' => ['items']]);
    }

    /** @test */
    public function validation_fails_for_invalid_status_transition()
    {
        $auth = $this->authenticateUser('admin');
        $order = $this->createTestOrder(
            User::factory()->create(['role' => 'customer'])
        );

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->patchJson('/api/v1/orders/' . $order->id . '/status', [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['status']);
    }

    /** @test */
    public function unauthenticated_user_cannot_create_order()
    {
        $products = Product::factory()->count(2)->create();
        
        $orderData = [
            'items' => [
                [
                    'product_id' => $products[0]->id,
                    'quantity' => 1,
                    'price' => $products[0]->price
                ]
            ]
        ];

        $response = $this->postJson('/api/v1/orders', $orderData);

        $response->assertStatus(401);
    }

    /** @test */
    public function order_calculation_is_correct()
    {
        $auth = $this->authenticateUser('customer');
        $products = Product::factory()->createMany([
            ['price' => 100, 'stock_quantity' => 10],
            ['price' => 50, 'stock_quantity' => 20]
        ]);
        
        $orderData = [
            'items' => [
                [
                    'product_id' => $products[0]->id,
                    'quantity' => 2,
                    'price' => $products[0]->price
                ],
                [
                    'product_id' => $products[1]->id,
                    'quantity' => 3,
                    'price' => $products[1]->price
                ]
            ],
            'shipping_address' => '123 Main St, Anytown, State 12345'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/orders', $orderData);

        $response->assertStatus(201);
        
        $subtotal = (100 * 2) + (50 * 3); // 200 + 150 = 350
        $tax = $subtotal * 0.1; // 10% tax = 35
        $totalAmount = $subtotal + $tax; // 350 + 35 = 385

        // Get the order data from the response
        $orderData = $response->json('order');

        // Assert that the total_amount matches our calculation
        $this->assertEquals(number_format($totalAmount, 2), $orderData['total_amount']);
    }
}