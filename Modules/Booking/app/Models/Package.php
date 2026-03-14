<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $table = 'booking_packages';

    protected $fillable = [
        'name', 'description', 'session_count', 'price', 'regular_price',
        'validity_days', 'applicable_service_ids', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'regular_price' => 'decimal:2',
        'is_active' => 'boolean',
        'applicable_service_ids' => 'array',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    public function savingsPercent(): ?int
    {
        if (! $this->regular_price || $this->regular_price <= 0) {
            return null;
        }

        return (int) round((1 - $this->price / $this->regular_price) * 100);
    }
}
