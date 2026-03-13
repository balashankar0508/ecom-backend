<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product; // Need to fetch product prices directly
use App\Models\ProductVariant; // If using variants
use App\Models\Shipment;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function checkout(Request $request)
    {
        // 1. Validate including 'items' since we don't have a reliable server-side cart
        $data = $request->validate([
            'billing_address' => 'required|array',
            'billing_address.name' => 'required|string|max:120',
            'billing_address.phone' => 'required|string|max:20',
            'billing_address.line1' => 'required|string|max:255',
            'billing_address.city' => 'required|string|max:120',
            'billing_address.postal_code' => 'required|string|max:20',
            
            'shipping_address' => 'required|array',
            
            'coupon_code' => 'nullable|string',
            'payment_method' => 'required|in:razorpay,cod',
            
            'items' => 'required|array|min:1',
            'items.*.id' => 'required', // Product ID
            'items.*.qty' => 'required|integer|min:1',
            'items.*.variant_id' => 'nullable', // Optional if using variants
        ]);

        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Limit pending orders to prevent spamming duplicate orders when payment is aborted
        if ($data['payment_method'] === 'razorpay') {
            $productIds = collect($data['items'])->pluck('id')->sort()->values()->toArray();
            
            $pendingOrders = Order::where('user_id', $user->id)
                ->where('status', 'pending')
                ->with('items')
                ->get();
                
            $duplicateCount = 0;
            foreach ($pendingOrders as $pendingOrder) {
                $pendingProductIds = $pendingOrder->items->pluck('product_id')->sort()->values()->toArray();
                if ($productIds === $pendingProductIds) {
                    $duplicateCount++;
                }
            }

            if ($duplicateCount >= 2) {
                return response()->json(['error' => 'You have multiple pending orders for these exact items. Please view your orders to complete payment or cancel them before placing a new one.'], 400);
            }
        }

        // 2. Calculate Totals from Database (Secure)
        $subtotal = 0;
        $orderItemsData = [];

        foreach ($data['items'] as $item) {
            $product = Product::find($item['id']);
            
            if (!$product) continue;
            
            // Check stock if needed (omitted for brevity, but recommended)
            
            $price = $product->base_price; 
            
            // If you had variants, logic would go here:
            // $variant = ProductVariant::find($item['variant_id']);
            // $price = $variant ? $variant->price : $product->base_price;

            $lineTotal = $price * $item['qty'];
            $subtotal += $lineTotal;

            $orderItemsData[] = [
                'product_id' => $product->id,
                'title_snapshot' => $product->title,
                'qty' => $item['qty'],
                'unit_price' => $price,
                'subtotal' => $lineTotal
            ];
        }

        if ($subtotal <= 0) {
            return response()->json(['error' => 'Invalid order total'], 400);
        }

        $tax = 0; // Simplified for now
        $shipping = 100.00; // Flat rate
        $discount = 0;

        // 3. Coupon Logic
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

        // 4. Create Addresses
        // Note: Make sure your Address model has 'phone' in fillable if sending it
        $billingData = array_merge($data['billing_address'], ['user_id' => $user->id, 'type' => 'billing']);
        $shippingData = array_merge($data['shipping_address'], ['user_id' => $user->id, 'type' => 'shipping']);
        
        // Simple address creation (adjust fields based on your Address model)
        $billing = Address::create($billingData);
        $shippingAddr = Address::create($shippingData);

        // 5. Create Order
        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-' . strtoupper(uniqid()), // Ensure you have this column or use ID
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

        // 6. Create Order Items
        foreach ($orderItemsData as $itemData) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $itemData['product_id'],
                'title_snapshot' => $itemData['title_snapshot'],
                'qty' => $itemData['qty'],
                'unit_price' => $itemData['unit_price'],
                'subtotal' => $itemData['subtotal'],
            ]);
            // Decrement Stock
            // DB::table('inventory')->where('product_id', $itemData['product_id'])->decrement('stock', $itemData['qty']);
        }

        Shipment::create(['order_id' => $order->id, 'status' => 'pending']);

        $payment = Payment::create([
            'order_id' => $order->id,
            'payment_method' => $data['payment_method'], // Fixed key
            'amount' => $total,
            'currency' => 'INR',
            'status' => 'pending',
        ]);

        // 7. Payment Integration
        if ($data['payment_method'] === 'razorpay') {
            $razorpayKey = env('RAZORPAY_KEY');
            $razorpaySecret = env('RAZORPAY_SECRET');

            if (!$razorpayKey || !$razorpaySecret) {
               return response()->json(['error' => 'Razorpay configuration missing'], 500);
            }

            $api = new Api($razorpayKey, $razorpaySecret);
            $rzpOrder = $api->order->create([
                'amount' => (int)($total * 100), // Paise
                'currency' => 'INR',
                'receipt' => (string)$order->id,
            ]);
            
            $payment->intent_id = $rzpOrder['id'];
            $payment->save();

            return response()->json([
                'order' => $order,
                'razorpay_order_id' => $rzpOrder['id'],
                'razorpay_key' => $razorpayKey,
                'amount' => (int)($total * 100),
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);
        } else {
            // COD
            $payment->status = 'authorized'; // Pending collection
            $payment->save();
            // Order remains pending until confirmed/delivered usually, but for simplicity:
            $order->status = 'placed'; 
            $order->save();
            
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
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id' => $data['razorpay_order_id'],
                'razorpay_payment_id' => $data['razorpay_payment_id'],
                'razorpay_signature' => $data['razorpay_signature']
            ]);
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

        return response()->json(['order' => $order, 'message' => 'Payment successful']);
    }

    public function retryPayment(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Ensure user owns the order and it's pending
        if (!$user || $order->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json(['error' => 'Order is not in pending state'], 400);
        }

        $razorpayKey = env('RAZORPAY_KEY');
        $razorpaySecret = env('RAZORPAY_SECRET');

        if (!$razorpayKey || !$razorpaySecret) {
            return response()->json(['error' => 'Razorpay configuration missing'], 500);
        }

        // We need an existing payment record, or we create one if COD was changed to Razorpay
        $payment = $order->payment()->first();
        if (!$payment) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'payment_method' => 'razorpay',
                'amount' => $order->total,
                'currency' => 'INR',
                'status' => 'pending',
            ]);
        } else {
            $payment->update(['payment_method' => 'razorpay']);
        }

        // Create a new Razorpay Order ID for retry
        $api = new Api($razorpayKey, $razorpaySecret);
        $rzpOrder = $api->order->create([
            'amount' => (int)($order->total * 100), // Paise
            'currency' => 'INR',
            'receipt' => (string)$order->id,
        ]);
        
        $payment->intent_id = $rzpOrder['id'];
        $payment->save();

        return response()->json([
            'order' => $order,
            'razorpay_order_id' => $rzpOrder['id'],
            'razorpay_key' => $razorpayKey,
            'amount' => (int)($order->total * 100),
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);
    }
}