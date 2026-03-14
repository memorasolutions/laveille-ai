<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Coupon;

class CouponController
{
    public function index(): View
    {
        $coupons = Coupon::latest()->get();

        return view('ecommerce::admin.coupons.index', compact('coupons'));
    }

    public function create(): View
    {
        return view('ecommerce::admin.coupons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:ecommerce_coupons,code',
            'type' => 'required|string|in:fixed,percent,free_shipping',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        Coupon::create($validated);

        session()->flash('success', 'Coupon créé avec succès.');

        return redirect()->route('admin.ecommerce.coupons.index');
    }

    public function edit(Coupon $coupon): View
    {
        return view('ecommerce::admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:255|unique:ecommerce_coupons,code,'.$coupon->id,
            'type' => 'required|string|in:fixed,percent,free_shipping',
            'value' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $coupon->update($validated);

        session()->flash('success', 'Coupon mis à jour avec succès.');

        return redirect()->route('admin.ecommerce.coupons.index');
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $coupon->delete();

        session()->flash('success', 'Coupon supprimé avec succès.');

        return redirect()->route('admin.ecommerce.coupons.index');
    }
}
