<?php

namespace Modules\Shop\Services;

use Modules\Shop\Gelato\GelatoSubmitException;
use Modules\Shop\Gelato\OrderItemRouter;
use Modules\Shop\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GelatoService
{
    /** Taille de page de la recherche de réconciliation (une réponse pleine = potentiellement tronquée). */
    public const SEARCH_LIMIT = 50;

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

    /** Dernière issue en échec de createOrder() (null si succès) : permet au listener de distinguer rejet définitif / ambigu. */
    public ?GelatoSubmitException $lastFailure = null;

    /**
     * Les items sont routés PAR PRODUIT par OrderItemRouter (catalogue = storeProductVariantId, éditeur = fichier approuvé).
     * Variante « simple » (drapeau OFF) : retourne null en cas d'échec, mais la CAUSE classée (rejet définitif 4xx
     * ou issue ambiguë) reste lisible dans $lastFailure - un 400 de validation ne doit jamais rester « inconnu ».
     *
     * @param string|null $idempotencyKey Clé déterministe envoyée en en-tête.
     */
    public function createOrder(Order $order, ?string $idempotencyKey = null, ?array $body = null): ?string
    {
        $this->lastFailure = null;
        // Corps construit HORS du try : un échec de routage n'est pas une issue ambiguë, rien n'est parti.
        $body ??= $this->buildOrderBody($order);

        try {
            return $this->postOrder($body, $idempotencyKey);
        } catch (GelatoSubmitException $e) {
            $this->lastFailure = $e;
            Log::error('Gelato createOrder: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Soumission « zéro erreur » : ne masque JAMAIS l'issue. Retourne l'id Gelato ou lève
     * GelatoSubmitException (definitive=true seulement pour un rejet 4xx de validation).
     *
     * @throws GelatoSubmitException
     * @throws \Modules\Shop\Gelato\GelatoRoutingException|\Modules\Shop\Gelato\PrintFileNotApprovedException avant tout envoi (routage impossible)
     */
    public function submitOrderStrict(Order $order, string $idempotencyKey, ?array $body = null): string
    {
        // Corps construit HORS du try : un échec de routage n'est pas une issue ambiguë, rien n'est parti.
        $body ??= $this->buildOrderBody($order);

        return $this->postOrder($body, $idempotencyKey);
    }

    /** @throws GelatoSubmitException */
    private function postOrder(array $body, ?string $idempotencyKey): string
    {
        try {
            $client = $this->orderClient();
            if ($idempotencyKey !== null) {
                $client = $client->withHeaders(['Idempotency-Key' => $idempotencyKey]);
            }
            $response = $client->post('/v4/orders', $body);
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

    /** Statuts EXPLICITEMENT bons : seule une commande dont les DEUX statuts sont dans ces listes peut être adoptée. */
    private const ADOPTABLE_FINANCIAL = ['paid', 'pending', 'partially_paid'];
    // `pending_approval` est VOLONTAIREMENT absent : l'impression exige une approbation humaine chez Gelato, ce n'est pas une commande
    // « en cours de traitement ». Elle tombe dans les entrées non résolues (jamais adoptée, alerte admin, aucune re-création).
    // `invoiced` / `to_be_invoiced` (financier) et `passed` combiné restent aussi non résolus : échec SÛR (revue manuelle),
    // faute de certitude documentée sur leur sens exact - on n'élargit jamais au risque d'une adoption erronée.
    private const ADOPTABLE_FULFILLMENT = ['created', 'passed', 'in_production', 'printed', 'shipped', 'delivered'];

    /** Marqueur d'environnement gravé sur chaque commande envoyée (métadonnée) : isole prod / préproduction / local d'un même compte Gelato. */
    public function envMarker(): string
    {
        $configured = (string) config('shop.gelato_env_marker', '');

        return $configured !== '' ? $configured : (string) (parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'unknown');
    }

    /**
     * Réconciliation : id de l'UNIQUE commande Gelato valide et NÔTRE portant cet orderReferenceId, null si aucune.
     * - adoptée seulement si les DEUX statuts sont présents ET explicitement bons (jamais draft/draft, jamais un statut inconnu);
     * - refusée / échouée / annulée = écartée (une même référence peut porter une tentative morte ET une valide);
     * - toute autre entrée portant la référence (statut absent, incomplet, non reconnu) = AMBIGUË : lève, jamais d'adoption
     *   (on ne peut pas prouver que la « bonne » est la seule);
     * - entrée d'un AUTRE environnement du même compte (marqueur différent) = écartée; marqueur absent = ambigu (fail-closed);
     * - `pending_approval` = jamais adoptée (approbation humaine requise), traitée comme non résolue avec alerte.
     * TODO(vérifier en réel) : endpoint de recherche v4 `POST /v4/orders:search` (filtre `orderReferenceIds`) et exposition
     * des `metadata` dans la réponse, non éprouvés contre l'API live - toute réponse inattendue lève (jamais un « introuvable » deviné).
     *
     * @throws GelatoSubmitException si la recherche elle-même échoue ou si l'issue est ambiguë (issue inconnue)
     */
    public function findOrderIdByReference(string $orderReferenceId, ?Order $order = null): ?string
    {
        try {
            $response = $this->orderClient()->post('/v4/orders:search', [
                'orderReferenceIds' => [$orderReferenceId],
                'limit' => self::SEARCH_LIMIT,
            ]);
        } catch (\Throwable $e) {
            throw new GelatoSubmitException('Recherche Gelato impossible : '.$e->getMessage(), false);
        }

        if (! $response->successful() || ! is_array($response->json('orders'))) {
            throw new GelatoSubmitException('Recherche Gelato inexploitable (HTTP '.$response->status().')', false, $response->status());
        }

        // Réponse potentiellement TRONQUÉE (autant de résultats que la limite demandée) : une 2e commande valide
        // pourrait manquer, donc adopter serait un pari. Non déterminable => lève, jamais d'adoption.
        if (count($response->json('orders')) >= self::SEARCH_LIMIT) {
            throw new GelatoSubmitException("Recherche Gelato potentiellement tronquée pour la référence {$orderReferenceId} (".self::SEARCH_LIMIT.' résultats ou plus), intervention manuelle requise', false, $response->status());
        }

        $valid = [];
        $unresolved = [];
        foreach ($response->json('orders') as $found) {
            // Égalité STRICTE sur la référence, id non vide.
            if (! is_array($found) || ($found['orderReferenceId'] ?? null) !== $orderReferenceId || empty($found['id'])) {
                continue;
            }

            $financial = is_string($found['financialStatus'] ?? null) ? strtolower($found['financialStatus']) : '';
            $fulfillment = is_string($found['fulfillmentStatus'] ?? null) ? strtolower($found['fulfillmentStatus']) : '';

            if (in_array($financial, ['refused', 'canceled', 'cancelled'], true) || in_array($fulfillment, ['failed', 'canceled', 'cancelled'], true)) {
                continue; // tentative morte, prouvée par un statut terminal
            }

            // Isolation d'environnement FAIL-CLOSED : un marqueur d'un autre environnement écarte l'entrée; un marqueur ABSENT
            // (alors que nous en gravons toujours un) ne prouve pas qu'elle est nôtre => ambigu, jamais d'adoption.
            $env = $this->environmentOf($found);
            if ($env === 'foreign') {
                continue;
            }
            if ($env === 'unknown') {
                $unresolved[(string) $found['id']] = 'marqueur d\'environnement absent';

                continue;
            }
            if (in_array($financial, self::ADOPTABLE_FINANCIAL, true) && in_array($fulfillment, self::ADOPTABLE_FULFILLMENT, true)) {
                $valid[(string) $found['id']] = true;

                continue;
            }
            $unresolved[(string) $found['id']] = ($financial ?: '?').'/'.($fulfillment ?: '?');
        }

        if ($unresolved !== []) {
            $detail = implode(', ', array_map(fn ($id, $st) => "{$id} [{$st}]", array_keys($unresolved), $unresolved));
            throw new GelatoSubmitException("Commande(s) Gelato de statut incomplet ou non reconnu pour la référence {$orderReferenceId} ({$detail}), aucune adoption, intervention manuelle requise", false, $response->status());
        }
        if (count($valid) > 1) {
            throw new GelatoSubmitException("Plusieurs commandes Gelato valides pour la référence {$orderReferenceId} (".implode(', ', array_keys($valid))."), intervention manuelle requise", false, $response->status());
        }

        return $valid === [] ? null : (string) array_key_first($valid);
    }

    /**
     * Environnement d'une commande trouvée, d'après le marqueur `memora_env` qu'on grave à la création.
     * 'ours' = marqueur identique au nôtre; 'foreign' = marqueur différent; 'unknown' = marqueur absent de la réponse
     * (jamais deviné par un repli sur le client de référence : ce serait ouvrir le filtre au lieu de le fermer).
     */
    private function environmentOf(array $found): string
    {
        foreach ((array) ($found['metadata'] ?? []) as $meta) {
            if (is_array($meta) && ($meta['key'] ?? null) === 'memora_env' && isset($meta['value'])) {
                return (string) $meta['value'] === $this->envMarker() ? 'ours' : 'foreign';
            }
        }

        return 'unknown';
    }

    /** @throws \Modules\Shop\Gelato\GelatoRoutingException|\Modules\Shop\Gelato\PrintFileNotApprovedException */
    public function buildOrderBody(Order $order): array
    {
        $address = $order->shipping_address ?? [];
        $router = app(OrderItemRouter::class);

        $body = [
            'orderReferenceId' => $order->order_number ?? (string) $order->id,
            'customerReferenceId' => (string) ($order->user_id ?? $order->email),
            'currency' => strtoupper(config('shop.currency', 'CAD')),
            'metadata' => [['key' => 'memora_env', 'value' => $this->envMarker()]],
            'items' => $order->items->map(fn ($item) => $router->resolvePayload($item))->values()->toArray(),
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

        // Mode de livraison CHOISI et payé par le client : sans lui, Gelato prendrait la méthode la moins chère.
        if (! empty($order->shipping_method_uid)) {
            $body['shipmentMethodUid'] = $order->shipping_method_uid;
        }

        return $body;
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
        // Référence = CLÉ de ligne du panier (unique), jamais product_id : deux variantes du même produit partageaient
        // la même référence et Gelato pouvait rejeter le devis (400), donc la commande.
        $products = [];
        foreach ($cartItems as $cartKey => $item) {
            $products[] = [
                'itemReferenceId' => 'line-'.$cartKey,
                'productUid' => $item['gelato_variant_id'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
            ];
        }

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
            $handlingFee = (float) config('shop.handling_fee', 1.00);

            // Un panier réparti entre PLUSIEURS centres de production donne plusieurs devis : chacun a SON expédition à payer.
            // On additionne donc, pour une même méthode (uid), les frais de TOUS les devis; une méthode absente d'un seul d'entre
            // eux n'est pas offerte (jamais sous-facturer). Aucune méthode commune => refus (null), le client ne passe pas.
            $quotes = array_values(array_filter((array) ($data['quotes'] ?? []), 'is_array'));
            $byUid = [];
            foreach ($quotes as $i => $quote) {
                foreach ($quote['shipmentMethods'] ?? [] as $method) {
                    $uid = (string) ($method['shipmentMethodUid'] ?? '');
                    if ($uid === '' || isset($byUid[$uid][$i])) {
                        continue;
                    }
                    $byUid[$uid][$i] = $method;
                }
            }

            $allMethods = [];
            foreach ($byUid as $uid => $perQuote) {
                if (count($perQuote) !== count($quotes)) {
                    continue; // méthode non disponible pour tous les centres
                }
                $first = reset($perQuote);
                $allMethods[] = [
                    'name' => $first['name'] ?? '',
                    'uid' => (string) $uid,
                    'price' => round(array_sum(array_map(fn ($m) => (float) ($m['price'] ?? 0), $perQuote)) + $handlingFee, 2),
                    'currency' => $first['currency'] ?? 'CAD',
                    'min_days' => max(array_map(fn ($m) => (int) ($m['minDeliveryDays'] ?? 0), $perQuote)) ?: null,
                    'max_days' => max(array_map(fn ($m) => (int) ($m['maxDeliveryDays'] ?? 0), $perQuote)) ?: null,
                ];
            }

            if ($quotes !== [] && $allMethods === []) {
                Log::error('Gelato getQuote : aucune méthode de livraison commune à tous les centres, livraison refusée');

                return null;
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
