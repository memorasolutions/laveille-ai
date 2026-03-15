<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Controllers\BaseApiController;
use Modules\Ecommerce\Models\Address;
use Modules\Ecommerce\Models\Coupon;
use Modules\Ecommerce\Services\CartService;
use Modules\Ecommerce\Services\CheckoutService;

class CheckoutApiController extends BaseApiController
{
    public function __construct(
        protected CartService $cartService,
        protected CheckoutService $checkoutService,
    ) {}

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address_id' => ['required', 'integer', 'exists:ecommerce_addresses,id'],
            'billing_address_id' => ['nullable', 'integer', 'exists:ecommerce_addresses,id'],
            'coupon_code' => ['nullable', 'string'],
            'shipping_method' => ['required', 'string'],
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
        ]);

        $user = $request->user();
        $cart = $this->cartService->getOrCreateCart($user);
        $cart->load('items.variant.product');

        if ($cart->items->isEmpty()) {
            return $this->respondError('Le panier est vide.', 422);
        }

        $shippingAddress = Address::findOrFail($validated['shipping_address_id']);
        $billingAddress = ! empty($validated['billing_address_id']) ? Address::find($validated['billing_address_id']) : null;
        $coupon = ! empty($validated['coupon_code']) ? Coupon::where('code', $validated['coupon_code'])->first() : null;

        $order = $this->checkoutService->createOrder(
            $user,
            $cart,
            $shippingAddress,
            $billingAddress,
            $coupon,
            $validated['shipping_method'],
        );

        $session = $this->checkoutService->createStripeCheckoutSession(
            $order,
            $validated['success_url'],
            $validated['cancel_url'],
        );

        return $this->respondSuccess(['checkout_url' => $session->url]);
    }
}
