<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACTION: Contrôleur admin — Générateur d'objectif vidéo (plage de dates libre + sélection
 *         d'actualités + synthèse IA du texte à coller dans le champ « Objectif de la vidéo »
 *         du Prompteur BYOA).
 * RAISON: Aucune intégration serveur entre ce contrôleur et le Prompteur (100% client-side) —
 *         le texte généré est copié-collé manuellement par le superadmin.
 */

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\News\Models\NewsArticle;
use Modules\News\Services\NewsVideoGoalAiService;

class VideoGoalBuilderController extends Controller
{
    /**
     * Plage max (en jours) autorisée pour une requête d'actualités — évite une requête énorme.
     */
    private const MAX_RANGE_DAYS = 90;

    public function __construct(private readonly NewsVideoGoalAiService $aiService)
    {
    }

    public function index(): View
    {
        $defaultEnd = Carbon::today('America/Toronto');
        $defaultStart = $defaultEnd->copy()->subDays(6);

        return view('news::admin.video-goal-builder', [
            'defaultStart' => $defaultStart->toDateString(),
            'defaultEnd' => $defaultEnd->toDateString(),
        ]);
    }

    public function newsForRange(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_start'],
        ]);

        $start = Carbon::parse($validated['date_start'], 'America/Toronto');
        $end = Carbon::parse($validated['date_end'], 'America/Toronto');

        $dayDiff = (int) $start->diffInDays($end);
        if ($dayDiff > self::MAX_RANGE_DAYS) {
            return response()->json([
                'error' => 'La plage de dates ne peut pas dépasser ' . self::MAX_RANGE_DAYS . ' jours.',
            ], 422);
        }

        $news = NewsArticle::query()
            ->where('is_published', true)
            ->whereBetween('pub_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->with('source:id,name,url')
            ->orderByDesc('pub_date')
            ->get(['id', 'news_source_id', 'title', 'seo_title', 'slug', 'url', 'summary', 'pub_date', 'category_tag', 'image_url']);

        return response()->json([
            'date_start' => $start->toDateString(),
            'date_end' => $end->toDateString(),
            'count' => $news->count(),
            'items' => $news->map(fn (NewsArticle $a) => [
                'id' => $a->id,
                'title' => $a->seo_title ?: $a->title, // FR traduit en priorité, sinon EN original
                'title_original' => $a->title,
                'slug' => $a->slug,
                'site_url' => url('/actualites/' . $a->slug),
                'source_url' => $a->url,
                'summary' => mb_strimwidth((string) ($a->summary ?? ''), 0, 220, '…'),
                'pub_date' => $a->pub_date?->toIso8601String(),
                'pub_date_short' => $a->pub_date?->isoFormat('D MMM HH:mm'),
                'category_tag' => $a->category_tag,
                'image_url' => $a->image_url,
                'source_name' => $a->source?->name,
                'source_language' => $a->source?->language ?? 'unknown',
                'favicon' => $a->url ? 'https://www.google.com/s2/favicons?domain=' . parse_url($a->url, PHP_URL_HOST) . '&sz=32' : null,
            ])->values(),
        ]);
    }

    public function generateGoal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'article_ids' => ['required', 'array', 'min:1'],
            'article_ids.*' => ['integer', 'exists:news_articles,id'],
        ]);

        $articles = NewsArticle::query()
            ->whereIn('id', $validated['article_ids'])
            ->get();

        if ($articles->isEmpty()) {
            return response()->json(['error' => 'Aucune actualité valide trouvée pour ces identifiants.'], 422);
        }

        try {
            $goal = $this->aiService->generateGoal($articles);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'error' => "Erreur inattendue lors de la génération de l'objectif de vidéo.",
            ], 500);
        }

        return response()->json([
            'success' => true,
            'goal' => $goal,
            'article_count' => $articles->count(),
        ]);
    }
}
