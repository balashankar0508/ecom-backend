<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'created_at' => now(),
        ]);

        DB::table('brands')->insert(['name' => 'Acme', 'slug' => 'acme']);
        DB::table('categories')->insert(['name' => 'T-Shirts', 'slug' => 't-shirts']);
        DB::table('products')->insert([
            'category_id' => 1,
            'brand_id' => 1,
            'title' => 'Classic Tee',
            'slug' => 'classic-tee',
            'status' => 'active',
            'base_price' => 499.00,
        ]);
        DB::table('product_variants')->insert([
            'product_id' => 1,
            'sku' => 'TEE-BLK-M',
            'size' => 'M',
            'color' => 'Black',
            'price' => 499.00,
        ]);
        DB::table('inventory')->insert(['variant_id' => 1, 'stock' => 25]);

        DB::table('coupons')->insert([
            'code' => 'DISCOUNT10',
            'type' => 'percent',
            'value' => 10,
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);
    }
}