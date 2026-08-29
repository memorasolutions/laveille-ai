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
        // Compagnie d'IA + drapeau officiel (2026-08-29) - voir migration
        // add_company_fields_to_news_sources pour la justification du choix de colonnes.
        'is_official', 'company',
    ];

    protected $casts = [
        'active' => 'boolean',
        'last_fetched_at' => 'datetime',
        'is_official' => 'boolean',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(NewsArticle::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Sources publiées PAR la compagnie elle-même (blog officiel, page de recherche) - jamais un
     * média tiers qui en parle. Symétrique de scopeActive() ci-dessus.
     */
    public function scopeOfficial(Builder $query): Builder
    {
        return $query->where('is_official', true);
    }
}
