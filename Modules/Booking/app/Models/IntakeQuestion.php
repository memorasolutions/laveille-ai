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
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntakeQuestion extends Model
{
    protected $table = 'booking_intake_questions';

    protected $fillable = [
        'service_id', 'label', 'type', 'options',
        'is_required', 'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class, 'service_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(IntakeAnswer::class, 'question_id');
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order', 'asc');
    }

    public function scopeForService(Builder $query, int $serviceId): void
    {
        $query->where('service_id', $serviceId);
    }
}
