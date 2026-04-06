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

    public static function smartPrice(float $costBaseUsd, string $category = 'default'): float
    {
        $rate = config('shop.pricing.usd_cad_rate', 1.40);
        $costCad = $costBaseUsd * $rate;

        $margins = config('shop.pricing.margins', [
            't-shirts' => 0.45,
            'mugs' => 0.50,
            'tote-bags' => 0.60,
            'posters' => 0.50,
            'hoodies' => 0.45,
            'default' => 0.45,
        ]);

        $margin = Arr::get($margins, $category, $margins['default']);
        $result = $costCad * (1 + $margin);

        return ceil($result) - 0.01;
    }

    public function calculatePrice(): float
    {
        $costBase = $this->metadata['cost_base'] ?? 0;

        return self::smartPrice((float) $costBase, $this->category ?? 'default');
    }
}
