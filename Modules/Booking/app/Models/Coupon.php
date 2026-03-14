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

class Coupon extends Model
{
    protected $table = 'booking_coupons';

    protected $fillable = [
        'code', 'description', 'type', 'value', 'min_order_amount',
        'max_uses', 'used_count', 'max_uses_per_customer', 'starts_at',
        'expires_at', 'is_active', 'new_customers_only', 'applicable_service_ids',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'is_active' => 'boolean',
        'new_customers_only' => 'boolean',
        'applicable_service_ids' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()));
    }

    public function scopeValid(Builder $query): void
    {
        $query->active()->where(fn ($q) => $q->whereNull('max_uses')->orWhereColumn('used_count', '<', 'max_uses'));
    }

    public function calculateDiscount(float $price): float
    {
        return $this->type === 'percent'
            ? round($price * ($this->value / 100), 2)
            : min((float) $this->value, $price);
    }

    public function isValidForService(int $serviceId): bool
    {
        return empty($this->applicable_service_ids)
            || in_array($serviceId, $this->applicable_service_ids);
    }

    public function canBeUsedByCustomer(int $customerId): bool
    {
        return CouponUsage::where('coupon_id', $this->id)
            ->where('customer_id', $customerId)
            ->count() < $this->max_uses_per_customer;
    }
}
