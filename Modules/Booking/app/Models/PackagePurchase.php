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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackagePurchase extends Model
{
    protected $table = 'booking_package_purchases';

    protected $fillable = [
        'customer_id', 'package_id', 'sessions_remaining', 'sessions_used',
        'purchased_at', 'expires_at', 'payment_status', 'stripe_session_id', 'amount_paid',
    ];

    protected $casts = [
        'purchased_at' => 'datetime',
        'expires_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('sessions_remaining', '>', 0)
            ->where('expires_at', '>', now())
            ->where('payment_status', 'paid');
    }

    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('payment_status', 'paid');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(BookingCustomer::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
