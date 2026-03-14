<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Booking\Models\GiftCard;

class GiftCardController extends Controller
{
    public function index()
    {
        $giftCards = GiftCard::latest()->get();

        return view('booking::admin.gift-cards.index', compact('giftCards'));
    }

    public function create()
    {
        return view('booking::admin.gift-cards.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:booking_gift_cards,code',
            'purchaser_name' => 'required|string|max:255',
            'purchaser_email' => 'required|email',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_email' => 'nullable|email',
            'recipient_message' => 'nullable|string',
            'initial_amount' => 'required|numeric|min:0',
            'remaining_amount' => 'required|numeric|min:0',
            'currency' => 'string|max:3',
            'status' => 'required|in:active,used,expired,exhausted',
            'purchased_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ]);

        GiftCard::create($validated);

        return redirect()->route('admin.booking.gift-cards.index')
            ->with('success', __('Carte-cadeau créée avec succès.'));
    }

    public function edit(GiftCard $giftCard)
    {
        return view('booking::admin.gift-cards.edit', compact('giftCard'));
    }

    public function update(Request $request, GiftCard $giftCard)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|unique:booking_gift_cards,code,'.$giftCard->id,
            'purchaser_name' => 'required|string|max:255',
            'purchaser_email' => 'required|email',
            'recipient_name' => 'nullable|string|max:255',
            'recipient_email' => 'nullable|email',
            'recipient_message' => 'nullable|string',
            'initial_amount' => 'required|numeric|min:0',
            'remaining_amount' => 'required|numeric|min:0',
            'currency' => 'string|max:3',
            'status' => 'required|in:active,used,expired,exhausted',
            'purchased_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
        ]);

        $giftCard->update($validated);

        return redirect()->route('admin.booking.gift-cards.index')
            ->with('success', __('Carte-cadeau mise à jour avec succès.'));
    }

    public function destroy(GiftCard $giftCard)
    {
        $giftCard->delete();

        return redirect()->route('admin.booking.gift-cards.index')
            ->with('success', __('Carte-cadeau supprimée avec succès.'));
    }
}
