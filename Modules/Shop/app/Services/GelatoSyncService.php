<?php

declare(strict_types=1);

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\Product;

/**
 * Sync produit Gelato store → DB locale shop_products.
 *
 * Lit l'endpoint `/v3/stores/{storeId}/products/{productId}` (ecommerce API)
 * et transforme la réponse en JSON variants stocké dans shop_products.variants.
 *
 * Source de vérité = Gelato store. La DB locale est un cache requêtable.
 * Re-exécution idempotente : upsert sur slug.
 */
class GelatoSyncService
{
    /** Map nom couleur Gelato → hex (Gildan 5000 standards 2026). */
    private const COLOR_HEX_MAP = [
        'white' => '#FFFFFF',
        'natural' => '#E8DCC4',
        'kiwi' => '#A4C639',
        'carolina-blue' => '#7BAFD4',
        'royal' => '#1F3A93',
        'dark-heather' => '#4D4F53',
        'cardinal-red' => '#8B1A1A',
        'forest-green' => '#1B3A28',
        'black' => '#000000',
    ];

    /**
     * Surcharge prix par taille (Gelato facture plus cher au-dessus de XL).
     * Pourcentage du prix de base.
     */
    private const SIZE_SURCHARGE = [
        'S' => 0.00, 'M' => 0.00, 'L' => 0.00, 'XL' => 0.00,
        '2XL' => 3.00, '3XL' => 5.00, '4XL' => 7.00, '5XL' => 9.00,
    ];

    public function isConfigured(): bool
    {
        return ! empty(config('shop.gelato.api_key')) && ! empty(config('shop.gelato.store_id'));
    }

