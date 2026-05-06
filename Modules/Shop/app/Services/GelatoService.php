<?php

namespace Modules\Shop\Services;

use Modules\Shop\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GelatoService
{
    public function isConfigured(): bool
    {
        return ! empty(config('shop.gelato.api_key'));
    }

    private function client()
    {
        return Http::withHeaders([
            'X-API-KEY' => config('shop.gelato.api_key'),
        ])->baseUrl(config('shop.gelato.api_url'));
    }

    private function orderClient()
    {
        return Http::withHeaders([
            'X-API-KEY' => config('shop.gelato.api_key'),
        ])->baseUrl('https://order.gelatoapis.com');
    }

    public function getCatalogs(): array
    {
        try {
            return $this->client()->get('/v3/catalogs')->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Gelato getCatalogs: ' . $e->getMessage());
            return [];
        }
    }

    public function searchProducts(string $catalogUid, array $filters = []): array
    {
        try {
            return $this->client()
                ->post("/v3/catalogs/{$catalogUid}/products:search", $filters)
                ->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Gelato searchProducts: ' . $e->getMessage());
            return [];
        }
    }

    public function createOrder(Order $order): ?string
    {
        try {
            $address = $order->shipping_address ?? [];

            $body = [
                'orderReferenceId' => $order->order_number ?? (string) $order->id,
                'customerReferenceId' => (string) ($order->user_id ?? $order->email),
                'currency' => strtoupper(config('shop.currency', 'CAD')),
                'items' => $order->items->map(function ($item) {
                    return $this->buildOrderItemPayload($item);
                })->toArray(),
                'shippingAddress' => [
                    'firstName' => $address['first_name'] ?? '',
                    'lastName' => $address['last_name'] ?? '',
                    'addressLine1' => $address['address_line1'] ?? '',
                    'addressLine2' => $address['address_line2'] ?? '',
                    'city' => $address['city'] ?? '',
                    'state' => $address['state'] ?? '',
                    'postCode' => $address['postal_code'] ?? '',
                    'country' => $address['country'] ?? 'CA',
                    'email' => $order->email,
                ],
            ];

            $response = $this->orderClient()->post('/v4/orders', $body);

            if ($response->successful()) {
                return $response->json('id');
            }

            Log::error('Gelato createOrder failed: ' . $response->body());
            return null;
        } catch (\Exception $e) {
            Log::error('Gelato createOrder: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Construit le payload Gelato pour un OrderItem.
     *
     * Priorité 1 : storeProductVariantId (store Gelato publié = design source de vérité)
     *   → Gelato utilise automatiquement le design uploadé dans le store, pas de fileUrl à fournir.
     *   → Évite les régressions de design (ex : t-shirt #210 imprimé avec ancien fileUrl DB stale).
     *
     * Priorité 2 (fallback) : productUid + files custom (ancien comportement)
     *   → Utilisé si store non publié, ou variant non synchronisé via shop:sync-gelato.
     *   → Loggé en warning pour traçabilité.
     */
    private function buildOrderItemPayload($item): array
    {
        $product = $item->product;
        $productUid = $item->gelato_variant_id;

        $storeVariantId = $product->metadata['store_variant_map'][$productUid] ?? null;

        if ($storeVariantId) {
            return [
                'itemReferenceId' => (string) $item->id,
                'storeProductVariantId' => $storeVariantId,
                'quantity' => $item->quantity,
            ];
        }

        Log::warning('Gelato createOrder fallback : storeProductVariantId absent, utilisation productUid+files', [
            'order_item_id' => $item->id,
            'product_uid' => $productUid,
            'product_id' => $product->id ?? null,
        ]);

        $entry = [
            'itemReferenceId' => (string) $item->id,
            'productUid' => $productUid,
            'quantity' => $item->quantity,
        ];

        if ($product && !empty($product->metadata['print_file_url'])) {
            $entry['files'] = [['type' => 'default', 'url' => $product->metadata['print_file_url']]];
        }

        return $entry;
    }

    public function getOrder(string $gelatoOrderId): array
    {
        try {
            return $this->orderClient()->get("/v4/orders/{$gelatoOrderId}")->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Gelato getOrder: ' . $e->getMessage());
            return [];
        }
    }

    public function getShippingMethods(string $country = 'CA'): array
    {
        try {
            return $this->client()
                ->get('/v1/shipment-methods', ['country' => $country])
                ->json() ?? [];
        } catch (\Exception $e) {
            Log::error('Gelato getShippingMethods: ' . $e->getMessage());
            return [];
        }
    }

    public function getQuote(Order $order): ?array
    {
        return $this->requestQuote(
            'quote-' . $order->id,
            (string) ($order->user_id ?? $order->email),
            $order->shipping_address ?? [],
            $order->email,
            $order->items->map(fn ($item) => [
                'itemReferenceId' => (string) $item->id,
                'productUid' => $item->gelato_variant_id,
                'quantity' => $item->quantity,
            ])->toArray()
        );
    }

    public function getQuoteFromCart(array $cartItems, array $shippingAddress, ?string $email = null): ?array
    {
        $products = array_map(fn ($item) => [
            'itemReferenceId' => (string) ($item['product_id'] ?? uniqid()),
            'productUid' => $item['gelato_variant_id'] ?? '',
            'quantity' => $item['quantity'] ?? 1,
        ], $cartItems);

        return $this->requestQuote(
            'quote-cart-' . uniqid(),
            $email ?? 'guest',
            $shippingAddress,
            $email,
            $products
        );
    }

    private function requestQuote(string $orderRefId, string $customerRefId, array $address, ?string $email, array $products): ?array
    {
        try {
            $body = [
                'orderReferenceId' => $orderRefId,
                'customerReferenceId' => $customerRefId,
                'currency' => config('shop.currency', 'CAD'),
                'recipient' => [
                    'firstName' => $address['first_name'] ?? '',
                    'lastName' => $address['last_name'] ?? '',
                    'addressLine1' => $address['address_line1'] ?? '',
                    'city' => $address['city'] ?? '',
                    'postCode' => $address['postal_code'] ?? '',
                    'country' => $address['country'] ?? 'CA',
                    'email' => $email ?? '',
                ],
                'products' => $products,
            ];

            $response = $this->orderClient()->post('/v4/orders:quote', $body);

            if (! $response->successful()) {
                Log::error('Gelato getQuote failed: ' . $response->body());
                return null;
            }

            $data = $response->json();
            $allMethods = [];

            foreach ($data['quotes'] ?? [] as $quote) {
                foreach ($quote['shipmentMethods'] ?? [] as $method) {
                    $handlingFee = (float) config('shop.handling_fee', 1.00);
                    $allMethods[] = [
                        'name' => $method['name'] ?? '',
                        'uid' => $method['shipmentMethodUid'] ?? '',
                        'price' => round((float) ($method['price'] ?? 0) + $handlingFee, 2),
                        'currency' => $method['currency'] ?? 'CAD',
                        'min_days' => $method['minDeliveryDays'] ?? null,
                        'max_days' => $method['maxDeliveryDays'] ?? null,
                    ];
                }
            }

            usort($allMethods, fn ($a, $b) => $a['price'] <=> $b['price']);

            return [
                'methods' => $allMethods,
                'cheapest_price' => $allMethods[0]['price'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Gelato getQuote: ' . $e->getMessage());
            return null;
        }
    }
}
