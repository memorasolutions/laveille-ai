<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Ads\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AdPlacement extends Model
{
    protected $table = 'ads_placements';

    protected $fillable = [
        'key', 'name', 'description', 'ad_code', 'ad_slot', 'ad_format', 'min_height', 'lazy',
        'is_active', 'is_external', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_external' => 'boolean',
        'lazy' => 'boolean',
        'min_height' => 'integer',
    ];

    /** Emplacement AdSense : un identifiant d'unité (data-ad-slot) est renseigné. */
    public function isAdsense(): bool
    {
        return ! empty($this->ad_slot);
    }

    /** Pub directe maison : du code publicitaire est renseigné. */
    public function hasDirect(): bool
    {
        return ! empty($this->ad_code);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByKey(Builder $query, string $key): Builder
    {
        return $query->where('key', $key);
    }
}
