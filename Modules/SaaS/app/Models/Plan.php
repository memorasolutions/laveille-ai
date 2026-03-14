<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use Spatie\ResponseCache\Facades\ResponseCache;

class Plan extends Model
{
    use HasFactory, Searchable;

    public function toSearchableArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
        ];
    }

    public function shouldBeSearchable(): bool
    {
        return $this->is_active;
    }

    protected static function booted(): void
    {
        static::saved(fn () => ResponseCache::clear());
        static::deleted(fn () => ResponseCache::clear());
    }

    protected $fillable = [
        'name',
        'slug',
        'description',
        'stripe_product_id',
        'stripe_price_id',
        'price',
        'currency',
        'interval',
        'trial_days',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
            'trial_days' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeMonthly(Builder $query): Builder
    {
        return $query->where('interval', 'monthly');
    }

    public function scopeYearly(Builder $query): Builder
    {
        return $query->where('interval', 'yearly');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price');
    }

    protected static function newFactory(): \Modules\SaaS\Database\Factories\PlanFactory
    {
        return \Modules\SaaS\Database\Factories\PlanFactory::new();
    }
}
