<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = OrderItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'product_name' => fake()->words(3, true),
            'product_sku' => fake()->ean13(),
            'price' => fake()->randomFloat(2, 10, 200),
            'quantity' => fake()->numberBetween(1, 5),
            'subtotal' => function (array $attributes) {
                return $attributes['price'] * $attributes['quantity'];
            },
            'variant_details' => null,
        ];
    }

    /**
     * Indicate that the order item has a variant.
     */
    public function withVariant(): static
    {
        return $this->state(fn (array $attributes) => [
            'product_variant_id' => ProductVariant::factory(),
            'variant_details' => fake()->words(2, true),
        ]);
    }
}
