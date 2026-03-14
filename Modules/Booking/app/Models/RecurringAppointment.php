<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringAppointment extends Model
{
    protected $table = 'booking_recurring_appointments';

    protected $fillable = [
        'customer_id', 'service_id', 'frequency', 'day_of_week',
        'preferred_time', 'starts_at', 'ends_at', 'is_active',
        'last_generated_at', 'notes',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_active' => 'boolean',
        'last_generated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(BookingCustomer::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeForCustomer(Builder $query, int $customerId): void
    {
        $query->where('customer_id', $customerId);
    }

    public function nextOccurrence(): ?Carbon
    {
        if (! $this->is_active) {
            return null;
        }

        $startDate = $this->last_generated_at
            ? $this->last_generated_at->copy()->addDay()
            : $this->starts_at->copy();

        $today = Carbon::today();

        if ($startDate->lt($today)) {
            $startDate = $today;
        }

        if ($this->ends_at && $startDate->gt($this->ends_at)) {
            return null;
        }

        $nextDate = $startDate->copy();

        if ($this->day_of_week !== null) {
            while ($nextDate->dayOfWeek !== $this->day_of_week) {
                $nextDate->addDay();
            }
        }

        if ($this->frequency === 'biweekly') {
            $weeksDiff = $this->starts_at->diffInWeeks($nextDate);
            if ($weeksDiff % 2 !== 0) {
                $nextDate->addWeek();
            }
        }

        if ($this->ends_at && $nextDate->gt($this->ends_at)) {
            return null;
        }

        return $nextDate;
    }
}
