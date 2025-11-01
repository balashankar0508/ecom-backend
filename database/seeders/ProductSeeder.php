<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // simple sample products for each category
        $samples = [
            'fashion' => [
                ['title' => 'Women Floral Dress', 'slug' => 'women-floral-dress', 'base_price' => 1299, 'description' => 'Stylish floral dress.'],
                ['title' => 'Men Casual Shirt', 'slug' => 'men-casual-shirt', 'base_price' => 899, 'description' => 'Comfortable casual shirt.'],
            ],
            'electronics' => [
                ['title' => 'Bluetooth Headphones', 'slug' => 'bluetooth-headphones', 'base_price' => 2599, 'description' => 'Noise cancelling headphones.'],
                ['title' => 'Smart Watch', 'slug' => 'smart-watch', 'base_price' => 4999, 'description' => 'Fitness and notifications.'],
            ],
            'organic' => [
                ['title' => 'Organic Honey Jar', 'slug' => 'organic-honey-jar', 'base_price' => 399, 'description' => 'Pure local honey.'],
                ['title' => 'Organic Almonds 500g', 'slug' => 'organic-almonds-500g', 'base_price' => 799, 'description' => 'Premium almonds.'],
            ],
        ];

        foreach ($samples as $categorySlug => $items) {
            $category = Category::where('slug', $categorySlug)->first();
            if (! $category) continue;

            foreach ($items as $it) {
                $product = Product::updateOrCreate(
                    ['slug' => $it['slug']],
                    [
                        'title' => $it['title'],
                        'slug' => $it['slug'],
                        'base_price' => $it['base_price'],
                        'description' => $it['description'],
                        'category_id' => $category->id,
                        'brand_id' => null,
                        'status' => 'active',
                    ]
                );

                // add a placeholder image (change URL if you host images)
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'position' => 1],
                    ['url' => 'https://via.placeholder.com/800x600?text=' . urlencode($product->title)]
                );
            }
        }
    }
}
