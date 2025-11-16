<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🌱 Starting database seeding...');
        
        // Run seeders in correct order to respect foreign key constraints
        $this->command->info('👥 Creating users...');
        $this->call(UserSeeder::class);
        
        $this->command->info('📦 Creating products with variants...');
        $this->call(ProductSeeder::class);
        
        $this->command->info('🛒 Creating sample orders...');
        $this->call(OrderSeeder::class);
        
        $this->command->info('✅ Database seeding completed successfully!');
        $this->command->info('');
        $this->command->info('📊 Summary:');
        $this->command->info('- Users: ' . \App\Models\User::count());
        $this->command->info('- Products: ' . \App\Models\Product::count());
        $this->command->info('- Product Variants: ' . \App\Models\ProductVariant::count());
        $this->command->info('- Orders: ' . \App\Models\Order::count());
        $this->command->info('- Order Items: ' . \App\Models\OrderItem::count());
        $this->command->info('');
        $this->command->info('🔑 Sample Login Credentials:');
        $this->command->info('Admin: admin@ecommerce.com / password');
        $this->command->info('Vendor: vendor@electronics.com / password');
        $this->command->info('Customer: john.doe@example.com / password');
    }
}
