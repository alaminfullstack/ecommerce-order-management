<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\InventoryLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StockReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendor;
    private User $customer;
    private array $products;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->vendor = User::factory()->create(['role' => 'vendor']);
        $this->customer = User::factory()->create(['role' => 'customer']);

        // Create test products for vendor
        $this->products = [
            Product::factory()->create([
                'vendor_id' => $this->vendor->id,
                'stock_quantity' => 100,
                'low_stock_threshold' => 20,
                'price' => 29.99,
            ]),
            Product::factory()->create([
                'vendor_id' => $this->vendor->id,
                'stock_quantity' => 5,
                'low_stock_threshold' => 10,
                'price' => 19.99,
            ]),
            Product::factory()->create([
                'vendor_id' => $this->admin->id,
                'stock_quantity' => 0,
                'low_stock_threshold' => 15,
                'price' => 39.99,
            ]),
        ];

        // Create product variants for the first product
        ProductVariant::factory()->create([
            'product_id' => $this->products[0]->id,
            'stock_quantity' => 20,
            'price' => 34.99,
        ]);

        ProductVariant::factory()->create([
            'product_id' => $this->products[0]->id,
            'stock_quantity' => 0,
            'price' => 39.99,
        ]);

        // Create some inventory logs
        InventoryLog::factory()->create([
            'inventoriable_type' => 'App\Models\Product',
            'inventoriable_id' => $this->products[0]->id,
            'type' => 'increase',
            'quantity_before' => 90,
            'quantity_after' => 100,
            'quantity_changed' => 10,
            'reason' => 'restock',
            'user_id' => $this->vendor->id,
            'created_at' => now()->subDays(5),
        ]);

        InventoryLog::factory()->create([
            'inventoriable_type' => 'App\Models\Product',
            'inventoriable_id' => $this->products[1]->id,
            'type' => 'decrease',
            'quantity_before' => 10,
            'quantity_after' => 5,
            'quantity_changed' => 5,
            'reason' => 'order',
            'user_id' => $this->admin->id,
            'created_at' => now()->subDays(2),
        ]);
    }

    public function test_admin_can_access_stock_report()
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/products/stock-report');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => [
                'total_products',
                'low_stock_products',
                'out_of_stock_products',
                'total_stock_value',
                'total_stock_quantity',
                'stock_movements',
            ],
            'products' => [
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
            'stock_movements',
        ]);

        // Admin should see all products
        $this->assertEquals(3, $response->json('summary.total_products'));
        $this->assertEquals(1, $response->json('summary.low_stock_products'));
        $this->assertEquals(1, $response->json('summary.out_of_stock_products'));
        $this->assertEquals(2, $response->json('summary.stock_movements'));
    }

    public function test_vendor_can_access_stock_report()
    {
        Sanctum::actingAs($this->vendor);

        $response = $this->getJson('/api/v1/products/stock-report');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'summary' => [
                'total_products',
                'low_stock_products',
                'out_of_stock_products',
                'total_stock_value',
                'total_stock_quantity',
                'stock_movements',
            ],
            'products' => [
                'data',
                'current_page',
                'last_page',
                'per_page',
                'total',
            ],
            'stock_movements',
        ]);

        // Vendor should only see their own products
        $this->assertEquals(2, $response->json('summary.total_products'));
        $this->assertEquals(1, $response->json('summary.low_stock_products'));
        $this->assertEquals(0, $response->json('summary.out_of_stock_products'));
        $this->assertEquals(1, $response->json('summary.stock_movements'));
    }

    public function test_customer_cannot_access_stock_report()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/v1/products/stock-report');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_stock_report()
    {
        $response = $this->getJson('/api/v1/products/stock-report');

        $response->assertStatus(401);
    }

    public function test_stock_report_with_product_filter()
    {
        Sanctum::actingAs($this->admin);

        // Filter by a specific product
        $response = $this->getJson('/api/v1/products/stock-report?product_id=' . $this->products[0]->id);

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('summary.total_products'));
        $this->assertEquals(0, $response->json('summary.low_stock_products'));
        $this->assertEquals(0, $response->json('summary.out_of_stock_products'));
        $this->assertEquals(1, $response->json('summary.stock_movements'));
    }

    public function test_stock_report_with_date_range_filter()
    {
        Sanctum::actingAs($this->admin);

        // Filter by date range
        $response = $this->getJson('/api/v1/products/stock-report?start_date=' . now()->subDays(3)->format('Y-m-d'));

        $response->assertStatus(200);
        // Should only include stock movements from the last 3 days
        $this->assertEquals(1, $response->json('summary.stock_movements'));
    }

    public function test_stock_report_with_variants()
    {
        Sanctum::actingAs($this->admin);

        // Include variants in the report
        $response = $this->getJson('/api/v1/products/stock-report?include_variants=1');

        $response->assertStatus(200);

        // Check that variants are included
        $productData = $response->json('products.data');
        $productWithVariants = collect($productData)->firstWhere('id', $this->products[0]->id);
        $this->assertArrayHasKey('variants', $productWithVariants);
        $this->assertCount(2, $productWithVariants['variants']);
    }

    public function test_stock_report_with_per_page_filter()
    {
        Sanctum::actingAs($this->admin);

        // Set per_page to 1
        $response = $this->getJson('/api/v1/products/stock-report?per_page=1');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('products.per_page'));
        $this->assertGreaterThan(1, $response->json('products.last_page'));
    }

    public function test_stock_report_validation()
    {
        Sanctum::actingAs($this->admin);

        // Test invalid product_id
        $response = $this->getJson('/api/v1/products/stock-report?product_id=999');
        $response->assertStatus(422);

        // Test invalid date range
        $response = $this->getJson('/api/v1/products/stock-report?start_date=2023-01-01&end_date=2022-01-01');
        $response->assertStatus(422);

        // Test invalid per_page
        $response = $this->getJson('/api/v1/products/stock-report?per_page=0');
        $response->assertStatus(422);

        $response = $this->getJson('/api/v1/products/stock-report?per_page=101');
        $response->assertStatus(422);
    }
}
