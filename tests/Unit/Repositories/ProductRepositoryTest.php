<?php

namespace Tests\Unit\Repositories;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ProductRepository $repository;
    protected User $vendor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new ProductRepository(new Product());
        $this->vendor = User::factory()->create();
    }

    public function test_search_products_returns_paginated_results()
    {
        Product::factory()->count(20)->create(['vendor_id' => $this->vendor->id]);

        $result = $this->repository->searchProducts([], 5);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->perPage());
    }

    public function test_search_products_filters_by_search_term()
    {
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Special Widget',
            'status' => 'active'
        ]);
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'name' => 'Regular Gadget',
            'status' => 'active'
        ]);

        $result = $this->repository->searchProducts(['search' => 'Special']);

        $this->assertCount(1, $result->items());
        $this->assertEquals('Special Widget', $result->items()[0]->name);
    }

    public function test_search_products_filters_by_vendor_id()
    {
        $vendor2 = User::factory()->create();
        Product::factory()->count(5)->create(['vendor_id' => $this->vendor->id]);
        Product::factory()->count(3)->create(['vendor_id' => $vendor2->id]);

        $result = $this->repository->searchProducts(['vendor_id' => $this->vendor->id]);

        $this->assertCount(5, $result->items());
        foreach ($result->items() as $product) {
            $this->assertEquals($this->vendor->id, $product->vendor_id);
        }
    }

    public function test_search_products_filters_by_price_range()
    {
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'price' => 10.00,
            'status' => 'active'
        ]);
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'price' => 50.00,
            'status' => 'active'
        ]);
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'price' => 100.00,
            'status' => 'active'
        ]);

        $result = $this->repository->searchProducts([
            'min_price' => 20.00,
            'max_price' => 80.00
        ]);

        $this->assertCount(1, $result->items());
        $this->assertEquals(50.00, $result->items()[0]->price);
    }

    public function test_get_low_stock_products_returns_only_low_stock_items()
    {
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'stock_quantity' => 10,
            'is_active' => true
        ]);
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'stock_quantity' => 3,
            'is_active' => true
        ]);
        Product::factory()->create([
            'vendor_id' => $this->vendor->id,
            'stock_quantity' => 0,
            'is_active' => true
        ]);

        $result = $this->repository->getLowStockProducts();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
        foreach ($result as $product) {
            $this->assertLessThanOrEqual(5, $product->stock);
        }
    }

    public function test_get_product_with_variants_includes_variants()
    {
        $product = Product::factory()->create(['vendor_id' => $this->vendor->id]);
        ProductVariant::factory()->count(3)->create(['product_id' => $product->id]);

        $result = $this->repository->getProductWithVariants($product->id);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertCount(3, $result->variants);
        $this->assertEquals($product->id, $result->id);
    }

    public function test_get_product_with_variants_throws_exception_for_nonexistent_id()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->getProductWithVariants(999);
    }
}
