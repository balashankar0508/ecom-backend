<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function show(Request $request)
    {
        $cart = $this->getCart($request);
        return $cart->load('items.variant.product');
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'qty' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::find($data['variant_id']);
        if ($variant->inventory->stock < $data['qty']) {
            return response()->json(['error' => 'Out of stock'], 400);
        }

        $cart = $this->getCart($request);
        $item = $cart->items()->updateOrCreate(
            ['variant_id' => $data['variant_id']],
            ['qty' => \DB::raw('qty + ' . $data['qty']), 'unit_price' => $variant->price]
        );

        return $cart->fresh()->load('items.variant.product');
    }

    public function update(Request $request, $itemId)
    {
        $data = $request->validate(['qty' => 'required|integer|min:0']);

        $cart = $this->getCart($request);
        $item = $cart->items()->findOrFail($itemId);
        if ($data['qty'] === 0) {
            $item->delete();
        } else {
            if ($item->variant->inventory->stock < $data['qty']) {
                return response()->json(['error' => 'Out of stock'], 400);
            }
            $item->update(['qty' => $data['qty']]);
        }

        return $cart->fresh()->load('items.variant.product');
    }

    public function remove($itemId, Request $request)
    {
        $cart = $this->getCart($request);
        $cart->items()->findOrFail($itemId)->delete();
        return $cart->fresh()->load('items.variant.product');
    }

    protected function getCart(Request $request)
    {
        $user = $request->user();
        if ($user) {
            return Cart::firstOrCreate(['user_id' => $user->id]);
        }

        $guestToken = $request->header('X-Guest-Token') ?? str_replace('-', '', \Str::uuid()->toString());
        return Cart::firstOrCreate(['guest_token' => $guestToken]);
    }
}