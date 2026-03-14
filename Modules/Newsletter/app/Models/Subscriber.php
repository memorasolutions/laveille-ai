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
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Modules\Newsletter\Database\Factories\SubscriberFactory;
use Modules\Tenancy\Traits\BelongsToTenant;

class Subscriber extends Model
{
    use BelongsToTenant, HasFactory, Notifiable;

    protected $table = 'newsletter_subscribers';

    protected $fillable = ['email', 'name', 'token', 'confirmed_at', 'unsubscribed_at', 'tenant_id'];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
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

    public function isActive(): bool
    {
        return $this->isConfirmed() && is_null($this->unsubscribed_at);
    }

    public function scopeActive($query)
    {
        return $query->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    protected static function newFactory(): SubscriberFactory
    {
        return SubscriberFactory::new();
    }
}
