<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Address;
use Modules\Ecommerce\Models\Cart;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\Coupon;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\OrderItem;
use Stripe\Checkout\Session as StripeSession;
use Stripe\StripeClient;
use Stripe\Webhook;

class CheckoutService
{
    public function __construct(
        private CartService $cartService,
        private ShippingService $shippingService,
        private TaxService $taxService,
    ) {}

    public function createOrder(
        User $user,
        Cart $cart,
        Address $shippingAddress,
        ?Address $billingAddress,
        ?Coupon $coupon,
        string $shippingMethod,
    ): Order {
        return DB::transaction(function () use ($user, $cart, $shippingAddress, $billingAddress, $coupon, $shippingMethod) {
            $prefix = (string) config('modules.ecommerce.invoices.prefix', 'INV-');
            $orderNumber = $prefix.now()->format('Ymd').'-'.strtoupper(substr(uniqid(), -6));

            $subtotal = $this->cartService->getTotal($cart);
            $shippingCost = $this->shippingService->calculateShipping($cart, $shippingMethod);
            $taxAmount = $this->taxService->calculateTax($subtotal);
            $discountAmount = $coupon ? $this->calculateDiscount($coupon, $subtotal) : 0.0;
            $total = round($subtotal + $shippingCost + $taxAmount - $discountAmount, 2);

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total' => max(0, $total),
                'coupon_id' => $coupon?->id,
                'shipping_address_id' => $shippingAddress->id,
                'billing_address_id' => $billingAddress?->id,
                'shipping_method' => $shippingMethod,
            ]);

            foreach ($cart->items as $item) {
                /** @var CartItem $item */
                $variant = $item->variant;
                $unitPrice = $variant->price;

                OrderItem::create([
                    'order_id' => $order->id,
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name ?? '',
                    'variant_label' => $variant->sku,
                    'price' => $unitPrice,
                    'quantity' => $item->quantity,
                    'total' => round($unitPrice * $item->quantity, 2),
                ]);

                if (config('modules.ecommerce.stock.track_inventory')) {
                    $variant->decrement('stock', $item->quantity);
                }
            }

            $this->cartService->clear($cart);

            if ($coupon) {
                $coupon->increment('used_count');
            }

            \Modules\Ecommerce\Events\OrderCreated::dispatch($order);

            return $order;
        });
    }

    public function createStripeCheckoutSession(Order $order, string $successUrl, string $cancelUrl): StripeSession
    {
        $stripe = new StripeClient((string) config('services.stripe.secret'));
        $currency = strtolower((string) config('modules.ecommerce.currency', 'cad'));

        $lineItems = $order->items->map(fn (OrderItem $item) => [
            'price_data' => [
                'currency' => $currency,
                'product_data' => ['name' => $item->product_name],
                'unit_amount' => (int) round($item->price * 100),
            ],
            'quantity' => $item->quantity,
        ])->toArray();

        if ($order->shipping_cost > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => ['name' => 'Livraison'],
                    'unit_amount' => (int) round($order->shipping_cost * 100),
                ],
                'quantity' => 1,
            ];
        }

        if ($order->tax_amount > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => $currency,
                    'product_data' => ['name' => 'Taxes'],
                    'unit_amount' => (int) round($order->tax_amount * 100),
                ],
                'quantity' => 1,
            ];
        }

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => ['order_id' => (string) $order->id],
        ]);

        $order->update(['stripe_session_id' => $session->id]);

        return $session;
    }

    public function handleStripeWebhook(string $payload, string $signature): void
    {
        $webhookSecret = (string) config('services.stripe.webhook_secret');
        $event = Webhook::constructEvent($payload, $signature, $webhookSecret);

        if ($event->type === 'checkout.session.completed') {
            /** @var \Stripe\Checkout\Session $session */
            $session = $event->data->object;
            $order = Order::where('stripe_session_id', $session->id)->first();

            if ($order) {
                $order->update([
                    'status' => 'paid',
                    'stripe_payment_intent' => $session->payment_intent,
                    'paid_at' => now(),
                ]);

                /** @var \App\Models\User $user */
                $user = $order->user;
                $user->notify(new \Modules\Ecommerce\Notifications\OrderConfirmationNotification($order));

                \Modules\Ecommerce\Events\OrderPaid::dispatch($order);
            }
        }
    }

    private function calculateDiscount(Coupon $coupon, float $subtotal): float
    {
        if (! $coupon->isValid()) {
            return 0.0;
        }

        return match ($coupon->type) {
            'percent' => round($subtotal * $coupon->value / 100, 2),
            'fixed' => min($coupon->value, $subtotal),
            'free_shipping' => 0.0,
            default => 0.0,
        };
    }
}
