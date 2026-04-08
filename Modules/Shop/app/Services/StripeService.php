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

    public function createCheckoutSession(array $cartItems, string $returnUrl, ?string $customerEmail = null, float $taxAmount = 0, float $shippingCost = 0): ?array
    {
        try {
            $productIds = array_column($cartItems, 'product_id');
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $currency = strtolower(config('shop.currency', 'CAD'));

            $lineItems = [];
            $idx = 0;
            foreach ($cartItems as $item) {
                $product = $products[$item['product_id']] ?? null;
                $lineItems["line_items[{$idx}][price_data][currency]"] = $currency;
                $lineItems["line_items[{$idx}][price_data][product_data][name]"] = ($product?->name ?? 'Produit') . ($item['variant_label'] ? ' — ' . $item['variant_label'] : '');
                $lineItems["line_items[{$idx}][price_data][unit_amount]"] = (int) round($item['unit_price'] * 100);
                $lineItems["line_items[{$idx}][quantity]"] = $item['quantity'];
                $idx++;
            }

            if ($taxAmount > 0) {
                $lineItems["line_items[{$idx}][price_data][currency]"] = $currency;
                $lineItems["line_items[{$idx}][price_data][product_data][name]"] = 'TPS + TVQ';
                $lineItems["line_items[{$idx}][price_data][unit_amount]"] = (int) round($taxAmount * 100);
                $lineItems["line_items[{$idx}][quantity]"] = 1;
                $idx++;
            }

            if ($shippingCost > 0) {
                $lineItems["line_items[{$idx}][price_data][currency]"] = $currency;
                $lineItems["line_items[{$idx}][price_data][product_data][name]"] = 'Livraison';
                $lineItems["line_items[{$idx}][price_data][unit_amount]"] = (int) round($shippingCost * 100);
                $lineItems["line_items[{$idx}][quantity]"] = 1;
            }

            $params = array_merge($lineItems, [
                'mode' => 'payment',
                'ui_mode' => 'embedded',
                'return_url' => $returnUrl,
                'payment_intent_data[statement_descriptor_suffix]' => 'LAVEILLE.AI',
            ]);

            if ($customerEmail) {
                $params['customer_email'] = $customerEmail;
            }

            $response = $this->client()->post("{$this->apiUrl}/checkout/sessions", $params);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'client_secret' => $data['client_secret'],
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

                if ($order && $order->status === 'pending') {
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
