<?php

namespace Tests\Unit\Repositories;

use App\Models\Product;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected BaseRepository $repository;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new BaseRepository(new Product());
        $this->product = Product::factory()->create();
    }

    public function test_all_returns_all_records()
    {
        Product::factory()->count(3)->create();

        $result = $this->repository->all();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(4, $result); // 3 created + 1 in setUp
    }

    public function test_find_returns_record_by_id()
    {
        $result = $this->repository->find($this->product->id);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($this->product->id, $result->id);
    }

    public function test_find_throws_exception_for_nonexistent_id()
    {
        $this->expectException(ModelNotFoundException::class);

        $this->repository->find(999);
    }

    public function test_create_creates_new_record()
    {
        $data = Product::factory()->raw();

        $result = $this->repository->create($data);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertDatabaseHas('products', [
            'name' => $data['name'],
            'sku' => $data['sku'],
        ]);
    }

    public function test_update_updates_existing_record()
    {
        $newData = [
            'name' => 'Updated Product Name',
            'price' => 99.99,
        ];

        $result = $this->repository->update($this->product->id, $newData);

        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals($newData['name'], $result->name);
        $this->assertEquals($newData['price'], $result->price);
        $this->assertDatabaseHas('products', [
            'id' => $this->product->id,
            'name' => $newData['name'],
            'price' => $newData['price'],
        ]);
    }

    public function test_delete_deletes_record()
    {
        $result = $this->repository->delete($this->product->id);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('products', [
            'id' => $this->product->id,
        ]);
    }

    public function test_paginate_returns_paginated_results()
    {
        Product::factory()->count(20)->create();

        $result = $this->repository->paginate(5);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
        $this->assertEquals(5, $result->perPage());
        $this->assertTrue($result->hasMorePages());
    }
}
