<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticle extends Model
{
    protected $fillable = [
        'news_source_id', 'title', 'guid', 'url', 'description',
        'summary', 'image_url', 'author', 'pub_date', 'is_published',
    ];

    protected $casts = [
        'pub_date' => 'datetime',
        'is_published' => 'boolean',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(NewsSource::class, 'news_source_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('pub_date', 'desc');
    }
}
