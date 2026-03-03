<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShortUrl extends Model
{
    use SoftDeletes;

    protected $table = 'short_urls';

    protected $fillable = [
        'user_id',
        'domain_id',
        'slug',
        'original_url',
        'title',
        'password',
        'expires_at',
        'max_clicks',
        'is_active',
        'redirect_type',
        'clicks_count',
        'tags',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'clicks_count' => 'integer',
            'tags' => 'array',
            'redirect_type' => 'integer',
            'max_clicks' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(ShortUrlDomain::class, 'domain_id');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(ShortUrlClick::class, 'short_url_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function scopeNotExpired(Builder $query): void
    {
        $query->where(function (Builder $q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at?->isPast() ?? false;
    }

    public function hasReachedMaxClicks(): bool
    {
        return $this->max_clicks !== null && $this->clicks_count >= $this->max_clicks;
    }

    public function isAccessible(): bool
    {
        return $this->is_active && ! $this->isExpired() && ! $this->hasReachedMaxClicks();
    }

    public function getShortUrl(): string
    {
        if ($this->domain_id !== null && $this->relationLoaded('domain') && $this->domain !== null) {
            $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https';

            return "{$scheme}://{$this->domain->domain}/{$this->slug}";
        }

        return url('/s/' . $this->slug);
    }

    public function incrementClicks(): void
    {
        $this->increment('clicks_count');
    }
}
