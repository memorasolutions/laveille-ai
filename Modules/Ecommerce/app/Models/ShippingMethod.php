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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    protected $table = 'ecommerce_shipping_methods';

    protected $fillable = [
        'shipping_zone_id', 'name', 'type', 'cost',
        'min_order', 'max_order', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'cost' => 'float',
        'min_order' => 'float',
        'max_order' => 'float',
        'is_active' => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
