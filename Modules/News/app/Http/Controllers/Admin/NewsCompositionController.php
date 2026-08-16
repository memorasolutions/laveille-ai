<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\News\Models\NewsArticle;

/**
 * Écran de composition manuelle d'une actualité (Phase A - design doc "Actus - composition
 * manuelle assistée", 2026-08-15). Structure seulement : sélection d'UNE actualité déjà
 * collectée (réutilisation du composant partagé news-article-picker), édition du titre et du
 * résumé publiés, et persistance du texte source collé (jamais publié - voir section 5.2 du
 * design doc et la migration 2026_08_16_150800_add_internal_source_text_to_news_articles).
 *
 * Explicitement HORS PÉRIMÈTRE ici (phases suivantes) : construction de prompt d'image, dépôt
 * d'image, validation de fichier, fiche de preuve éditoriale (Phase B), politique de rétention
 * avec extraits/empreinte (Phase C). La bascule de publication existe déjà ailleurs
 * (Modules\News\Http\Controllers\AdminNewsController::toggleArticle).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */
class NewsCompositionController extends Controller
{
    public function index(): View
    {
        return view('news::admin.composition-builder', [
            'candidatesEndpoint' => route('admin.news.composition.candidates'),
            'showEndpointTemplate' => route('admin.news.composition.show', ['article' => '__SLUG__']),
            'updateEndpointTemplate' => route('admin.news.composition.update', ['article' => '__SLUG__']),
            'deleteSourceTextEndpointTemplate' => route('admin.news.composition.destroy-source-text', ['article' => '__SLUG__']),
            'articlesIndexUrl' => route('admin.news.articles.index'),
        ]);
    }

    /**
     * Liste des actualités déjà collectées, pour la colonne "disponibles" du composant partagé
     * news-article-picker.js (voir public/assets/admin/news-article-picker.js). Même forme JSON
     * que ConcentreBuilderController::newsForWeek(), sans le regroupement par acteur (inutile ici
     * : une seule actualité est composée à la fois, pas de tri par cluster à faire).
     */
    public function candidates(): JsonResponse
    {
        $articles = NewsArticle::query()
            ->with('source')
            ->orderByDesc('pub_date')
            ->limit(200)
            ->get();

        return response()->json([
            'items' => $articles->map(fn (NewsArticle $a) => [
                'id' => $a->id,
                'title' => $a->seo_title ?: $a->title,
                'title_original' => $a->title,
                'slug' => $a->slug,
                'site_url' => url('/actualites/'.$a->slug),
                'source_url' => $a->url,
                'summary' => mb_strimwidth((string) ($a->summary ?? ''), 0, 220, '…'),
                'pub_date' => $a->pub_date?->toIso8601String(),
                'pub_date_short' => $a->pub_date?->isoFormat('D MMM HH:mm'),
                'category_tag' => $a->category_tag,
                'image_url' => $a->image_url,
                'source_name' => $a->source?->name,
                'source_language' => $a->source?->language ?? 'unknown',
                'actor_cluster' => null,
                'cluster_color' => null,
                'favicon' => $a->url ? 'https://www.google.com/s2/favicons?domain='.parse_url($a->url, PHP_URL_HOST).'&sz=32' : null,
                // Réutilise le badge "🔁 déjà utilisée" du partial partagé pour signaler qu'une
                // composition a déjà été commencée sur cette fiche (texte source déjà collé).
                'already_used' => filled($a->internal_source_text),
            ])->values(),
        ]);
    }

    /**
     * Détail complet d'UNE actualité pour préremplir le formulaire de composition. Volontairement
     * distinct de candidates() (qui tronque le résumé et n'inclut jamais le texte source) : le
     * texte intégral n'est transmis au navigateur admin qu'au moment où l'actualité est
     * effectivement sélectionnée, jamais dans la liste en vrac - minimisation cohérente avec le
     * design doc section 4.
     */
    public function show(NewsArticle $article): JsonResponse
    {
        return response()->json([
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'seo_title' => $article->seo_title,
            'summary' => $article->summary,
            'internal_source_text' => $article->internal_source_text,
            'is_published' => (bool) $article->is_published,
            'site_url' => url('/actualites/'.$article->slug),
            'updated_at' => $article->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Sauvegarde le titre publié (seo_title), le résumé publié (summary) et/ou le texte source
     * interne (internal_source_text) d'une actualité déjà collectée. N'écrit jamais dans
     * 'description' (colonne purgée, ne plus jamais réutiliser - voir la migration).
     */
    public function update(Request $request, NewsArticle $article): JsonResponse
    {
        // 'sometimes' avant 'nullable' : un champ ABSENT du corps JSON reste absent de
        // $validated (donc jamais écrasé à null) ; un champ présent mais vide/null est bien
        // accepté et écrit. Sans 'sometimes', un appel qui n'enverrait que internal_source_text
        // aurait silencieusement vidé seo_title et summary.
        $validated = $request->validate([
            'seo_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'summary' => ['sometimes', 'nullable', 'string', 'max:2000'],
            // Borne large mais finie : évite un payload démesuré côté admin sans empêcher de
            // coller l'intégralité d'un article de fond.
            'internal_source_text' => ['sometimes', 'nullable', 'string', 'max:200000'],
        ]);

        $article->update($validated);

        return response()->json([
            'success' => true,
            'updated_at' => $article->fresh()->updated_at?->toIso8601String(),
        ]);
    }

    /**
     * Supprime UNIQUEMENT le texte source interne, à tout moment, sans toucher au reste de la
     * fiche (titre, résumé, statut de publication) - décision 5.2 du design doc : "supprimable à
     * tout moment". Action dédiée plutôt que surchargée dans update() pour rester testable en
     * isolation et pour que le bouton "Supprimer le texte source" ait un contrat sans ambiguïté.
     */
    public function destroySourceText(NewsArticle $article): JsonResponse
    {
        $article->update(['internal_source_text' => null]);

        return response()->json(['success' => true]);
    }
}
