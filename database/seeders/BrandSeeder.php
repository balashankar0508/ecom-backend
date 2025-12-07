<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            'Test Brand',
            'Brand One',
            'Brand Two',
        ];

        foreach ($brands as $name) {
            Brand::firstOrCreate(['name' => $name], ['name' => $name]);
        }
    }
}
