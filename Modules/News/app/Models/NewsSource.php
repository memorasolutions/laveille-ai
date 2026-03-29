<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsSource extends Model
{
    protected $fillable = [
        'name', 'url', 'category', 'language', 'active', 'last_fetched_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_fetched_at' => 'datetime',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
