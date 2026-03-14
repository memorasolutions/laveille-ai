<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SEO\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\SEO\Database\Factories\UrlRedirectFactory;
use Modules\Tenancy\Traits\BelongsToTenant;

class UrlRedirect extends Model
{
    use BelongsToTenant, HasFactory;

    protected $table = 'url_redirects';

    protected $fillable = [
        'from_url',
        'to_url',
        'status_code',
        'is_active',
        'note',
        'tenant_id',
    ];

    protected $attributes = [
        'hits' => 0,
        'is_active' => true,
        'status_code' => 301,
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'status_code' => 'integer',
        'hits' => 'integer',
        'last_hit_at' => 'datetime',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function recordHit(): void
    {
        $this->increment('hits', 1, ['last_hit_at' => now()]);
    }

    /**
     * Find an active redirect for the given URL path.
     * Tries exact match first, then wildcard patterns (* → any segment).
     */
    public static function findRedirect(string $url): ?self
    {
        // Exact match
        $redirect = static::active()->where('from_url', $url)->first();

        if ($redirect) {
            return $redirect;
        }

        // Wildcard match: load patterns containing *
        $wildcards = static::active()->where('from_url', 'like', '%*%')->get();

        foreach ($wildcards as $candidate) {
            if (Str::is($candidate->from_url, $url)) {
                return $candidate;
            }
        }

        return null;
    }

    protected static function newFactory(): UrlRedirectFactory
    {
        return UrlRedirectFactory::new();
    }
}
