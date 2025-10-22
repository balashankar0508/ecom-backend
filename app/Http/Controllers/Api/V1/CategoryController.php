<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return Category::with('parent')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'required|string|max:191|unique:categories',
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        $category = Category::create($data);
        return response()->json($category->load('parent'), 201);
    }

    public function show(Category $category)
    {
        return $category->load('parent');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'required|string|max:191|unique:categories,slug,' . $category->id,
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        $category->update($data);
        return $category->load('parent');
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response()->noContent();
    }
}