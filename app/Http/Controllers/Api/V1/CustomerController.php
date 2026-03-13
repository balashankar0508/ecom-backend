<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index()
    {
        // Filter users by role 'customer'
        $customers = User::where('role', 'customer')->orWhereNull('role')->orderByDesc('created_at')->paginate(20);
        return response()->json($customers);
    }

    /**
     * Display the specified customer.
     */
    public function show(User $customer)
    {
        return $customer->load('orders');
    }
}
