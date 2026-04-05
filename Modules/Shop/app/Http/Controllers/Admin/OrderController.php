<?php

namespace Modules\Shop\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shop\Models\Order;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')
            ->withCount('items')
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->byStatus($request->input('status'));
        }

        $orders = $query->paginate(20);
        $statuses = ['pending', 'paid', 'processing', 'fulfilled', 'shipped', 'delivered', 'cancelled', 'refunded'];

        return view('shop::admin.orders.index', compact('orders', 'statuses'));
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'user');

        return view('shop::admin.orders.show', compact('order'));
    }

    public function cancel(Order $order)
    {
        if (in_array($order->status, ['shipped', 'delivered'])) {
            return back()->with('error', __('Impossible d\'annuler une commande déjà expédiée.'));
        }

        $order->update(['status' => 'cancelled']);

        return back()->with('success', __('Commande annulée.'));
    }
}
