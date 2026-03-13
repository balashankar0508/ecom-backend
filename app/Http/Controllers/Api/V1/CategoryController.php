<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List categories (existing method; keep if you already have it)
     */
    public function index()
    {
        $categories = Category::with('parent')->orderBy('name')->get();
        return response()->json($categories);
    }

    /**
     * Return products for a given category slug (public).
     *
     * GET /api/v1/catalog/categories/{slug}/products
     *
     * Returns a paginated JSON of products:
     * {
     *   current_page, data: [...], last_page, total, ...
     * }
     */
    public function products($slug, Request $request)
    {
        // find category by slug or name (case-insensitive)
        $category = Category::whereRaw('LOWER(slug) = ?', [strtolower($slug)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower($slug)])
            ->first();

        if (! $category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        // Build product query (eager load relations)
        $q = Product::with(['brand', 'category', 'variants.inventory', 'images'])
            ->where('category_id', $category->id)
            ->orderByDesc('id');

        // Optional: allow ?page= and ?per_page= in query string
        $perPage = (int) $request->query('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;

        $products = $q->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'required|string|max:191|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
        ]);
        $category = Category::create($data);
        return response()->json($category, 201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category)
    {
        return $category->load('parent');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'required|string|max:191|unique:categories,slug,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
        ]);
        $category->update($data);
        return $category;
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->noContent();
    }
}
