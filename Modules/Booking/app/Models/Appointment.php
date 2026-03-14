<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Booking\Database\Factories\AppointmentFactory;

class Appointment extends Model
{
    use HasFactory;

    protected static function newFactory(): AppointmentFactory
    {
        return AppointmentFactory::new();
    }

    protected $table = 'booking_appointments';

    protected $fillable = [
        'service_id', 'customer_id', 'assigned_admin_id',
        'start_at', 'end_at', 'status',
        'google_event_id', 'google_meet_link',
        'source', 'cancel_token', 'notes',
        'reminders_sent', 'cancelled_at', 'cancel_reason',
        'payment_status', 'amount_paid', 'stripe_session_id',
        'coupon_id', 'discount_amount', 'reschedule_count',
        'approval_note', 'approved_at',
        'recurrence_type', 'recurrence_end_date', 'recurrence_parent_id',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'reminders_sent' => 'array',
        'amount_paid' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'recurrence_end_date' => 'date',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class, 'service_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(BookingCustomer::class, 'customer_id');
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'assigned_admin_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>', now());
    }

    public function scopeForAdmin($query, int $adminId)
    {
        return $query->where('assigned_admin_id', $adminId);
    }

    public function recurrenceChildren(): HasMany
    {
        return $this->hasMany(Appointment::class, 'recurrence_parent_id');
    }

    public function recurrenceParent(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'recurrence_parent_id');
    }
}
