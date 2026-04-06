<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\Cart;
use Modules\Shop\Services\GelatoService;

class ShippingQuoteController extends Controller
{
    public function __invoke(Request $request, GelatoService $gelatoService): JsonResponse
    {
        $request->validate([
            'postal_code' => 'required|string|min:6',
            'country' => 'required|string|size:2',
        ]);

        $empty = ['methods' => [], 'cheapest_price' => 0];

        $cart = auth()->check()
            ? Cart::where('user_id', auth()->id())->active()->first()
            : Cart::bySession(session()->getId())->active()->first();

        if (! $cart || empty($cart->items)) {
            Log::debug('ShippingQuote: panier vide', ['user' => auth()->id(), 'session' => session()->getId()]);
            return response()->json(array_merge($empty, ['debug' => 'panier vide']));
        }

        $hasGelato = collect($cart->items)->contains(fn ($i) => ! empty($i['gelato_variant_id']));
        if (! $hasGelato) {
            Log::warning('ShippingQuote: aucun item avec gelato_variant_id', ['items' => $cart->items]);
            return response()->json(array_merge($empty, ['debug' => 'items sans gelato_variant_id']));
        }

        $shippingAddress = [
            'first_name' => 'Quote',
            'last_name' => 'Request',
            'address_line1' => '',
            'city' => '',
            'postal_code' => $request->input('postal_code'),
            'country' => $request->input('country', 'CA'),
        ];

        $quote = $gelatoService->getQuoteFromCart(
            $cart->items,
            $shippingAddress,
            auth()->user()?->email
        );

        if (! $quote) {
            Log::error('ShippingQuote: Gelato retourne null', ['address' => $shippingAddress]);
            return response()->json(array_merge($empty, ['debug' => 'gelato_null']));
        }

        return response()->json($quote);
    }
}
