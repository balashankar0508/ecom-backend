<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function index()
    {
        return Brand::paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'required|string|max:191|unique:brands',
        ]);
        $brand = Brand::create($data);
        return response()->json($brand, 201);
    }

    public function show(Brand $brand)
    {
        return $brand;
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'required|string|max:191|unique:brands,slug,' . $brand->id,
        ]);
        $brand->update($data);
        return $brand;
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return response()->noContent();
    }
}