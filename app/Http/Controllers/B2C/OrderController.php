<?php

namespace App\Http\Controllers\B2C;

use App\Http\Controllers\Controller;
use App\Models\B2C\B2cOrder;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Auth::guard('b2c')->user()
            ->orders()
            ->with(['item.category', 'latestPayment'])
            ->latest()
            ->paginate(10);

        return view('b2c.account.orders', compact('orders'));
    }

    public function show(string $ref)
    {
        $order = B2cOrder::where('order_ref', $ref)
            ->where('b2c_user_id', Auth::guard('b2c')->id())
            ->with(['item.category', 'passengers', 'payments'])
            ->firstOrFail();

        return view('b2c.account.order-detail', compact('order'));
    }
}
