<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    protected $table = 'ecommerce_shipping_zones';

    protected $fillable = ['name', 'regions', 'is_active', 'sort_order'];

    protected $casts = [
        'regions' => 'array',
        'is_active' => 'boolean',
    ];

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForProvince(Builder $query, string $province): Builder
    {
        return $query->whereJsonContains('regions', strtoupper($province));
    }
}
