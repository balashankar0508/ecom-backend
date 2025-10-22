<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class VariantController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku' => 'required|max:64|unique:product_variants,sku',
            'size' => 'nullable|max:32',
            'color' => 'nullable|max:64',
            'attributes' => 'array',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
        ]);

        $variant = $product->variants()->create($data);
        $variant->inventory()->create(['stock' => $data['stock'] ?? 0]);
        return response()->json($variant->load('inventory'), 201);
    }

    public function update(Request $request, ProductVariant $variant)
    {
        $data = $request->validate([
            'size' => 'nullable|max:32',
            'color' => 'nullable|max:64',
            'attributes' => 'array',
            'price' => 'nullable|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
        ]);

        $variant->update($data);
        if (array_key_exists('stock', $data)) {
            $variant->inventory()->updateOrCreate(['variant_id' => $variant->id], ['stock' => $data['stock']]);
        }
        return $variant->load('inventory');
    }

    public function destroy(ProductVariant $variant)
    {
        $variant->delete();
        return response()->noContent();
    }
}