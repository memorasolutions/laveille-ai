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
use Illuminate\Support\Facades\DB;
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
     *
     * 2026-07-04 : le texte scanné inclut désormais aussi le résumé structuré IA
     * (hook + points clés + pourquoi important), pas seulement title/description/summary.
     * Pour beaucoup d'actualités, description/summary sont vides et TOUT le contenu réel
     * vit dans structured_summary (cf. show.blade.php : le résumé brut ne s'affiche QUE en
     * l'absence de résumé structuré) - sans cet ajout, la détection échouait silencieusement
     * à 0 dès que le nom de l'outil n'apparaissait que dans le résumé IA.
     *
     * Les outils de TOOL_NEVER_AUTO (mots aussi courants en français : "Claude", "Avec",
     * "Tome", "Make"...) sont exclus des $terms de GlossaryLinkifier et donc jamais détectés
     * par linkify() ci-dessous, quel que soit $text. On les détecte séparément sur le texte
     * COMPLET de l'article (titre + description + résumé + résumé structuré), mais en CASSE
     * STRICTE (la forme capitalisée "Claude", pas "claude") : un mot français courant comme
     * "avec" apparaît presque toujours en minuscule en milieu de phrase, jamais capitalisé
     * hors début de phrase - la casse stricte limite donc fortement le risque de faux positif
     * (confirmé : un outil "Avec" existe bel et bien, publié, dans l'annuaire).
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
            strip_tags($article->flattenStructuredSummary()),
        ]));

        GlossaryLinkifier::linkify($text);

        $matchedTools = collect(GlossaryLinkifier::getLastMatchedTerms())
            ->filter(fn (array $t) => ($t['type'] ?? '') === 'tool');

        // 2026-07-04 : JSON_UNQUOTE indispensable sous MySQL (règle projet permanente, cf. #227/#306) -
        // sans lui, JSON_EXTRACT renvoie la valeur JSON-quotée (ex. "claude" avec guillemets littéraux),
        // qui ne matche donc JAMAIS mb_strtolower($name) ('claude' sans guillemets). SQLite (tests)
        // déquote déjà les scalaires nativement dans JSON_EXTRACT et n'a PAS de fonction JSON_UNQUOTE
        // (cf. migration 2026_05_05_180100_set_transformer_case_sensitive.php) - d'où le conditionnel.
        $nameJsonExpr = DB::getDriverName() === 'mysql'
            ? "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"fr_CA\"')))"
            : "LOWER(JSON_EXTRACT(name, '$.\"fr_CA\"'))";

        $neverAutoIds = collect(GlossaryLinkifier::TOOL_NEVER_AUTO)
            ->filter(fn (string $name) => (bool) preg_match('/\b' . preg_quote(ucfirst($name), '/') . '\b/u', $text))
            ->map(fn (string $name) => Tool::published()
                ->whereRaw("{$nameJsonExpr} = ?", [mb_strtolower($name)])
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
