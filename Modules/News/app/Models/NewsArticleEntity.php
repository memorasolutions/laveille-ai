<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: entité nommée rattachée à une fiche d'actualité (index des connexes par entités).
 * MCP: hermes→deepseek-v4-flash (validé par le superviseur)
 * RAISON: arbitrage panel 2026-08-17 - une ligne par entité, slug normalisé indexé.
 */

declare(strict_types=1);

namespace Modules\News\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticleEntity extends Model
{
    protected $fillable = [
        'news_article_id',
        'entity_slug',
        'entity_label',
    ];

    public function newsArticle(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class);
    }
}
