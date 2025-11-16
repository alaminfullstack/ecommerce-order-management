<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get vendor IDs
        $electronicsVendor = User::where('email', 'vendor@electronics.com')->first();
        $fashionVendor = User::where('email', 'vendor@fashion.com')->first();
        $sportsVendor = User::where('email', 'vendor@sports.com')->first();

        if (!$electronicsVendor || !$fashionVendor || !$sportsVendor) {
            $this->command->error('Vendor users not found. Please run UserSeeder first.');
            return;
        }

        // Electronics Products with Variants
        $laptop = Product::create([
            'vendor_id' => $electronicsVendor->id,
            'name' => 'MacBook Pro 14"',
            'slug' => 'macbook-pro-14',
            'description' => 'The most powerful MacBook Pro ever is here. With the blazing-fast M2 Pro chip — built on the next-generation 5nm process technology — MacBook Pro delivers exceptional performance and amazing battery life.',
            'price' => 1999.00,
            'sku' => 'MBP-14-001',
            'stock_quantity' => 50,
            'low_stock_threshold' => 10,
            'is_active' => true,
            'image' => 'products/macbook-pro-14.jpg',
            'metadata' => [
                'brand' => 'Apple',
                'category' => 'Laptops',
                'specifications' => [
                    'processor' => 'Apple M2 Pro',
                    'memory' => '16GB RAM',
                    'storage' => '512GB SSD',
                    'display' => '14.2" Liquid Retina XDR'
                ]
            ]
        ]);

        // Laptop Variants
        ProductVariant::create([
            'product_id' => $laptop->id,
            'sku' => 'MBP-14-512-16',
            'name' => 'MacBook Pro 14" - 512GB / 16GB RAM',
            'price' => 1999.00,
            'stock_quantity' => 20,
            'attributes' => [
                'storage' => '512GB',
                'memory' => '16GB RAM',
                'color' => 'Space Gray'
            ],
            'is_active' => true
        ]);

        ProductVariant::create([
            'product_id' => $laptop->id,
            'sku' => 'MBP-14-512-16-SG',
            'name' => 'MacBook Pro 14" - 512GB / 16GB RAM - Silver',
            'price' => 1999.00,
            'stock_quantity' => 15,
            'attributes' => [
                'storage' => '512GB',
                'memory' => '16GB RAM',
                'color' => 'Silver'
            ],
            'is_active' => true
        ]);

        ProductVariant::create([
            'product_id' => $laptop->id,
            'sku' => 'MBP-14-1TB-16',
            'name' => 'MacBook Pro 14" - 1TB / 16GB RAM - Space Gray',
            'price' => 2499.00,
            'stock_quantity' => 15,
            'attributes' => [
                'storage' => '1TB',
                'memory' => '16GB RAM',
                'color' => 'Space Gray'
            ],
            'is_active' => true
        ]);

        // iPhone Product
        $iphone = Product::create([
            'vendor_id' => $electronicsVendor->id,
            'name' => 'iPhone 15 Pro',
            'slug' => 'iphone-15-pro',
            'description' => 'iPhone 15 Pro. Forged in titanium and featuring the groundbreaking A17 Pro chip, a customizable Action Button, and the most powerful iPhone camera system ever.',
            'price' => 999.00,
            'sku' => 'IP15-001',
            'stock_quantity' => 100,
            'low_stock_threshold' => 20,
            'is_active' => true,
            'image' => 'products/iphone-15-pro.jpg',
            'metadata' => [
                'brand' => 'Apple',
                'category' => 'Smartphones',
                'specifications' => [
                    'processor' => 'A17 Pro',
                    'display' => '6.1" Super Retina XDR',
                    'camera' => '48MP Main Camera',
                    'storage_options' => ['128GB', '256GB', '512GB', '1TB']
                ]
            ]
        ]);

        // iPhone Variants
        $iphoneColors = ['Natural Titanium', 'Blue Titanium', 'White Titanium', 'Black Titanium'];
        $iphoneStorages = ['128GB', '256GB', '512GB', '1TB'];
        $iphonePrices = [999.00, 1099.00, 1299.00, 1499.00];

        foreach ($iphoneColors as $colorIndex => $color) {
            foreach ($iphoneStorages as $storageIndex => $storage) {
                ProductVariant::create([
                    'product_id' => $iphone->id,
                    'sku' => "IP15-PRO-{$storageIndex}-{$colorIndex}",
                    'name' => "iPhone 15 Pro - {$storage} - {$color}",
                    'price' => $iphonePrices[$storageIndex],
                    'stock_quantity' => rand(10, 25),
                    'attributes' => [
                        'storage' => $storage,
                        'color' => $color
                    ],
                    'is_active' => true
                ]);
            }
        }

        // Headphones Product
        $headphones = Product::create([
            'vendor_id' => $electronicsVendor->id,
            'name' => 'Sony WH-1000XM5',
            'slug' => 'sony-wh-1000xm5',
            'description' => 'Industry-leading noise canceling headphones with next-level sound quality and comfort. The Sony WH-1000XM5 headphones feature dual noise sensor technology.',
            'price' => 399.99,
            'sku' => 'SONY-XM5-001',
            'stock_quantity' => 75,
            'low_stock_threshold' => 15,
            'is_active' => true,
            'image' => 'products/sony-wh-1000xm5.jpg',
            'metadata' => [
                'brand' => 'Sony',
                'category' => 'Audio',
                'specifications' => [
                    'type' => 'Over-ear',
                    'noise_canceling' => 'Yes',
                    'battery_life' => '30 hours',
                    'connectivity' => 'Bluetooth 5.2'
                ]
            ]
        ]);

        // Headphones Variants
        ProductVariant::create([
            'product_id' => $headphones->id,
            'sku' => 'SONY-XM5-BLACK',
            'name' => 'Sony WH-1000XM5 - Black',
            'price' => 399.99,
            'stock_quantity' => 30,
            'attributes' => [
                'color' => 'Black'
            ],
            'is_active' => true
        ]);

        ProductVariant::create([
            'product_id' => $headphones->id,
            'sku' => 'SONY-XM5-SILVER',
            'name' => 'Sony WH-1000XM5 - Silver',
            'price' => 399.99,
            'stock_quantity' => 25,
            'attributes' => [
                'color' => 'Silver'
            ],
            'is_active' => true
        ]);

        ProductVariant::create([
            'product_id' => $headphones->id,
            'sku' => 'SONY-XM5-BLUE',
            'name' => 'Sony WH-1000XM5 - Blue',
            'price' => 399.99,
            'stock_quantity' => 20,
            'attributes' => [
                'color' => 'Blue'
            ],
            'is_active' => true
        ]);

        // Fashion Products
        $tshirt = Product::create([
            'vendor_id' => $fashionVendor->id,
            'name' => 'Premium Cotton T-Shirt',
            'slug' => 'premium-cotton-tshirt',
            'description' => 'High-quality 100% organic cotton t-shirt. Soft, comfortable, and durable. Perfect for everyday wear.',
            'price' => 29.99,
            'sku' => 'TSHIRT-001',
            'stock_quantity' => 200,
            'low_stock_threshold' => 25,
            'is_active' => true,
            'image' => 'products/premium-cotton-tshirt.jpg',
            'metadata' => [
                'brand' => 'EcoWear',
                'category' => 'Clothing',
                'material' => '100% Organic Cotton',
                'care' => 'Machine wash cold'
            ]
        ]);

        // T-Shirt Variants (Sizes and Colors)
        $sizes = ['XS', 'S', 'M', 'L', 'XL', 'XXL'];
        $colors = ['White', 'Black', 'Navy', 'Gray', 'Red', 'Blue'];

        foreach ($colors as $colorIndex => $color) {
            foreach ($sizes as $sizeIndex => $size) {
                ProductVariant::create([
                    'product_id' => $tshirt->id,
                    'sku' => "TSHIRT-{$size}-{$color}",
                    'name' => "Premium T-Shirt - {$size} - {$color}",
                    'price' => 29.99,
                    'stock_quantity' => rand(15, 35),
                    'attributes' => [
                        'size' => $size,
                        'color' => $color
                    ],
                    'is_active' => true
                ]);
            }
        }

        // Sneakers Product
        $sneakers = Product::create([
            'vendor_id' => $fashionVendor->id,
            'name' => 'Running Sneakers',
            'slug' => 'running-sneakers',
            'description' => 'Professional running sneakers with advanced cushioning technology. Perfect for athletes and casual runners.',
            'price' => 129.99,
            'sku' => 'SNEAKERS-001',
            'stock_quantity' => 150,
            'low_stock_threshold' => 30,
            'is_active' => true,
            'image' => 'products/running-sneakers.jpg',
            'metadata' => [
                'brand' => 'SportMax',
                'category' => 'Footwear',
                'type' => 'Running Shoes',
                'technology' => 'Advanced Cushioning System'
            ]
        ]);

        // Sneakers Variants (Sizes and Colors)
        $sneakerSizes = ['6', '7', '8', '9', '10', '11', '12', '13'];
        $sneakerColors = ['White/Black', 'Blue/White', 'Red/Black', 'Gray/White'];

        foreach ($sneakerColors as $colorIndex => $color) {
            foreach ($sneakerSizes as $sizeIndex => $size) {
                ProductVariant::create([
                    'product_id' => $sneakers->id,
                    'sku' => "SNEAKERS-{$size}-{$colorIndex}",
                    'name' => "Running Sneakers - Size {$size} - {$color}",
                    'price' => 129.99,
                    'stock_quantity' => rand(8, 20),
                    'attributes' => [
                        'size' => $size,
                        'color' => $color
                    ],
                    'is_active' => true
                ]);
            }
        }

        // Sports Products
        $yogaMat = Product::create([
            'vendor_id' => $sportsVendor->id,
            'name' => 'Premium Yoga Mat',
            'slug' => 'premium-yoga-mat',
            'description' => 'High-quality yoga mat with excellent grip and cushioning. Made from eco-friendly materials.',
            'price' => 49.99,
            'sku' => 'YOGA-MAT-001',
            'stock_quantity' => 80,
            'low_stock_threshold' => 15,
            'is_active' => true,
            'image' => 'products/premium-yoga-mat.jpg',
            'metadata' => [
                'brand' => 'ZenFit',
                'category' => 'Fitness',
                'material' => 'Eco-friendly TPE',
                'thickness' => '6mm',
                'dimensions' => '72" x 24"'
            ]
        ]);

        // Yoga Mat Variants
        $yogaMatColors = ['Purple', 'Blue', 'Pink', 'Green', 'Black'];

        foreach ($yogaMatColors as $colorIndex => $color) {
            ProductVariant::create([
                'product_id' => $yogaMat->id,
                'sku' => "YOGA-MAT-{$colorIndex}",
                'name' => "Premium Yoga Mat - {$color}",
                'price' => 49.99,
                'stock_quantity' => rand(10, 20),
                'attributes' => [
                    'color' => $color
                ],
                'is_active' => true
            ]);
        }

        // Resistance Bands Product
        $resistanceBands = Product::create([
            'vendor_id' => $sportsVendor->id,
            'name' => 'Resistance Bands Set',
            'slug' => 'resistance-bands-set',
            'description' => 'Complete resistance bands set for home workouts. Includes multiple resistance levels and accessories.',
            'price' => 39.99,
            'sku' => 'RES-BANDS-001',
            'stock_quantity' => 60,
            'low_stock_threshold' => 12,
            'is_active' => true,
            'image' => 'products/resistance-bands-set.jpg',
            'metadata' => [
                'brand' => 'FitPro',
                'category' => 'Fitness',
                'material' => 'Natural Latex',
                'includes' => ['5 resistance levels', 'Door anchor', 'Handle attachments', 'Ankle straps']
            ]
        ]);

        // Resistance Bands Variants
        $resistanceLevels = ['Light (10-25 lbs)', 'Medium (25-50 lbs)', 'Heavy (50-75 lbs)', 'Extra Heavy (75-100 lbs)'];

        foreach ($resistanceLevels as $index => $level) {
            ProductVariant::create([
                'product_id' => $resistanceBands->id,
                'sku' => "RES-BANDS-{$index}",
                'name' => "Resistance Bands - {$level}",
                'price' => $index == 3 ? 49.99 : 39.99, // Extra heavy is more expensive
                'stock_quantity' => rand(10, 18),
                'attributes' => [
                    'resistance_level' => $level
                ],
                'is_active' => true
            ]);
        }
    }
}
