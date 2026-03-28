<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

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

    public const RESERVED_SLUGS = [
        'admin', 'api', 'login', 'dashboard', 'user', 's', 'raccourcir',
        'roadmap', 'blog', 'annuaire', 'glossaire', 'outils', 'acronymes',
        'contact', 'faq', 'register', 'logout', 'password', 'search',
        'page', 'newsletter', 'privacy-policy', 'cookie-policy',
    ];

    protected $table = 'short_urls';

    protected $fillable = [
        'user_id',
        'domain_id',
        'slug',
        'original_url',
        'title',
        'description',
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
        'og_title',
        'og_description',
        'og_image',
        'thumbnail',
        'is_anonymous',
    ];

    protected $hidden = [
        'password',
    ];

    protected function slug(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (?string $value) => $value ? strtolower(preg_replace('/-{2,}/', '-', preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', \Illuminate\Support\Str::ascii($value))))) : $value,
        );
    }

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'clicks_count' => 'integer',
            'tags' => 'array',
            'redirect_type' => 'integer',
            'max_clicks' => 'integer',
            'is_anonymous' => 'boolean',
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
        $domain = $this->relationLoaded('domain') ? $this->domain : $this->domain()->first();

        if ($this->domain_id !== null && $domain !== null) {
            return "https://{$domain->domain}/{$this->slug}";
        }

        return url('/s/'.$this->slug);
    }

    public function incrementClicks(): void
    {
        $this->increment('clicks_count');
    }
}
