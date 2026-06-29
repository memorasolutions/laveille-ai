<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION: synchronisation et suggestion d'outils liés à une actualité (DRY).
 * Factorisé depuis AdminNewsController pour être partagé avec ArticleToolsEditor (Livewire).
 *
 * RAISON: éviter la duplication de la logique sync/suggest entre le contrôleur admin
 *         et le composant Livewire d'édition inline front-end.
 */

declare(strict_types=1);

namespace Modules\News\Actions;

use Illuminate\Support\Collection;
use Modules\Core\Services\GlossaryLinkifier;
use Modules\Directory\Models\Tool;
use Modules\News\Models\NewsArticle;

final class NewsToolSyncAction
{
    /**
     * Synchronise les outils liés à l'actualité (source=manual).
     * Valide les IDs contre la table des outils publiés avant d'écrire.
     *
     * @param  array<int, int>  $toolIds
     */
    public function sync(NewsArticle $article, array $toolIds): void
    {
        $validIds = Tool::published()
            ->whereIn('id', $toolIds)
            ->pluck('id')
            ->all();

        $article->tools()->sync(
            array_fill_keys($validIds, ['source' => 'manual'])
        );
    }

    /**
     * Suggère les outils détectés automatiquement dans le contenu de l'actualité.
     * Les outils de TOOL_NEVER_AUTO sont inclus uniquement si leur nom apparaît
     * en mot entier (insensible à la casse) dans le TITRE de l'article.
     *
     * Renvoie une Collection d'IDs d'outils (sans enregistrer - l'admin valide).
     *
     * @return Collection<int, int>
     */
    public function suggest(NewsArticle $article): Collection
    {
        GlossaryLinkifier::resetState();

        $text = implode(' ', array_filter([
            strip_tags($article->title ?? ''),
            strip_tags($article->description ?? ''),
            strip_tags($article->summary ?? ''),
        ]));

        GlossaryLinkifier::linkify($text);

        $matchedTools = collect(GlossaryLinkifier::getLastMatchedTerms())
            ->filter(fn (array $t) => ($t['type'] ?? '') === 'tool');

        $titleLower = mb_strtolower(strip_tags($article->title ?? ''));
        $neverAutoIds = collect(GlossaryLinkifier::TOOL_NEVER_AUTO)
            ->filter(fn (string $name) => (bool) preg_match('/\b' . preg_quote($name, '/') . '\b/iu', $titleLower))
            ->map(fn (string $name) => Tool::published()
                ->whereRaw("LOWER(JSON_EXTRACT(name, '$.\"fr_CA\"')) = ?", [mb_strtolower($name)])
                ->value('id'))
            ->filter()
            ->values();

        $locale = app()->getLocale();
        $slugsFromLinkifier = $matchedTools->pluck('slug');

        $detectedBySlug = Tool::published()
            ->whereIn("slug->{$locale}", $slugsFromLinkifier)
            ->pluck('id');

        return $detectedBySlug->merge($neverAutoIds)->unique()->values();
    }
}
