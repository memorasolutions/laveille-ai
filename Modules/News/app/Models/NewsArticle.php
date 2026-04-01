<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    protected $fillable = [
        'news_source_id', 'title', 'slug', 'guid', 'url', 'description',
        'summary', 'image_url', 'author', 'pub_date', 'is_published',
        'relevance_score', 'score_justification', 'structured_summary',
        'category_tag', 'impact_level', 'feed_type', 'seo_title', 'meta_description',
    ];

    protected $casts = [
        'pub_date' => 'datetime',
        'is_published' => 'boolean',
        'structured_summary' => 'array',
        'relevance_score' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $article) {
            if (empty($article->slug)) {
                $article->slug = self::generateUniqueSlug($article->seo_title ?? $article->title ?? 'article');
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($title) ?: 'article';
        $slug = $baseSlug;
        $counter = 2;

        while (self::where('slug', $slug)->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $baseSlug.'-'.$counter++;
        }

        return $slug;
    }

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
