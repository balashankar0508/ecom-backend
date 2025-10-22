<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function download(Order $order, Request $request)
    {
        if ($request->user()->id !== $order->user_id && $request->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $pdf = Pdf::loadView('invoices.order', ['order' => $order->load('items', 'billingAddress', 'shippingAddress', 'user')]);
        return $pdf->download("invoice-{$order->order_number}.pdf");
    }
}