    /**
     * Récupère un product depuis Gelato store API (ecommerce, pas catalog).
     */
    public function fetchStoreProduct(string $storeId, string $productId): ?array
    {
        try {
            $response = Http::withHeaders([
                'X-API-KEY' => config('shop.gelato.api_key'),
            ])->baseUrl('https://ecommerce.gelatoapis.com')
              ->get("/v1/stores/{$storeId}/products/{$productId}");

            if (! $response->successful()) {
                Log::error('GelatoSyncService.fetchStoreProduct failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('GelatoSyncService.fetchStoreProduct exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Transforme la réponse Gelato en JSON variants utilisable par la vue Product.
     *
     * Format de sortie attendu par show.blade.php (Alpine x-data):
     *   [
     *     {
     *       "color": "Blanc",
     *       "color_slug": "white",
     *       "color_hex": "#FFFFFF",
     *       "size_prices": { "S": 20.99, "M": 20.99, ..., "5XL": 29.99 },
     *       "variant_ids": { "S": "uuid", "M": "uuid", ... },
     *       "product_uids": { "S": "apparel_product_...", ... },
     *       "mockup_url": "https://...",
     *       "sort_order": 0
     *     }
     *   ]
     */
    public function transformToLocalVariants(array $gelatoProduct, float $basePrice): array
    {
        $variants = $gelatoProduct['variants'] ?? [];
        $attributes = $gelatoProduct['productVariantAttributes'] ?? [];
        $images = $gelatoProduct['productImages'] ?? [];

        $colorOrder = [];
        foreach ($attributes as $attr) {
            if ($attr['name'] === 'Couleur') {
                foreach ($attr['values'] as $i => $v) {
                    $slug = $v['keys'][0]['value'] ?? strtolower($v['value']);
                    $colorOrder[$slug] = ['label' => $v['value'], 'order' => $i];
                }
            }
        }

        // Map image fileUrl par variant_id (un mockup peut couvrir N variants)
        $imageByVariantId = [];
        foreach ($images as $img) {
            foreach (($img['productVariantIds'] ?? []) as $vid) {
                if (! isset($imageByVariantId[$vid])) {
                    $imageByVariantId[$vid] = $img['fileUrl'] ?? null;
                }
            }
        }
        $primaryImage = collect($images)->firstWhere('isPrimary', true)['fileUrl']
            ?? ($images[0]['fileUrl'] ?? null);

        $grouped = [];
        foreach ($variants as $v) {
            $title = $v['title'] ?? '';
            // Parse "Color - Size - DTG..." → extract color + size
            $parts = array_map('trim', explode('-', $title));
            $colorLabel = $parts[0] ?? 'Unknown';
            $size = $parts[1] ?? 'M';

            // Color slug depuis productUid : ...gco_<slug>_gpr...
            $productUid = $v['productUid'] ?? '';
            $colorSlug = 'unknown';
            if (preg_match('/_gco_([a-z\-]+)_gpr/', $productUid, $m)) {
                $colorSlug = $m[1];
            }

            $price = round($basePrice + (self::SIZE_SURCHARGE[$size] ?? 0), 2);

            if (! isset($grouped[$colorSlug])) {
                $grouped[$colorSlug] = [
                    'color' => $colorOrder[$colorSlug]['label'] ?? $colorLabel,
                    'color_slug' => $colorSlug,
                    'color_hex' => self::COLOR_HEX_MAP[$colorSlug] ?? '#888888',
                    'label' => $colorOrder[$colorSlug]['label'] ?? $colorLabel,
                    'size_prices' => [],
                    'variant_ids' => [],
                    'product_uids' => [],
                    'mockup_url' => $imageByVariantId[$v['id']] ?? $primaryImage,
                    'sort_order' => $colorOrder[$colorSlug]['order'] ?? 99,
                ];
            }
            $grouped[$colorSlug]['size_prices'][$size] = $price;
            $grouped[$colorSlug]['variant_ids'][$size] = $v['id'];
            $grouped[$colorSlug]['product_uids'][$size] = $productUid;
        }

        // Tri ordre Gelato puis tailles standard
        $sizeOrder = ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', '5XL'];
        usort($grouped, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);
        foreach ($grouped as &$g) {
            $sortedPrices = [];
            $sortedIds = [];
            $sortedUids = [];
            foreach ($sizeOrder as $s) {
                if (isset($g['size_prices'][$s])) {
                    $sortedPrices[$s] = $g['size_prices'][$s];
                    $sortedIds[$s] = $g['variant_ids'][$s];
                    $sortedUids[$s] = $g['product_uids'][$s];
                }
            }
            $g['size_prices'] = $sortedPrices;
            $g['variant_ids'] = $sortedIds;
            $g['product_uids'] = $sortedUids;
        }

        return array_values($grouped);
    }

    /**
     * Sync complet : fetch Gelato + upsert dans DB.
     */
    public function syncProductBySlug(string $slug, string $gelatoStoreProductId): array
    {
        $product = Product::where('slug', $slug)->first();
        if (! $product) {
            return ['ok' => false, 'error' => "Product slug={$slug} not found"];
        }

        $storeId = config('shop.gelato.store_id');
        $gelato = $this->fetchStoreProduct($storeId, $gelatoStoreProductId);
        if (! $gelato) {
            return ['ok' => false, 'error' => 'Gelato fetch failed'];
        }

        $basePrice = (float) ($product->price ?: 20.99);
        $variants = $this->transformToLocalVariants($gelato, $basePrice);

        // Backup ancien gelato_product_id avant écraser
        if ($product->gelato_product_id && ! $product->legacy_gelato_uid) {
            $product->legacy_gelato_uid = $product->gelato_product_id;
        }

        $product->gelato_store_product_id = $gelatoStoreProductId;
        $product->gelato_synced_at = now();
        $product->variants = $variants;
        // Si title vide en DB, prendre celui Gelato
        if (empty($product->name) || $product->name === '') {
            $product->name = strip_tags($gelato['title'] ?? '');
        }
        $product->save();

        return [
            'ok' => true,
            'product_id' => $product->id,
            'colors' => count($variants),
            'total_variants' => array_sum(array_map(fn ($v) => count($v['size_prices']), $variants)),
            'gelato_title' => $gelato['title'] ?? null,
        ];
    }
}
