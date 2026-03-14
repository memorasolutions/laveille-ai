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

class BookingWebhook extends Model
{
    protected $table = 'booking_webhooks';

    protected $fillable = [
        'url', 'secret', 'events', 'is_active',
        'last_triggered_at', 'last_status',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForEvent(Builder $query, string $event): void
    {
        $query->whereJsonContains('events', $event);
    }
}
