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
        $total = $this->cartService->getTotal();

        // Créer la commande
        $order = Order::create([
            'user_id' => auth()->id(),
            'email' => $request->input('email'),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
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

        // Créer session Stripe Checkout
        $stripeUrl = $this->stripeService->createCheckoutSession(
            $cartItems,
            route('shop.confirmation', $order),
            route('shop.cart'),
            $request->input('email')
        );

        if (! $stripeUrl) {
            $order->update(['status' => 'cancelled', 'notes' => 'Stripe checkout session creation failed']);
            return back()->with('error', __('Erreur lors de la création du paiement. Veuillez réessayer.'));
        }

        // Sauvegarder le session ID Stripe
        $order->update(['stripe_session_id' => last(explode('/', parse_url($stripeUrl, PHP_URL_PATH)))]);

        // Vider le panier
        $this->cartService->clear();

        return redirect($stripeUrl);
    }

    public function success(Order $order)
    {
        return view('shop::public.confirmation', compact('order'));
    }
}
