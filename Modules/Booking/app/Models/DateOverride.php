<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DateOverride extends Model
{
    protected $table = 'booking_date_overrides';

    protected $fillable = [
        'date', 'override_type', 'all_day',
        'start_time', 'end_time', 'reason', 'created_by_id',
        'user_id', 'repeat_yearly', 'label',
    ];

    protected $casts = [
        'date' => 'date',
        'all_day' => 'boolean',
        'repeat_yearly' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by_id');
    }

    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeBlocked($query)
    {
        return $query->where('override_type', 'blocked');
    }

    public function scopeAvailable($query)
    {
        return $query->where('override_type', 'available');
    }
}
