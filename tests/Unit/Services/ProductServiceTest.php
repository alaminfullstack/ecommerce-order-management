<?php

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Mockery;

class ProductServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProductService $service;
    protected ProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = Mockery::mock(ProductRepository::class);
        $this->service = new ProductService($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_product_creates_product_without_variants()
    {
        $productData = Product::factory()->raw();
        $product = new Product($productData);
        $product->id = 1;

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($productData)
            ->andReturn($product);

        $result = $this->service->createProduct($productData);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($product->id, $result->id);
    }

    public function test_create_product_creates_product_with_variants()
    {
        $productData = Product::factory()->raw();
        $productData['variants'] = [
            ['name' => 'Variant 1', 'sku' => 'VAR1', 'price' => 10.99, 'stock' => 5],
            ['name' => 'Variant 2', 'sku' => 'VAR2', 'price' => 15.99, 'stock' => 10],
        ];

        $product = new Product($productData);
        $product->id = 1;
        $product->setRelation('variants', collect());

        $this->repository
            ->shouldReceive('create')
            ->once()
            ->with($productData)
            ->andReturn($product);

        $result = $this->service->createProduct($productData);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($product->id, $result->id);
    }

    public function test_update_product_updates_product_without_variants()
    {
        $productId = 1;
        $updateData = ['name' => 'Updated Product Name'];
        $product = Product::factory()->create(['id' => $productId]);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with($productId, $updateData)
            ->andReturn($product);

        $result = $this->service->updateProduct($productId, $updateData);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($product->id, $result->id);
    }

    public function test_update_product_updates_product_with_variants()
    {
        $productId = 1;
        $updateData = [
            'name' => 'Updated Product Name',
            'variants' => [
                ['id' => 1, 'name' => 'Updated Variant 1'],
                ['name' => 'New Variant'],
            ],
        ];
        $product = Product::factory()->create(['id' => $productId]);
        $product->setRelation('variants', collect());

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with($productId, $updateData)
            ->andReturn($product);

        $result = $this->service->updateProduct($productId, $updateData);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($product->id, $result->id);
    }

    public function test_delete_product_deletes_product()
    {
        $productId = 1;

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with($productId)
            ->andReturn(true);

        $result = $this->service->deleteProduct($productId);

        $this->assertTrue($result);
    }

    public function test_import_products_from_csv_imports_products_successfully()
    {
        $filePath = 'test_file.csv';
        $csvContent = "name,sku,price,stock
Product 1,SKU1,10.99,5
Product 2,SKU2,15.99,10";

        // Create a temporary CSV file
        file_put_contents($filePath, $csvContent);

        $this->repository
            ->shouldReceive('create')
            ->twice()
            ->andReturn(new Product());

        $result = $this->service->importProductsFromCsv($filePath);

        $this->assertEquals(2, $result['imported']);
        $this->assertEmpty($result['errors']);

        // Clean up the temporary file
        unlink($filePath);
    }

    public function test_import_products_from_csv_handles_errors()
    {
        $filePath = 'test_file.csv';
        $csvContent = "name,sku,price,stock
Product 1,SKU1,10.99,5
Invalid Row
Product 2,SKU2,15.99,10";

        // Create a temporary CSV file
        file_put_contents($filePath, $csvContent);

        $this->repository
            ->shouldReceive('create')
            ->twice()
            ->andReturn(new Product());

        $result = $this->service->importProductsFromCsv($filePath);

        $this->assertEquals(2, $result['imported']);
        $this->assertCount(1, $result['errors']);

        // Clean up the temporary file
        unlink($filePath);
    }

    public function test_check_low_stock_dispatches_job_for_low_stock_products()
    {
        Queue::fake();

        $lowStockProduct = Product::factory()->make();
        $lowStockProducts = collect([$lowStockProduct]);

        $this->repository
            ->shouldReceive('getLowStockProducts')
            ->once()
            ->andReturn($lowStockProducts);

        $this->service->checkLowStock();

        Queue::assertPushed(\App\Jobs\CheckLowStockJob::class, function ($job) use ($lowStockProduct) {
            return $job->product->id === $lowStockProduct->id;
        });
    }
}
