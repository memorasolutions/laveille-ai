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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Booking\Database\Factories\BookingServiceFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BookingService extends Model
{
    use HasFactory, LogsActivity;

    protected static function newFactory(): BookingServiceFactory
    {
        return BookingServiceFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Service {$eventName}");
    }

    protected $table = 'booking_services';

    protected $fillable = [
        'name', 'slug', 'description', 'long_description',
        'duration_minutes', 'price', 'color', 'is_active', 'sort_order',
        'benefits', 'image', 'category', 'duration_display', 'price_display',
        'max_participants', 'is_group', 'require_approval',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
        'benefits' => 'array',
        'is_group' => 'boolean',
        'max_participants' => 'integer',
    ];

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'service_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }
}
