<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductService;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\DatabaseSeeder'])->assertExitCode(0);
    }

    protected function authenticateUser(string $role = 'customer')
    {
        $user = User::factory()->create(['role' => $role]);
        $token = Auth::login($user);
        
        return [
            'user' => $user,
            'token' => $token
        ];
    }

    /** @test */
    public function authenticated_user_can_get_products_list()
    {
        $auth = $this->authenticateUser('customer');
        
        // Create some test products
        $products = Product::factory()->count(3)->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->getJson('/api/v1/products');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id', 'name', 'description', 'price', 'sku', 
                             'stock_quantity', 'low_stock_threshold', 'vendor_id',
                             'created_at', 'updated_at'
                         ]
                     ],
                     'current_page',
                     'per_page',
                     'total'
                 ]);
    }

    /** @test */
    public function user_can_filter_products()
    {
        $auth = $this->authenticateUser('customer');
        
        // Create test products
        $product1 = Product::factory()->create(['name' => 'Test Product 1', 'price' => 100]);
        $product2 = Product::factory()->create(['name' => 'Another Product', 'price' => 200]);

        // Test search filter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->getJson('/api/v1/products?search=Test');

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Test Product 1']);
    }

    /** @test */
    public function vendor_can_create_product()
    {
        $auth = $this->authenticateUser('vendor');
        
        $productData = [
            'name' => 'Test Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'sku' => 'TEST-SKU-001',
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'product' => [
                         'id', 'name', 'description', 'price', 'sku', 
                         'stock_quantity', 'low_stock_threshold', 'vendor_id'
                     ]
                 ])
                 ->assertJsonFragment([
                     'name' => 'Test Product',
                     'price' => 99.99
                 ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'vendor_id' => $auth['user']->id
        ]);
    }

    /** @test */
    public function vendor_cannot_create_product_with_duplicate_sku()
    {
        $auth = $this->authenticateUser('vendor');
        
        // Create a product first
        Product::factory()->create(['sku' => 'DUPLICATE-SKU']);

        $productData = [
            'name' => 'Duplicate Product',
            'description' => 'Test Description',
            'price' => 99.99,
            'sku' => 'DUPLICATE-SKU', // Duplicate SKU
            'stock_quantity' => 50,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/products', $productData);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['sku']);
    }

    /** @test */
    public function vendor_can_create_product_with_variants()
    {
        $auth = $this->authenticateUser('vendor');
        
        $productData = [
            'name' => 'T-Shirt',
            'description' => 'Cotton T-Shirt',
            'price' => 29.99,
            'sku' => 'TSHIRT-001',
            'stock_quantity' => 100,
            'variants' => [
                [
                    'name' => 'Size S - White',
                    'attributes' => ['size' => 'S', 'color' => 'White'],
                    'sku' => 'TSHIRT-001-S-WHITE',
                    'stock_quantity' => 20,
                    'price_modifier' => 0
                ],
                [
                    'name' => 'Size M - Black',
                    'attributes' => ['size' => 'M', 'color' => 'Black'],
                    'sku' => 'TSHIRT-001-M-BLACK',
                    'stock_quantity' => 30,
                    'price_modifier' => 5.00
                ]
            ]
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/products', $productData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'message',
                     'product' => [
                         'id', 'name', 'price', 'variants'
                     ]
                 ]);

        $this->assertDatabaseHas('products', ['name' => 'T-Shirt']);
        $this->assertDatabaseHas('product_variants', ['sku' => 'TSHIRT-001-S-WHITE']);
    }

    /** @test */
    public function customer_cannot_create_product()
    {
        $auth = $this->authenticateUser('customer');
        
        $productData = [
            'name' => 'Unauthorized Product',
            'price' => 99.99,
            'sku' => 'UNATH-001',
            'stock_quantity' => 50,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/products', $productData);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_get_product_details_with_variants()
    {
        $auth = $this->authenticateUser('customer');
        
        $product = Product::factory()->create();
        $variant = ProductVariant::factory()->create(['product_id' => $product->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->getJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'id', 'name', 'description', 'price', 'sku',
                     'stock_quantity', 'low_stock_threshold',
                     'variants' => [
                         '*' => [
                             'id', 'name', 'attributes', 'sku', 
                             'stock_quantity', 'price_modifier'
                         ]
                     ]
                 ])
                 ->assertJsonFragment(['id' => $product->id]);
    }

    /** @test */
    public function vendor_can_update_own_product()
    {
        $auth = $this->authenticateUser('vendor');
        
        $product = Product::factory()->create(['vendor_id' => $auth['user']->id]);

        $updateData = [
            'name' => 'Updated Product Name',
            'price' => 149.99,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->putJson('/api/v1/products/' . $product->id, $updateData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'product'
                 ])
                 ->assertJsonFragment([
                     'message' => 'Product updated successfully',
                     'name' => 'Updated Product Name'
                 ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Product Name',
            'price' => 149.99
        ]);
    }

    /** @test */
    public function vendor_cannot_update_others_product()
    {
        $auth = $this->authenticateUser('vendor');
        $otherVendor = User::factory()->create(['role' => 'vendor']);
        $otherProduct = Product::factory()->create(['vendor_id' => $otherVendor->id]);

        $updateData = ['name' => 'Hacked Product'];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->putJson('/api/v1/products/' . $otherProduct->id, $updateData);

        $response->assertStatus(403);
    }

    /** @test */
    public function vendor_can_delete_own_product()
    {
        $auth = $this->authenticateUser('vendor');
        
        $product = Product::factory()->create(['vendor_id' => $auth['user']->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->deleteJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Product deleted successfully']);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function admin_can_delete_any_product()
    {
        $auth = $this->authenticateUser('admin');
        $vendor = User::factory()->create(['role' => 'vendor']);
        $product = Product::factory()->create(['vendor_id' => $vendor->id]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->deleteJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    /** @test */
    public function vendor_can_import_products_from_csv()
    {
        Storage::fake('local');
        $auth = $this->authenticateUser('vendor');
        
        // Create a test CSV file
        $csvContent = "name,description,price,sku,stock_quantity\n";
        $csvContent .= "Imported Product 1,Description 1,99.99,IMPORT-001,50\n";
        $csvContent .= "Imported Product 2,Description 2,149.99,IMPORT-002,25\n";
        
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('products.csv', $csvContent);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/products/importCsv', [
            'file' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'message',
                     'result' => [
                         'total_processed',
                         'successful_imports',
                         'failed_imports',
                         'errors'
                     ]
                 ]);

        $this->assertDatabaseHas('products', ['name' => 'Imported Product 1']);
        $this->assertDatabaseHas('products', ['name' => 'Imported Product 2']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_products()
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }

    /** @test */
    public function validation_fails_for_missing_required_fields()
    {
        $auth = $this->authenticateUser('vendor');
        
        // Test missing name
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/products', [
            'price' => 99.99,
            'sku' => 'TEST-001',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function validation_fails_for_invalid_price()
    {
        $auth = $this->authenticateUser('vendor');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'price' => 'invalid-price', // Invalid price
            'sku' => 'TEST-001',
            'stock_quantity' => 50,
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['price']);
    }

    /** @test */
    public function validation_fails_for_negative_stock()
    {
        $auth = $this->authenticateUser('vendor');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $auth['token']
        ])->postJson('/api/v1/products', [
            'name' => 'Test Product',
            'price' => 99.99,
            'sku' => 'TEST-001',
            'stock_quantity' => -5, // Negative stock
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['stock_quantity']);
    }
}