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

class AdminCalendar extends Model
{
    protected $table = 'booking_admin_calendars';

    protected $fillable = [
        'user_id', 'google_calendar_id', 'calendar_name', 'email',
        'is_destination', 'is_blocking',
        'access_token', 'refresh_token', 'token_expires_at',
    ];

    protected $casts = [
        'is_destination' => 'boolean',
        'is_blocking' => 'boolean',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = ['access_token', 'refresh_token'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
