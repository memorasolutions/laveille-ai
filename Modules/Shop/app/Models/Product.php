<?php

namespace Modules\Shop\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

class Product extends Model
{
    use SoftDeletes;
    use \Modules\SEO\Traits\NotifiesIndexNow;

    public function getPublicUrl(): string
    {
        return route('shop.show', $this);
    }

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
     * Calcule le prix de vente : production × taux CAD × (1 + marge) / (1 - frais Stripe %).
     * Le pourcentage Stripe (2,9 %) est absorbé ici; le fixe (0,30 $) est couvert par la manutention + le garde-fou de marge.
     * La livraison est facturée séparément au checkout (pas dans le prix produit).
     * Arrondi .99 vers le HAUT : jamais sous le prix calculé.
     */
    public static function smartPrice(float $costBaseUsd, string $category = 'default'): float
    {
        $rate = \Modules\Shop\Services\ExchangeRateService::rate();
        $costCad = $costBaseUsd * $rate;

        $margins = config('shop.pricing.margins', ['default' => 0.30]);
        $margin = Arr::get($margins, $category, $margins['default'] ?? 0.30);

        $stripePct = min(max((float) config('shop.pricing.stripe_fee_pct', 0.029), 0.0), 0.5);
        $result = $costCad * (1 + $margin) / (1 - $stripePct);

        return self::roundTo99($result);
    }

    /**
     * Arrondi au .99 vers le HAUT : le plus petit x.99 supérieur ou égal au prix (54.24 → 54.99, 54.99 → 54.99,
     * 55.00 → 55.99). Ne descend jamais sous le prix calculé (l'ancien arrondi vers le bas rognait jusqu'à ~0,95 $).
     */
    public static function roundTo99(float $price): float
    {
        $n = (int) ceil(round($price - 0.99, 6));

        return round($n + 0.99, 2);
    }

    public function calculatePrice(): float
    {
        $costBase = $this->metadata['cost_base'] ?? 0;

        return self::smartPrice((float) $costBase, $this->category ?? 'default');
    }
}
