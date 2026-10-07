<?php

namespace Modules\Shop\Services;

use Modules\Shop\Gelato\GelatoSubmitException;
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

    /**
     * @param array<int|string,string>|null $printFileUrls  Mode « zéro erreur » : orderItemId => URL publique du print file APPROVED.
     *                                                       Si fourni, ces fichiers préparés remplacent tout autre design.
     * @param string|null $idempotencyKey                    Clé déterministe envoyée en en-tête (mode « zéro erreur »).
     */
    public function createOrder(Order $order, ?array $printFileUrls = null, ?string $idempotencyKey = null): ?string
    {
        try {
            $client = $this->orderClient();
            if ($idempotencyKey !== null) {
                $client = $client->withHeaders(['Idempotency-Key' => $idempotencyKey]);
            }
            $response = $client->post('/v4/orders', $this->buildOrderBody($order, $printFileUrls));

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
     * Soumission « zéro erreur » : ne masque JAMAIS l'issue. Retourne l'id Gelato ou lève
     * GelatoSubmitException (definitive=true seulement pour un rejet 4xx de validation).
     *
     * @param array<int|string,string> $printFileUrls
     * @throws GelatoSubmitException
     */
    public function submitOrderStrict(Order $order, array $printFileUrls, string $idempotencyKey): string
    {
        try {
            $response = $this->orderClient()
                ->withHeaders(['Idempotency-Key' => $idempotencyKey])
                ->post('/v4/orders', $this->buildOrderBody($order, $printFileUrls));
        } catch (\Throwable $e) {
            // Connexion coupée / timeout : la requête a pu partir, la commande peut exister.
            throw new GelatoSubmitException('Réponse Gelato perdue : '.$e->getMessage(), false);
        }

        if ($response->successful()) {
            $id = $response->json('id');
            if (! is_string($id) || $id === '') {
                throw new GelatoSubmitException('Gelato a répondu '.$response->status().' sans identifiant de commande', false, $response->status());
            }

            return $id;
        }

        $status = $response->status();
        $detail = 'HTTP '.$status.' '.mb_substr($response->body(), 0, 300);
        // 4xx = rejet de validation avant création, sauf délai (408), conflit (409) et limite de débit (429) : ambigus.
        $definitive = $status >= 400 && $status < 500 && ! in_array($status, [408, 409, 429], true);

        throw new GelatoSubmitException('Rejet Gelato : '.$detail, $definitive, $status);
    }

    /**
     * Réconciliation : id de l'UNIQUE commande Gelato valide (ni refusée, ni échouée, ni annulée) portant cet orderReferenceId,
     * null si aucune valide. Plusieurs valides = ambigu : lève (jamais de choix arbitraire).
     * TODO(vérifier en réel) : endpoint de recherche v4 `POST /v4/orders:search` (filtre `orderReferenceIds`),
     * non éprouvé contre l'API live - toute réponse inattendue lève (jamais un « introuvable » deviné).
     *
     * @throws GelatoSubmitException si la recherche elle-même échoue (issue inconnue)
     */
    public function findOrderIdByReference(string $orderReferenceId): ?string
    {
        try {
            $response = $this->orderClient()->post('/v4/orders:search', [
                'orderReferenceIds' => [$orderReferenceId],
                'limit' => 5,
            ]);
        } catch (\Throwable $e) {
            throw new GelatoSubmitException('Recherche Gelato impossible : '.$e->getMessage(), false);
        }

        if (! $response->successful() || ! is_array($response->json('orders'))) {
            throw new GelatoSubmitException('Recherche Gelato inexploitable (HTTP '.$response->status().')', false, $response->status());
        }

        // Égalité STRICTE sur la référence, id non vide, et on écarte les commandes refusées / échouées / annulées
        // (une même référence peut porter une tentative refusée ET une commande valide).
        $valid = [];
        foreach ($response->json('orders') as $found) {
            if (! is_array($found) || ($found['orderReferenceId'] ?? null) !== $orderReferenceId || empty($found['id'])) {
                continue;
            }
            if (in_array($found['financialStatus'] ?? null, ['refused', 'canceled'], true)
                || in_array($found['fulfillmentStatus'] ?? null, ['failed', 'canceled'], true)) {
                continue;
            }
            $valid[(string) $found['id']] = true;
        }

        if (count($valid) > 1) {
            throw new GelatoSubmitException("Plusieurs commandes Gelato valides pour la référence {$orderReferenceId} (".implode(', ', array_keys($valid))."), intervention manuelle requise", false, $response->status());
        }

        return $valid === [] ? null : (string) array_key_first($valid);
    }

    /** @param array<int|string,string>|null $printFileUrls */
    private function buildOrderBody(Order $order, ?array $printFileUrls): array
    {
        $address = $order->shipping_address ?? [];

        return [
            'orderReferenceId' => $order->order_number ?? (string) $order->id,
            'customerReferenceId' => (string) ($order->user_id ?? $order->email),
            'currency' => strtoupper(config('shop.currency', 'CAD')),
            'items' => $order->items->map(function ($item) use ($printFileUrls) {
                return $printFileUrls !== null
                    ? $this->buildPreparedItemPayload($item, $printFileUrls[$item->id])
                    : $this->buildOrderItemPayload($item);
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
    }

    /** Item « zéro erreur » : productUid + fichier d'impression préparé et approuvé (jamais un fichier brut). */
    private function buildPreparedItemPayload($item, string $printFileUrl): array
    {
        return [
            'itemReferenceId' => (string) $item->id,
            'productUid' => $item->gelato_variant_id,
            'quantity' => $item->quantity,
            'files' => [['type' => 'default', 'url' => $printFileUrl]],
        ];
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
