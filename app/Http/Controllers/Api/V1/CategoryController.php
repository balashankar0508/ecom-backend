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
        $categories = Category::orderBy('name')->get();
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
}
