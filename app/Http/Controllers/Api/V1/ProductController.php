<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use App\Models\Brand;

class ProductController extends Controller
{
    /**
     * --------------------------------------------
     * AUTO CATEGORY DETECTION FUNCTION
     * --------------------------------------------
     */
    private function detectCategory(string $title): ?int
    {
        $title = strtolower($title);

        $map = [
            'fashion' => ['shirt', 't-shirt', 'jeans', 'pants', 'dress', 'kurta', 'hoodie'],
            'organic' => ['organic', 'honey', 'herbal', 'bio', 'juice', 'oil'],
            'electronics' => ['phone', 'laptop', 'tv', 'earbud', 'smartwatch', 'camera'],
        ];

        foreach ($map as $slug => $keywords) {
            foreach ($keywords as $word) {
                if (str_contains($title, $word)) {
                    $category = Category::where('slug', $slug)->first();
                    return $category?->id;
                }
            }
        }

        return null; // no match
    }


    /**
     * --------------------------------------------
     * GET ALL PRODUCTS
     * --------------------------------------------
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'brand', 'images'])->latest();

        if ($request->has('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('limit')) {
            $query->limit($request->limit);
        }

        return response()->json(['data' => $query->get()], 200);
    }


    /**
     * --------------------------------------------
     * CREATE PRODUCT
     * --------------------------------------------
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',

            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug',

            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived',
            'base_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'seo' => 'nullable|array',

            // images
            'image_url' => 'required|url',
            'image_url_1' => 'required|url',
            'image_url_2' => 'required|url',
            'image_url_3' => 'nullable|url',
            'image_url_4' => 'nullable|url',
        ]);

        /** AUTO-SLUG */
        $slug = $validated['slug'] ?? Str::slug($validated['title']);

        /** AUTO CATEGORY DETECTION */
        $categoryId = $validated['category_id'] ?? $this->detectCategory($validated['title']);
        if (!$categoryId) {
            $categoryId = 1; // fallback to master category
        }

        /** CREATE PRODUCT */
        $product = Product::create([
            'category_id' => $categoryId,
            'brand_id' => $validated['brand_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'base_price' => $validated['base_price'],
            'tax_rate' => $validated['tax_rate'] ?? 0,
            'seo' => $validated['seo'] ?? [],
        ]);

        /** SAVE IMAGES */
        $imageKeys = ['image_url', 'image_url_1', 'image_url_2', 'image_url_3', 'image_url_4'];
        $position = 1;

        foreach ($imageKeys as $key) {
            if (!empty($validated[$key])) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => $validated[$key],
                    'position' => $position++,
                ]);
            }
        }

        return response()->json([
            'message' => 'Product created successfully!',
            'product' => $product->load(['brand', 'category', 'images']),
        ], 201);
    }


    /**
     * --------------------------------------------
     * SHOW PRODUCT
     * --------------------------------------------
     */
    public function show(Product $product): JsonResponse
    {
        return response()->json($product->load(['brand', 'category', 'images']));
    }


    /**
     * --------------------------------------------
     * UPDATE PRODUCT
     * --------------------------------------------
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'brand_id' => 'nullable|exists:brands,id',

            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $product->id,

            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived',
            'base_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'seo' => 'nullable|array',

            'image_url' => 'nullable|url',
            'image_url_1' => 'nullable|url',
            'image_url_2' => 'nullable|url',
            'image_url_3' => 'nullable|url',
            'image_url_4' => 'nullable|url',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['title']);

        /** AUTO / EXPLICIT CATEGORY UPDATE */
        $categoryId = $validated['category_id'] ?? $this->detectCategory($validated['title']);
        if (!$categoryId) {
            $categoryId = $product->category_id;
        }

        $product->update([
            'category_id' => $categoryId,
            'brand_id' => $validated['brand_id'] ?? null,
            'title' => $validated['title'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'base_price' => $validated['base_price'],
            'tax_rate' => $validated['tax_rate'] ?? 0,
            'seo' => $validated['seo'] ?? [],
        ]);

        /** IMAGE REPLACEMENT */
        $imageKeys = ['image_url', 'image_url_1', 'image_url_2', 'image_url_3', 'image_url_4'];
        $hasNewImages = collect($imageKeys)->contains(fn($key) => !empty($request->input($key)));

        if ($hasNewImages) {
            $product->images()->delete();

            $position = 1;
            foreach ($imageKeys as $key) {
                if ($request->filled($key)) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'url' => $request->input($key),
                        'position' => $position++,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Product updated successfully!',
            'product' => $product->fresh()->load(['brand', 'category', 'images']),
        ]);
    }


    /**
     * --------------------------------------------
     * DELETE PRODUCT
     * --------------------------------------------
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->images()->delete();
        $product->delete();

        return response()->json(['message' => 'Product deleted successfully!']);
    }


    /**
     * --------------------------------------------
     * RECENT PRODUCTS
     * --------------------------------------------
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 6);

        $products = Product::with(['images', 'category'])
            ->where('status', 'active')
            ->latest()
            ->take($limit)
            ->get();

        return response()->json(['data' => $products]);
    }
}
