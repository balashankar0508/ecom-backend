<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductImage; // ✅ ADDED
use Illuminate\Http\JsonResponse; // ✅ ADDED
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $q = Product::query()
            ->with(['brand', 'category', 'variants.inventory', 'images'])
            ->when($request->status, fn($x) => $x->where('status', $request->status))
            ->when($request->category_id, fn($x) => $x->where('category_id', $request->category_id))
            ->orderByDesc('id');
        return $q->paginate(20);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived', // ✅ FIXED: No spaces
            'base_price' => 'required|numeric',
            'tax_rate' => 'nullable|numeric',
            'seo' => 'nullable|array',
            'image_url_1' => 'nullable|url',
            'image_url_2' => 'nullable|url',
            'image_url_3' => 'nullable|url',
            'image_url_4' => 'nullable|url',
            'image_url_5' => 'nullable|url',
        ]);

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'brand_id' => $validated['brand_id'],
            'title' => $validated['title'],
            'slug' => $validated['slug'],
            'description' => $validated['description'],
            'status' => $validated['status'],
            'base_price' => $validated['base_price'],
            'tax_rate' => $validated['tax_rate'],
            'seo' => $validated['seo'] ?? [],
        ]);

        // ✅ FIXED: Handle image URL if provided
        if ($request->filled('image_url')) {
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $validated['image_url'],
                'position' => 1,
            ]);
        }
        // ✅ FIXED: Handle image URL if provided
        if ($request->filled('image_url_1')) {
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $validated['image_url_1'],
                'position' => 2,
            ]);
        }

        return response()->json($product->load(['brand', 'category', 'images']), 201);
    }

    public function show(Product $product)
    {
        return $product->load(['brand', 'category', 'variants.inventory', 'images']);
    }

    public function showproduct($slug)
    {
        // ✅ FIXED: EXPLICIT SLUG QUERY (NOT MODEL BINDING)
        $product = Product::with(['brand', 'category', 'variants.inventory', 'images'])
            ->where('slug', $slug)
            ->where('status', 'active') // Only active products
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }

        return response()->json($product);
    }

    public function update(Request $request, Product $product): JsonResponse // ✅ FIXED
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'brand_id' => 'required|exists:brands,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $product->id,
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active,archived',
            'base_price' => 'required|numeric',
            'tax_rate' => 'nullable|numeric',
            'seo' => 'nullable|array',
            'image_url_1' => 'nullable|url',
            'image_url_2' => 'nullable|url',
            'image_url_3' => 'nullable|url',
            'image_url_4' => 'nullable|url',
            'image_url_5' => 'nullable|url',
        ]);

        $product->update($validated);

        // ✅ Handle image URL update
        if ($request->filled('image_url')) {
            $product->images()->delete();
            ProductImage::create([
                'product_id' => $product->id,
                'url' => $validated['image_url'],
                'position' => 1,
            ]);
        }

        return response()->json($product->fresh()->load(['brand', 'category', 'images']));
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return response()->noContent();
    }
}