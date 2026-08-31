<?php

declare(strict_types=1);

namespace Modules\Blog\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Module « vérification » étendu au blogue (2026-08-31, demande fondateur).
 *
 * Une entrée de vérification attachée à un article de fond : une affirmation examinée, son
 * verdict (ou son statut « non concluante », orthogonal), le motif propre à ce cas précis, les
 * sources probantes, et l'origine traçable de l'affirmation. Un article peut en porter plusieurs
 * - décision de structure du 2026-08-31, jamais un verdict global sur l'article entier.
 *
 * Ce modèle ne porte ni traduction ni statut de publication propre : il suit son article parent
 * (`article()`), sans logique autonome.
 *
 * DRY strict : le vocabulaire des verdicts (libellé, teinte, phrase explicative, note
 * ClaimReview) vit À UN SEUL ENDROIT, `Modules\News\Models\NewsArticle::FACT_CHECK_VERDICTS`.
 * Cette classe ne stocke que la clé du verdict et la CONSOMME, jamais ne la copie. `class_exists()`
 * garde chaque lecture : le module News reste désactivable sans casser le blogue (modularité).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class ArticleVerification extends Model
{
    use SoftDeletes;

    protected $table = 'blog_article_verifications';

    protected $fillable = [
        'article_id',
        'claim',
        'verdict',
        'motif',
        'sources',
        'source_url',
        'inconclusive_at',
        'verified_at',
        'position',
    ];

    protected $casts = [
        'sources' => 'array',
        'inconclusive_at' => 'datetime',
        'verified_at' => 'datetime',
        'position' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Le vocabulaire consommé vaut-il toujours ? Un verdict retiré du vocabulaire après coup se
     * comporte comme une absence - même garde que NewsArticle::hasFactCheck().
     */
    public function hasFactCheck(): bool
    {
        return $this->verdict !== null
            && class_exists(\Modules\News\Models\NewsArticle::class)
            && array_key_exists($this->verdict, \Modules\News\Models\NewsArticle::FACT_CHECK_VERDICTS);
    }

    /**
     * Définition complète du verdict (libellé, teinte, phrase, note), ou null - même contrat que
     * NewsArticle::factCheckVerdict(), pour que le composant Blade partagé
     * `<x-news::fact-check-badge>` rende cette entrée à l'identique d'une fiche d'actualité.
     *
     * @return array{label:string, tone:string, summary:string, rating:int}|null
     */
    public function factCheckVerdict(): ?array
    {
        return $this->hasFactCheck() ? \Modules\News\Models\NewsArticle::FACT_CHECK_VERDICTS[$this->verdict] : null;
    }

    /**
     * Statut orthogonal « vérification non concluante » (2026-08-31) : cette entrée a été
     * examinée sans pouvoir trancher vers un verdict. Un verdict réel prime toujours - même
     * contrat que NewsArticle::hasFactCheckInconclusive().
     */
    public function hasFactCheckInconclusive(): bool
    {
        return ! $this->hasFactCheck() && $this->inconclusive_at !== null;
    }

    /**
     * Alias de lecture, jamais une deuxième colonne (DRY) : le composant Blade partagé ne connaît
     * que le nom `fact_check_claim` (celui de NewsArticle) - cet accesseur le fait pointer vers
     * la colonne réelle `claim` de cette table, sans la dupliquer.
     */
    public function getFactCheckClaimAttribute(): ?string
    {
        return $this->claim;
    }

    /**
     * Même principe que getFactCheckClaimAttribute() ci-dessus, pour `source_url` -> l'origine
     * traçable de l'affirmation, lue par le composant Blade partagé sous le nom
     * `fact_check_source`.
     */
    public function getFactCheckSourceAttribute(): ?string
    {
        return $this->source_url;
    }
}
