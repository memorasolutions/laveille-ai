<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
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

        $cart = auth()->check()
            ? Cart::where('user_id', auth()->id())->active()->first()
            : Cart::bySession(session()->getId())->active()->first();

        if (! $cart || empty($cart->items)) {
            return response()->json(['methods' => [], 'cheapest_price' => 0]);
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

        return response()->json($quote ?? ['methods' => [], 'cheapest_price' => 0]);
    }
}
