<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\InvoiceService;

class OrderController
{
    public function index(Request $request): View
    {
        $query = Order::with(['user', 'items']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%'.$request->input('search').'%');
        }

        $orders = $query->latest()->paginate(20);

        return view('ecommerce::admin.orders.index', compact('orders'));
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'user', 'shippingAddress', 'billingAddress', 'coupon']);

        return view('ecommerce::admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,completed,cancelled,refunded',
        ]);

        $order->update(['status' => $validated['status']]);

        if ($validated['status'] === 'shipped') {
            \Modules\Ecommerce\Events\OrderShipped::dispatch($order);
            /** @var \App\Models\User $user */
            $user = $order->user;
            $user->notify(new \Modules\Ecommerce\Notifications\OrderShippedNotification($order));
        }

        session()->flash('success', 'Statut de la commande mis à jour avec succès.');

        return redirect()->route('admin.ecommerce.orders.show', $order);
    }

    public function invoice(Order $order, InvoiceService $invoiceService): Response
    {
        return $invoiceService->downloadResponse($order);
    }
}
