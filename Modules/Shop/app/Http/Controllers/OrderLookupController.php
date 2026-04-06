<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Shop\Models\Order;

class OrderLookupController extends Controller
{
    public function index()
    {
        return view('shop::public.order-lookup');
    }

    public function search(Request $request)
    {
        $key = 'order-lookup:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            return back()->with('error', __('Trop de tentatives. Veuillez réessayer dans quelques minutes.'));
        }

        $request->validate([
            'order_id' => 'required|integer',
            'email' => 'required|email',
        ]);

        $order = Order::with(['items.product'])
            ->where('id', $request->input('order_id'))
            ->where('email', $request->input('email'))
            ->first();

        if (! $order) {
            RateLimiter::hit($key, 300);
            return back()->with('error', __('Aucune commande trouvée avec ces informations.'));
        }

        RateLimiter::clear($key);

        return view('shop::public.order-lookup', compact('order'));
    }
}
