<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'customer_id' => UserFactory::new(),
            'vendor_id' => UserFactory::new(),
            'order_number' => strtoupper(Str::random(10)),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled']),
            'subtotal' => $this->faker->randomFloat(2, 50, 1000),
            'tax' => $this->faker->randomFloat(2, 5, 50),
            'shipping' => $this->faker->randomFloat(2, 10, 30),
            'total' => $this->faker->randomFloat(2, 50, 1000),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed', 'refunded']),
            'payment_method' => $this->faker->optional()->randomElement(['stripe', 'paypal', 'bank_transfer']),
            'shipping_address' => json_encode([
                'name' => $this->faker->name(),
                'address_line_1' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'postal_code' => $this->faker->postcode(),
                'country' => 'United States',
                'phone' => $this->faker->phoneNumber()
            ]),
            'billing_address' => null,
            'notes' => $this->faker->optional()->sentence(),
            'confirmed_at' => null,
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
            'metadata' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function ($order) {
            // Create order items with required fields
            $itemCount = rand(1, 3);
            for ($i = 0; $i < $itemCount; $i++) {
                $product = ProductFactory::new()->create();
                $variant = ProductVariantFactory::new()->create(['product_id' => $product->id]);
                
                $order->items()->create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => 'Test Product',
                    'product_sku' => 'TEST-SKU-' . uniqid(),
                    'price' => $this->faker->randomFloat(2, 10, 100),
                    'quantity' => rand(1, 5),
                    'subtotal' => $this->faker->randomFloat(2, 10, 500),
                    'variant_details' => json_encode(['size' => 'M', 'color' => 'Black']),
                ]);
            }
        });
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'shipped',
            'shipped_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'delivered',
            'shipped_at' => now()->subDays(2),
            'delivered_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }
}