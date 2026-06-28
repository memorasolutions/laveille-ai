<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Modules\Newsletter\Database\Factories\SubscriberFactory;
use Modules\Tenancy\Traits\BelongsToTenant;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Subscriber extends Model
{
    use BelongsToTenant, HasFactory, LogsActivity, Notifiable, SoftDeletes;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn (string $eventName): string => "Abonné {$eventName}");
    }

    protected $table = 'newsletter_subscribers';

    protected $fillable = [
        'email',
        'name',
        'token',
        'confirmed_at',
        'unsubscribed_at',
        'bounce_count',
        'bounce_reason',
        'tenant_id',
        'unsubscribe_reason',
        'unsubscribe_feedback',
        'paused_until',
        'frequency_preference',
        'reminder_count',
        'last_reminded_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'paused_until' => 'datetime',
        'last_reminded_at' => 'datetime',
        'reminder_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->token)) {
                $model->token = Str::random(64);
            }
        });
    }

    public function isConfirmed(): bool
    {
        return ! is_null($this->confirmed_at);
    }

    /**
     * Détermine si un abonné est éligible au renvoi manuel de la confirmation.
     *
     * Garde-fous :
     *  - Doit être en attente (non confirmé, non désabonné)
     *  - Inscrit il y a moins de 7 jours (avant purge planifiée)
     *  - Maximum 2 rappels (cron J+1 + 1 renvoi manuel)
     *  - Délai minimal de 24 h entre deux envois
     */
    public function canResendConfirmation(): bool
    {
        return is_null($this->confirmed_at)
            && is_null($this->unsubscribed_at)
            && $this->created_at >= now()->subDays(7)
            && ((int) $this->reminder_count) < 2
            && (is_null($this->last_reminded_at) || $this->last_reminded_at <= now()->subDay());
    }

    /**
     * Incrémente le compteur de rappels et horodate le dernier envoi.
     */
    public function markReminded(): void
    {
        $this->forceFill([
            'reminder_count' => ((int) $this->reminder_count) + 1,
            'last_reminded_at' => now(),
        ])->save();
    }

    public function isActive(): bool
    {
        return $this->isConfirmed() && is_null($this->unsubscribed_at);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function scopeNotPaused($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('paused_until')->orWhere('paused_until', '<=', now());
        });
    }

    public function isPaused(): bool
    {
        return $this->paused_until !== null && $this->paused_until->isFuture();
    }

    protected static function newFactory(): SubscriberFactory
    {
        return SubscriberFactory::new();
    }
}
