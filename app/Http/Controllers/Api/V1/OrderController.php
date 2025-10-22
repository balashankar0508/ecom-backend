<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $q = Order::query()
            ->with(['user', 'items', 'payment', 'shipment'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->orderByDesc('placed_at');
        return $q->paginate(20);
    }

    public function show(Order $order)
    {
        return $order->load(['user', 'items', 'payment', 'shipment', 'billingAddress', 'shippingAddress']);
    }

    public function update(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,paid,shipped,delivered,cancelled,refunded',
        ]);
        $order->update($data);
        if ($data['status'] === 'shipped') {
            $order->shipment()->updateOrCreate(['order_id' => $order->id], ['status' => 'shipped', 'shipped_at' => now()]);
        }
        return $order->fresh()->load(['user', 'items', 'payment', 'shipment']);
    }

    public function customerIndex(Request $request)
    {
        return $request->user()->orders()->with(['items', 'payment', 'shipment'])->orderByDesc('placed_at')->paginate(10);
    }

    public function customerShow(Order $order, Request $request)
    {
        if ($request->user()->id !== $order->user_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return $order->load('items', 'payment', 'shipment', 'billingAddress', 'shippingAddress');
    }
}