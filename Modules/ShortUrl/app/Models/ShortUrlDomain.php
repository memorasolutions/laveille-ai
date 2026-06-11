<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\ShortUrl\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShortUrlDomain extends Model
{
    protected $table = 'short_url_domains';

    protected $fillable = [
        'domain',
        'display_label',
        'is_default',
        'is_active',
        'hidden_in_selector',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'hidden_in_selector' => 'boolean',
        ];
    }

    public function shortUrls(): HasMany
    {
        return $this->hasMany(ShortUrl::class, 'domain_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
