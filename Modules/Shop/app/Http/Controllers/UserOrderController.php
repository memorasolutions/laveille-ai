<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Shop\Models\Order;

class UserOrderController extends Controller
{
    public function index()
    {
        $orders = Order::where('user_id', auth()->id())->latest()->paginate(10);

        return view('shop::public.my-orders', compact('orders'));
    }
}
