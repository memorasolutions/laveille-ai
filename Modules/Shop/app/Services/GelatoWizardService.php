<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Modules\Shop\Models\Product;

class GelatoWizardService
{
    private const BASE_URL = 'https://product.gelatoapis.com/v3';

    private static function headers(): array
    {
        return ['X-API-KEY' => config('shop.gelato.api_key')];
    }

    private static function get(string $url, array $query = []): array
    {
        try {
            return Http::withHeaders(self::headers())->get($url, $query)->json() ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public static function getCatalogs(): array
    {
        return Cache::remember('gelato:catalogs', 86400, function () {
            return self::get(self::BASE_URL . '/catalogs');
        });
    }

    public static function searchProducts(string $catalogUid, array $filters = [], int $limit = 20): array
    {
        $cacheKey = 'gelato:products:' . $catalogUid . ':' . md5(json_encode($filters));

        return Cache::remember($cacheKey, 21600, function () use ($catalogUid, $filters, $limit) {
            return self::get(self::BASE_URL . '/catalogs/' . $catalogUid . '/products', [
                'attributeFilters' => !empty($filters) ? json_encode($filters) : null,
                'limit' => $limit,
                'offset' => 0,
            ]);
        });
    }

    public static function getProductPrices(string $productUid, string $country = 'CA'): ?float
    {
        $cacheKey = "gelato:prices:{$productUid}:{$country}";

        return Cache::remember($cacheKey, 21600, function () use ($productUid, $country) {
            $response = self::get(self::BASE_URL . "/products/{$productUid}/prices", [
                'country' => $country,
            ]);

            return isset($response[0]['price']) ? (float) $response[0]['price'] : null;
        });
    }

    public static function getAvailableAttributes(string $catalogUid): array
    {
        $cacheKey = "gelato:attributes:{$catalogUid}";

        return Cache::remember($cacheKey, 21600, function () use ($catalogUid) {
            $response = self::searchProducts($catalogUid, [], 1);

            return $response['hits']['attributeHits'] ?? [];
        });
    }

    public static function resolveProductUid(string $baseUid, string $size, string $color): string
    {
        $uid = preg_replace('/_gsi_[^_]+_/', "_gsi_{$size}_", $baseUid);
        $uid = preg_replace('/_gco_[^_]+_/', "_gco_{$color}_", $uid);

        return $uid;
    }

    public static function generateVariants(string $baseUid, array $sizes, array $colors, string $category): array
    {
        $variants = [];

        foreach ($sizes as $size) {
            foreach ($colors as $color) {
                $gelatoUid = self::resolveProductUid($baseUid, strtolower($size), strtolower($color));
                $cost = self::getProductPrices($gelatoUid);

                $variants[] = [
                    'label' => strtoupper($size) . ' - ' . ucfirst($color),
                    'size' => strtoupper($size),
                    'color' => $color,
                    'gelato_uid' => $gelatoUid,
                    'cost' => $cost,
                    'price' => $cost ? Product::smartPrice($cost, $category) : null,
                ];
            }
        }

        return $variants;
    }
}
