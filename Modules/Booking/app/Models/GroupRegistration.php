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
use Modules\Booking\Database\Factories\GroupRegistrationFactory;

class GroupRegistration extends Model
{
    use HasFactory;

    protected static function newFactory(): GroupRegistrationFactory
    {
        return GroupRegistrationFactory::new();
    }

    protected $table = 'booking_group_registrations';

    protected $fillable = [
        'appointment_id', 'customer_id', 'status', 'registered_at',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(BookingCustomer::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', '!=', 'cancelled');
    }

    public function scopeForAppointment(Builder $query, int $appointmentId): void
    {
        $query->where('appointment_id', $appointmentId);
    }
}
