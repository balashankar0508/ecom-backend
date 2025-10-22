<!doctype html>
<html>
<head>
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        body { font-family: sans-serif; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        h1, h2, h3 { margin: 10px 0; }
    </style>
</head>
<body>
    <h1>Invoice #{{ $order->order_number }}</h1>
    <p>Date: {{ $order->placed_at->format('Y-m-d') }}</p>
    <p>Customer: {{ $order->user->name }} ({{ $order->user->email }})</p>

    <h2>Billing Address</h2>
    <p>{{ $order->billingAddress->line1 }}, {{ $order->billingAddress->city }}, {{ $order->billingAddress->postal_code }}</p>

    <h2>Shipping Address</h2>
    <p>{{ $order->shippingAddress->line1 }}, {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->postal_code }}</p>

    <h2>Items</h2>
    <table>
        <thead><tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->title_snapshot }} ({{ $item->size_snapshot }} / {{ $item->color_snapshot }})</td>
                <td>{{ $item->qty }}</td>
                <td>₹{{ number_format($item->unit_price, 2) }}</td>
                <td>₹{{ number_format($item->subtotal, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <p>Subtotal: ₹{{ number_format($order->subtotal, 2) }}</p>
    <p>Tax: ₹{{ number_format($order->tax, 2) }}</p>
    <p>Shipping: ₹{{ number_format($order->shipping, 2) }}</p>
    <p>Discount: -₹{{ number_format($order->discount, 2) }}</p>
    <h3>Total: ₹{{ number_format($order->total, 2) }}</h3>
</body>
</html>