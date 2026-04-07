<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderItem;
use Modules\Shop\Services\CartService;
use Modules\Shop\Services\StripeService;
use Modules\Shop\Events\ShopOrderCreated;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected StripeService $stripeService,
    ) {}

    public function create(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'shipping_address' => 'required|array',
            'shipping_address.first_name' => 'required|string',
            'shipping_address.last_name' => 'required|string',
            'shipping_address.address_line1' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.postal_code' => 'required|string',
            'shipping_address.country' => 'required|string|size:2',
        ]);

        $cartItems = $this->cartService->getContent();

        if (empty($cartItems)) {
            return back()->with('error', __('Votre panier est vide.'));
        }

        $subtotal = $this->cartService->getSubtotal();
        $taxAmount = $this->cartService->getTaxAmount();
        $shippingCost = (float) $request->input('shipping_cost', 0);
        $total = round($this->cartService->getTotal() + $shippingCost, 2);

        // Créer la commande
        $order = Order::create([
            'user_id' => auth()->id(),
            'email' => $request->input('email'),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'total' => $total,
            'shipping_address' => $request->input('shipping_address'),
        ]);

        // Créer les items de commande
        foreach ($cartItems as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['product_id'],
                'variant_label' => $item['variant_label'] ?? null,
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'gelato_variant_id' => $item['gelato_variant_id'] ?? null,
            ]);
        }

        event(new ShopOrderCreated($order));

        // Inscription newsletter si coché (LCAP conforme — opt-in explicite)
        if ($request->input('newsletter') && class_exists(\Modules\Newsletter\Models\Subscriber::class)) {
            \Modules\Newsletter\Models\Subscriber::updateOrCreate(
                ['email' => $request->input('email')],
                ['status' => 'subscribed', 'subscribed_at' => now()]
            );
        }

        // Créer session Stripe Checkout (mode embedded)
        $returnUrl = route('shop.confirmation', $order) . '?session_id={CHECKOUT_SESSION_ID}';
        $checkout = $this->stripeService->createCheckoutSession(
            $cartItems,
            $returnUrl,
            $request->input('email')
        );

        if (! $checkout) {
            $order->update(['status' => 'cancelled', 'notes' => 'Stripe checkout session creation failed']);
            return back()->with('error', __('Erreur lors de la création du paiement. Veuillez réessayer.'));
        }

        // Sauvegarder le session ID Stripe
        $order->update(['stripe_session_id' => $checkout['session_id']]);

        // Vider le panier
        $this->cartService->clear();

        return redirect()->route('shop.checkout.pay', $order)
            ->with('stripe_client_secret', $checkout['client_secret']);
    }

    public function pay(Order $order)
    {
        $clientSecret = session('stripe_client_secret');

        if (! $clientSecret) {
            return redirect()->route('shop.cart')->with('error', __('Session de paiement expirée. Veuillez réessayer.'));
        }

        return view('shop::public.checkout-pay', [
            'order' => $order,
            'clientSecret' => $clientSecret,
            'stripeKey' => config('shop.stripe.publishable_key'),
        ]);
    }

    public function success(Order $order)
    {
        return view('shop::public.confirmation', compact('order'));
    }
}
