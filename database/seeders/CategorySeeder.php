<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'Fashion', 'slug' => 'fashion'],
            ['name' => 'Electronics', 'slug' => 'electronics'],
            ['name' => 'Organic', 'slug' => 'organic'],
        ];

        foreach ($categories as $c) {
            Category::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
