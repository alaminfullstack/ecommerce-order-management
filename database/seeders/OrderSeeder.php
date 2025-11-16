<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customers
        $customers = User::where('role', 'customer')->get();
        
        if ($customers->isEmpty()) {
            $this->command->error('Customer users not found. Please run UserSeeder first.');
            return;
        }

        // Get products and variants
        $products = Product::with('variants')->get();
        
        if ($products->isEmpty()) {
            $this->command->error('Products not found. Please run ProductSeeder first.');
            return;
        }

        // Sample shipping addresses
        $shippingAddresses = [
            [
                'name' => 'John Doe',
                'address_line_1' => '123 Main Street',
                'address_line_2' => 'Apt 4B',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'United States',
                'phone' => '+1-555-0123'
            ],
            [
                'name' => 'Jane Smith',
                'address_line_1' => '456 Oak Avenue',
                'address_line_2' => 'Suite 200',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'postal_code' => '90210',
                'country' => 'United States',
                'phone' => '+1-555-0456'
            ],
            [
                'name' => 'Mike Johnson',
                'address_line_1' => '789 Pine Road',
                'address_line_2' => '',
                'city' => 'Chicago',
                'state' => 'IL',
                'postal_code' => '60601',
                'country' => 'United States',
                'phone' => '+1-555-0789'
            ],
            [
                'name' => 'Sarah Wilson',
                'address_line_1' => '321 Elm Street',
                'address_line_2' => 'Unit 12',
                'city' => 'Miami',
                'state' => 'FL',
                'postal_code' => '33101',
                'country' => 'United States',
                'phone' => '+1-555-0321'
            ],
            [
                'name' => 'David Brown',
                'address_line_1' => '654 Maple Drive',
                'address_line_2' => 'Floor 3',
                'city' => 'Seattle',
                'state' => 'WA',
                'postal_code' => '98101',
                'country' => 'United States',
                'phone' => '+1-555-0654'
            ]
        ];

        $orderStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $orderWeights = [20, 25, 20, 20, 15]; // More likely to have pending/processing orders

        // Create 50 sample orders
        for ($i = 1; $i <= 50; $i++) {
            $customer = $customers->random();
            $shippingAddress = collect($shippingAddresses)->random();
            $status = $this->weightedRandomChoice($orderStatuses, $orderWeights);
            
            // Create order
            $order = Order::create([
                'order_number' => 'ORD-' . str_pad($i, 6, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'status' => $status,
                'subtotal' => 0, // Will be calculated
                'tax' => 0, // Will be calculated
                'shipping' => 9.99,
                'total' => 0, // Will be calculated
                'shipping_address' => json_encode($shippingAddress),
                'billing_address' => json_encode($shippingAddress),
                'notes' => $status === 'cancelled' ? 'Customer requested cancellation' : null,
                'created_at' => Carbon::now()->subDays(rand(1, 30)),
            ]);

            // Create order items (1-5 items per order)
            $itemCount = rand(1, 5);
            $orderSubtotal = 0;
            $taxRate = 0.08; // 8% tax

            for ($j = 0; $j < $itemCount; $j++) {
                $product = $products->random();
                $variant = $product->variants->random();
                
                $quantity = rand(1, 3);
                $price = $variant->price;
                $itemSubtotal = $price * $quantity;
                $orderSubtotal += $itemSubtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'product_sku' => $variant->sku,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                    'variant_details' => $variant->attributes,
                ]);

                // Reduce variant stock (simulate order)
                if (in_array($status, ['processing', 'shipped', 'delivered'])) {
                    $variant->decrement('stock_quantity', $quantity);
                }
            }

            // Calculate totals
            $tax = $orderSubtotal * $taxRate;
            $total = $orderSubtotal + $tax + 9.99;

            $order->update([
                'subtotal' => $orderSubtotal,
                'tax' => $tax,
                'total' => $total,
            ]);

            // Set status-specific timestamps
            $createdAt = $order->created_at;
            
            if ($status === 'processing') {
                $order->update(['confirmed_at' => $createdAt->copy()->addMinutes(30)]);
            } elseif ($status === 'shipped') {
                $order->update([
                    'confirmed_at' => $createdAt->copy()->addMinutes(30),
                    'shipped_at' => $createdAt->copy()->addDays(rand(1, 3)),
                ]);
            } elseif ($status === 'delivered') {
                $order->update([
                    'confirmed_at' => $createdAt->copy()->addMinutes(30),
                    'shipped_at' => $createdAt->copy()->addDays(rand(1, 2)),
                    'delivered_at' => $createdAt->copy()->addDays(rand(4, 7)),
                ]);
            } elseif ($status === 'cancelled') {
                $order->update([
                    'cancelled_at' => $createdAt->copy()->addHours(rand(2, 24)),
                ]);
            }
        }

        // Create some specific scenario orders for testing
        
        // Low stock order (will trigger low stock alert)
        $laptop = Product::where('name', 'MacBook Pro 14"')->first();
        if ($laptop) {
            $variant = $laptop->variants()->first();
            $variant->update(['stock_quantity' => 5]); // Below low stock threshold
            
            Order::create([
                'order_number' => 'ORD-000051',
                'customer_id' => $customers->first()->id,
                'status' => 'processing',
                'subtotal' => 1999.00,
                'tax' => 159.92,
                'shipping' => 9.99,
                'total' => 2168.91,
                'shipping_address' => json_encode($shippingAddresses[0]),
                'billing_address' => json_encode($shippingAddresses[0]),
                'confirmed_at' => now(),
                'created_at' => Carbon::now()->subDays(1),
            ]);
            
            OrderItem::create([
                'order_id' => Order::where('order_number', 'ORD-000051')->first()->id,
                'product_id' => $laptop->id,
                'product_variant_id' => $variant->id,
                'product_name' => $laptop->name,
                'product_sku' => $variant->sku,
                'price' => 1999.00,
                'quantity' => 1,
                'subtotal' => 1999.00,
                'variant_details' => $variant->attributes,
            ]);
            
            $variant->decrement('stock_quantity', 1);
        }

        // Large quantity order (inventory stress test)
        $tShirt = Product::where('name', 'Premium Cotton T-Shirt')->first();
        if ($tShirt) {
            $variant = $tShirt->variants()->where('attributes->size', 'L')->where('attributes->color', 'White')->first();
            
            $order = Order::create([
                'order_number' => 'ORD-000052',
                'customer_id' => $customers->skip(1)->first()->id,
                'status' => 'pending',
                'subtotal' => 299.90,
                'tax' => 23.99,
                'shipping' => 9.99,
                'total' => 333.88,
                'shipping_address' => json_encode($shippingAddresses[1]),
                'billing_address' => json_encode($shippingAddresses[1]),
                'created_at' => Carbon::now()->subHours(2),
            ]);
            
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $tShirt->id,
                'product_variant_id' => $variant->id,
                'product_name' => $tShirt->name,
                'product_sku' => $variant->sku,
                'price' => 29.99,
                'quantity' => 10,
                'subtotal' => 299.90,
                'variant_details' => $variant->attributes,
            ]);
        }
    }

    /**
     * Weighted random selection
     */
    private function weightedRandomChoice($choices, $weights)
    {
        $totalWeight = array_sum($weights);
        $randomWeight = rand(1, $totalWeight);
        
        $currentWeight = 0;
        foreach ($choices as $index => $choice) {
            $currentWeight += $weights[$index];
            if ($randomWeight <= $currentWeight) {
                return $choice;
            }
        }
        
        return $choices[0]; // Fallback
    }
}
