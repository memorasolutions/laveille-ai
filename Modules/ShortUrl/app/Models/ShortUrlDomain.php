<?php

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
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
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
