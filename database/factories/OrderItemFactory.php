<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quantity' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'subtotal' => $this->faker->randomFloat(2, 10, 500),
            'product_name' => $this->faker->word(),
            'product_sku' => strtoupper(Str::random(8)),
            'variant_details' => json_encode([
                'size' => 'M',
                'color' => 'Black'
            ]),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($orderItem) {
            // Calculate subtotal based on quantity and price
            if (!$orderItem->subtotal) {
                $orderItem->update([
                    'subtotal' => $orderItem->quantity * $orderItem->price
                ]);
            }
        });
    }

    public function single(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 1,
        ]);
    }

    public function multiple(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => $this->faker->numberBetween(2, 5),
        ]);
    }
}