<?php

declare(strict_types=1);

namespace Modules\Newsletter\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterEvent extends Model
{
    protected $table = 'newsletter_events';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'subscriber_id',
        'event',
        'message_id',
        'link',
        'ip',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            $model->created_at = now();
        });
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(Subscriber::class, 'subscriber_id');
    }

    public function scopeOpened(Builder $query): Builder
    {
        return $query->where('event', 'opened');
    }

    public function scopeClicked(Builder $query): Builder
    {
        return $query->where('event', 'clicked');
    }

    public function scopeForEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }
}
