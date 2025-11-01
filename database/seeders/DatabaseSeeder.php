<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Admin user (safe: will not fail if 'role' column missing) ---
        $admin = DB::table('users')->where('email', 'admin@example.com')->first();
        if (! $admin) {
            $id = DB::table('users')->insertGetId([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // update role only if column exists
            if (Schema::hasColumn('users', 'role')) {
                DB::table('users')->where('id', $id)->update(['role' => 'admin']);
            }
        } else {
            // ensure role is set if column exists
            if (Schema::hasColumn('users', 'role') && $admin->role !== 'admin') {
                DB::table('users')->where('email', 'admin@example.com')->update(['role' => 'admin']);
            }
        }

        // Call other seeders (they will handle categories/products creation)
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        // --- Brands / Categories / Products (idempotent using updateOrCreate) ---
        DB::table('brands')->updateOrInsert(
            ['slug' => 'acme'],
            ['name' => 'Acme', 'slug' => 'acme', 'created_at' => now(), 'updated_at' => now()]
        );

        DB::table('categories')->updateOrInsert(
            ['slug' => 't-shirts'],
            ['name' => 'T-Shirts', 'slug' => 't-shirts', 'created_at' => now(), 'updated_at' => now()]
        );

        // Product (if not exists create)
        DB::table('products')->updateOrInsert(
            ['slug' => 'classic-tee'],
            [
                'category_id' => DB::table('categories')->where('slug', 't-shirts')->value('id'),
                'brand_id' => DB::table('brands')->where('slug', 'acme')->value('id'),
                'title' => 'Classic Tee',
                'slug' => 'classic-tee',
                'status' => 'active',
                'base_price' => 499.00,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Product variant (idempotent)
        $productId = DB::table('products')->where('slug', 'classic-tee')->value('id');
        if ($productId) {
            DB::table('product_variants')->updateOrInsert(
                ['product_id' => $productId, 'sku' => 'TEE-BLK-M'],
                [
                    'size' => 'M',
                    'color' => 'Black',
                    'price' => 499.00,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $variantId = DB::table('product_variants')->where('sku', 'TEE-BLK-M')->value('id');
            if ($variantId) {
                DB::table('inventory')->updateOrInsert(
                    ['variant_id' => $variantId],
                    ['stock' => 25, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // Coupon (idempotent)
        DB::table('coupons')->updateOrInsert(
            ['code' => 'DISCOUNT10'],
            [
                'type' => 'percent',
                'value' => 10,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
