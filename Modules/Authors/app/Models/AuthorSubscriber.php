<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuthorSubscriber extends Model
{
    protected $table = 'author_subscribers';

    protected $fillable = [
        'author_profile_id',
        'email',
        'confirmation_token',
        'confirmed_at',
        'unsubscribed_at',
        'source',
        'ip_address',
        'user_agent',
        'locale',
        'last_digest_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'last_digest_at' => 'datetime',
    ];

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }

    public function scopeConfirmed(Builder $q): Builder
    {
        return $q->whereNotNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->whereNull('unsubscribed_at');
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->whereNull('confirmed_at')->whereNull('unsubscribed_at');
    }

    public static function generateConfirmationToken(): string
    {
        return Str::random(64);
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null && $this->unsubscribed_at === null;
    }

    public function isUnsubscribed(): bool
    {
        return $this->unsubscribed_at !== null;
    }

    public function markConfirmed(): void
    {
        $this->confirmed_at = now();
        $this->save();
    }

    public function markUnsubscribed(): void
    {
        $this->unsubscribed_at = now();
        $this->save();
    }
}
