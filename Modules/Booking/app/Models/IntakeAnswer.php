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

class IntakeAnswer extends Model
{
    protected $table = 'booking_intake_answers';

    protected $fillable = [
        'appointment_id', 'question_id', 'answer',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class, 'appointment_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(IntakeQuestion::class, 'question_id');
    }
}
