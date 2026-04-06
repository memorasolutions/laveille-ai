<?php

namespace Modules\Shop\Services;

use Modules\Shop\Models\Order;
use Modules\Shop\Models\Cart;
use Modules\Shop\Models\Product;
use Modules\Shop\Events\ShopOrderPaid;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

class StripeService
{
    private string $apiUrl = 'https://api.stripe.com/v1';

    public function isConfigured(): bool
    {
        return ! empty(config('shop.stripe.secret_key'));
    }

    private function client()
    {
        return Http::withBasicAuth(config('shop.stripe.secret_key'), '')
            ->asForm();
    }

    public function createCheckoutSession(array $cartItems, string $successUrl, string $cancelUrl, ?string $customerEmail = null): ?array
    {
        try {
            $productIds = array_column($cartItems, 'product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

            $lineItems = [];
            foreach ($cartItems as $i => $item) {
                $product = $products[$item['product_id']] ?? null;
                $lineItems["line_items[{$i}][price_data][currency]"] = strtolower(config('shop.currency', 'CAD'));
                $lineItems["line_items[{$i}][price_data][product_data][name]"] = $product?->name ?? 'Produit';
                $lineItems["line_items[{$i}][price_data][unit_amount]"] = (int) round($item['unit_price'] * 100);
                $lineItems["line_items[{$i}][quantity]"] = $item['quantity'];
            }

            $params = array_merge($lineItems, [
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'shipping_address_collection[allowed_countries][0]' => 'CA',
            ]);

            if ($customerEmail) {
                $params['customer_email'] = $customerEmail;
            }

            $response = $this->client()->post("{$this->apiUrl}/checkout/sessions", $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'url' => $data['url'],
                    'session_id' => $data['id'],
                ];
            }

            Log::error('Stripe createCheckoutSession failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Stripe createCheckoutSession: ' . $e->getMessage());
            return null;
        }
    }

    public function handleWebhook(Request $request): void
    {
        try {
            $payload = $request->getContent();
            $sigHeader = $request->header('Stripe-Signature');

            if (! $this->verifySignature($payload, $sigHeader)) {
                Log::warning('Stripe webhook: signature invalide');
                return;
            }

            $event = json_decode($payload, true);

            if (($event['type'] ?? '') === 'checkout.session.completed') {
                $session = $event['data']['object'];
                $order = Order::where('stripe_session_id', $session['id'])->first();

                if ($order) {
                    $order->update([
                        'status' => 'paid',
                        'stripe_payment_intent_id' => $session['payment_intent'] ?? null,
                    ]);
                    event(new ShopOrderPaid($order));
                }
            }
        } catch (\Exception $e) {
            Log::error('Stripe webhook: ' . $e->getMessage());
        }
    }

    public function refund(string $paymentIntentId, ?int $amountCents = null): bool
    {
        try {
            $data = ['payment_intent' => $paymentIntentId];

            if ($amountCents !== null) {
                $data['amount'] = $amountCents;
            }

            return $this->client()
                ->post("{$this->apiUrl}/refunds", $data)
                ->successful();
        } catch (\Exception $e) {
            Log::error('Stripe refund: ' . $e->getMessage());
            return false;
        }
    }

    private function verifySignature(string $payload, ?string $sigHeader): bool
    {
        if (! $sigHeader) {
            return false;
        }

        $secret = config('shop.stripe.webhook_secret');
        if (! $secret) {
            return true; // Pas de secret configuré = pas de vérification
        }

        // Parser le header Stripe-Signature
        $parts = [];
        foreach (explode(',', $sigHeader) as $part) {
            [$key, $value] = explode('=', trim($part), 2);
            $parts[$key] = $value;
        }

        $timestamp = $parts['t'] ?? '';
        $signature = $parts['v1'] ?? '';

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }
}
