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
                'orderReferenceId' => (string) $order->id,
                'customerReferenceId' => (string) ($order->user_id ?? $order->email),
                'items' => $order->items->map(fn ($item) => [
                    'itemReferenceId' => (string) $item->id,
                    'productUid' => $item->gelato_variant_id,
                    'quantity' => $item->quantity,
                ])->toArray(),
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

            $response = $this->client()->post('/v4/orders', $body);

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

    public function getOrder(string $gelatoOrderId): array
    {
        try {
            return $this->client()->get("/v4/orders/{$gelatoOrderId}")->json() ?? [];
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
}
