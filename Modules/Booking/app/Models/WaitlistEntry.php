<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Booking\Database\Factories\WaitlistEntryFactory;

class WaitlistEntry extends Model
{
    use HasFactory;

    protected $table = 'booking_waitlist_entries';

    protected $fillable = [
        'customer_id', 'service_id', 'preferred_date',
        'preferred_time_start', 'preferred_time_end',
        'status', 'notified_at', 'expires_at', 'notes',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'notified_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected static function newFactory(): WaitlistEntryFactory
    {
        return WaitlistEntryFactory::new();
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(BookingCustomer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class);
    }

    public function scopeWaiting(Builder $query): void
    {
        $query->where('status', 'waiting');
    }

    public function scopeNotified(Builder $query): void
    {
        $query->where('status', 'notified');
    }

    public function scopeForService(Builder $query, int $serviceId): void
    {
        $query->where('service_id', $serviceId);
    }

    public function scopeForDate(Builder $query, string $date): void
    {
        $query->whereDate('preferred_date', $date);
    }
}
