<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class Product extends Model
{
    use SoftDeletes;

    protected $table = 'shop_products';

    protected $fillable = [
        'gelato_product_id', 'name', 'slug', 'description', 'short_description',
        'price', 'compare_price', 'currency', 'images', 'variants',
        'category', 'status', 'sort_order', 'metadata',
    ];

    protected $casts = [
        'images' => 'array',
        'variants' => 'array',
        'metadata' => 'array',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Calcule le prix de vente : production × taux CAD × (1 + marge).
     * La livraison est facturée séparément au checkout (pas dans le prix produit).
     * Arrondi au .99 : >= .05 → même entier, <= .04 → entier précédent.
     */
    public static function smartPrice(float $costBaseUsd, string $category = 'default'): float
    {
        $rate = \Modules\Shop\Services\ExchangeRateService::rate();
        $costCad = $costBaseUsd * $rate;

        $margins = config('shop.pricing.margins', ['default' => 0.30]);
        $margin = Arr::get($margins, $category, $margins['default'] ?? 0.30);

        $result = $costCad * (1 + $margin);

        return self::roundTo99($result);
    }

    /**
     * Arrondi intelligent au .99 :
     * >= .05 → .99 du même entier (54.24 → 54.99)
     * <= .04 → .99 de l'entier précédent (55.04 → 54.99, 55.00 → 54.99)
     */
    public static function roundTo99(float $price): float
    {
        $cents = round(($price - floor($price)) * 100);

        if ($cents >= 5) {
            return floor($price) + 0.99;
        }

        return floor($price) - 1 + 0.99;
    }

    public function calculatePrice(): float
    {
        $costBase = $this->metadata['cost_base'] ?? 0;

        return self::smartPrice((float) $costBase, $this->category ?? 'default');
    }
}
