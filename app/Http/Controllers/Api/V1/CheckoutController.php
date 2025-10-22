<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Razorpay\Api\Api;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'billing_address' => 'required|array',
            'billing_address.name' => 'required|string|max:120',
            'billing_address.line1' => 'required|string|max:255',
            'billing_address.city' => 'required|string|max:120',
            'billing_address.postal_code' => 'required|string|max:20',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string|max:120',
            'shipping_address.line1' => 'required|string|max:255',
            'shipping_address.city' => 'required|string|max:120',
            'shipping_address.postal_code' => 'required|string|max:20',
            'coupon_code' => 'nullable|string',
            'payment_method' => 'required|in:razorpay,cod',
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $cart = Cart::where('user_id', $user->id)->firstOrFail();
        if ($cart->items->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 400);
        }

        $subtotal = $cart->items->sum(fn($item) => $item->qty * $item->unit_price);
        $tax = $subtotal * 0.18; // 18% GST
        $shipping = 100.00; // Flat rate
        $discount = 0;

        if ($data['coupon_code']) {
            $coupon = Coupon::where('code', $data['coupon_code'])
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now())
                ->whereRaw('usage_limit IS NULL OR used_count < usage_limit')
                ->first();
            if ($coupon) {
                $discount = $coupon->type === 'percent' ? ($subtotal * $coupon->value / 100) : $coupon->value;
                $coupon->increment('used_count');
            }
        }

        $total = $subtotal + $tax + $shipping - $discount;

        $billing = Address::create(array_merge($data['billing_address'], ['user_id' => $user->id, 'type' => 'billing']));
        $shippingAddr = Address::create(array_merge($data['shipping_address'], ['user_id' => $user->id, 'type' => 'shipping']));

        $order = Order::create([
            'user_id' => $user->id,
            'status' => 'pending',
            'currency' => 'INR',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total,
            'billing_address_id' => $billing->id,
            'shipping_address_id' => $shippingAddr->id,
            'placed_at' => now(),
        ]);

        foreach ($cart->items as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'variant_id' => $item->variant_id,
                'title_snapshot' => $item->variant->product->title,
                'size_snapshot' => $item->variant->size,
                'color_snapshot' => $item->variant->color,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->qty * $item->unit_price,
            ]);

            $item->variant->inventory->decrement('stock', $item->qty);
        }

        Shipment::create(['order_id' => $order->id, 'status' => 'pending']);

        $payment = Payment::create([
            'order_id' => $order->id,
            'provider' => $data['payment_method'],
            'amount' => $total,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        if ($data['payment_method'] === 'razorpay') {
            $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
            $rzpOrder = $api->order->create([
                'amount' => $total * 100, // Paise
                'currency' => 'INR',
                'receipt' => $order->order_number,
            ]);
            $payment->intent_id = $rzpOrder['id'];
            $payment->save();

            return response()->json([
                'order' => $order,
                'razorpay_order_id' => $rzpOrder['id'],
                'razorpay_key' => env('RAZORPAY_KEY'),
                'amount' => $total * 100,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);
        } else {
            $payment->status = 'authorized';
            $payment->save();
            $order->status = 'paid';
            $order->save();
            $cart->items()->delete();
            return response()->json(['order' => $order, 'message' => 'Order placed with COD']);
        }
    }

    public function verifyPayment(Request $request)
    {
        $data = $request->validate([
            'razorpay_order_id' => 'required',
            'razorpay_payment_id' => 'required',
            'razorpay_signature' => 'required',
        ]);

        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
        try {
            $api->utility->verifyPaymentSignature($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Payment verification failed'], 400);
        }

        $payment = Payment::where('intent_id', $data['razorpay_order_id'])->firstOrFail();
        $payment->status = 'succeeded';
        $payment->raw_payload = json_encode($request->all());
        $payment->save();

        $order = $payment->order;
        $order->status = 'paid';
        $order->save();

        Cart::where('user_id', $order->user_id)->first()?->items()->delete();

        return response()->json(['order' => $order, 'message' => 'Payment successful']);
    }
}