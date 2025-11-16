<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Admin User
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@ecommerce.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Vendor Users
        User::create([
            'name' => 'Electronics Vendor',
            'email' => 'vendor@electronics.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Fashion Vendor',
            'email' => 'vendor@fashion.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Sports Vendor',
            'email' => 'vendor@sports.com',
            'password' => bcrypt('password'),
            'role' => 'vendor',
            'email_verified_at' => now(),
        ]);

        // Create Customer Users
        User::create([
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Jane Smith',
            'email' => 'jane.smith@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Mike Johnson',
            'email' => 'mike.johnson@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Sarah Wilson',
            'email' => 'sarah.wilson@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'David Brown',
            'email' => 'david.brown@example.com',
            'password' => bcrypt('password'),
            'role' => 'customer',
            'email_verified_at' => now(),
        ]);
    }
}
