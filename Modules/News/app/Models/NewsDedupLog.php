<?php

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsDedupLog extends Model
{
    protected $table = 'news_dedup_log';

    public $timestamps = false;

    protected $fillable = [
        'new_article_id',
        'matched_article_id',
        'score',
        'reason',
        'signals',
        'action',
    ];

    protected $casts = [
        'signals' => 'array',
        'score' => 'float',
        'created_at' => 'datetime',
    ];

    public function newArticle(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'new_article_id');
    }

    public function matchedArticle(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'matched_article_id');
    }
}
