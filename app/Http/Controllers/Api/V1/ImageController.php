<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

class ImageController extends Controller
{
    public function index(Product $product)
    {
        return $product->images()->orderBy('position')->get();
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'image' => 'required|image|max:2048',
            'position' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('public/images');
        $url = Storage::url($path);

        // Optimize image
        $img = Image::make(storage_path('app/' . $path))->resize(800, null, function ($constraint) {
            $constraint->aspectRatio();
        })->save();

        $image = $product->images()->create([
            'url' => $url,
            'position' => $data['position'] ?? 0,
        ]);

        return response()->json($image, 201);
    }

    public function update(Request $request, ProductImage $image)
    {
        $data = $request->validate(['position' => 'nullable|integer|min:0']);
        $image->update($data);
        return $image;
    }

    public function destroy(ProductImage $image)
    {
        Storage::delete(str_replace('/storage/', 'public/', $image->url));
        $image->delete();
        return response()->noContent();
    }
}