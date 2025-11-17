<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        return [
            'vendor_id' => UserFactory::new(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'sku' => strtoupper(Str::random(10)),
            'stock_quantity' => $this->faker->numberBetween(1, 100),
            'low_stock_threshold' => $this->faker->numberBetween(1, 10),
            'is_active' => $this->faker->boolean(80),
            'image' => $this->faker->imageUrl(),
            'metadata' => json_encode([
                'brand' => $this->faker->company(),
                'category' => $this->faker->word(),
                'specifications' => [
                    'color' => $this->faker->colorName(),
                    'size' => $this->faker->randomElement(['S', 'M', 'L', 'XL']),
                ],
            ]),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($product) {
            // Create variants if needed
            if ($product->variants()->count() === 0) {
                $variants = [];
                for ($i = 0; $i < 3; $i++) {
                    $variants[] = ProductVariantFactory::new()->make([
                        'product_id' => $product->id,
                        'price' => $product->price + $this->faker->randomFloat(2, -20, 50),
                    ])->toArray();
                }
                $product->variants()->createMany($variants);
            }
        });
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_quantity' => 5,
            'low_stock_threshold' => 10,
        ]);
    }
}