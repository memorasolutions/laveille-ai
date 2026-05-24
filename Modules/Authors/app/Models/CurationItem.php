<?php

declare(strict_types=1);

namespace Modules\Authors\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Blog\Models\Article;

class CurationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'author_curation_items';

    protected $fillable = [
        'author_profile_id',
        'url',
        'title',
        'excerpt',
        'thumbnail',
        'note',
        'tags',
        'source_type',
        'rss_source_id',
        'used_in_article_id',
        'saved_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'saved_at' => 'datetime',
    ];

    public function authorProfile(): BelongsTo
    {
        return $this->belongsTo(AuthorProfile::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'used_in_article_id');
    }

    public function rssSource(): BelongsTo
    {
        return $this->belongsTo(self::class, 'rss_source_id');
    }

    public function scopeNotUsed($query)
    {
        return $query->whereNull('used_in_article_id');
    }

    public function scopeByTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
}
