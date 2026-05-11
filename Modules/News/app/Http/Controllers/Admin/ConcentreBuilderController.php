<?php

declare(strict_types=1);

namespace Modules\News\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\News\Models\ConcentreBuilderRun;
use Modules\News\Services\ConcentrePromptBuilder;

class ConcentreBuilderController extends Controller
{
    public function __construct(private readonly ConcentrePromptBuilder $builder)
    {
    }

    public function index(): View
    {
        $defaultStart = Carbon::today('America/Toronto')->previous(Carbon::MONDAY);
        $defaultEnd = $defaultStart->copy()->addDays(6);

        $history = ConcentreBuilderRun::query()
            ->where('user_id', auth()->id())
            ->latest('created_at')
            ->limit(10)
            ->get(['id', 'week_start', 'week_end', 'selected_news_ids', 'created_at']);

        return view('news::admin.concentre-builder', [
            'defaultStart' => $defaultStart->toDateString(),
            'defaultEnd' => $defaultEnd->toDateString(),
            'history' => $history,
        ]);
    }

    public function newsForWeek(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'week_start' => ['required', 'date_format:Y-m-d'],
            'week_end' => ['required', 'date_format:Y-m-d'],
        ]);

        $start = Carbon::parse($validated['week_start']);
        $end = Carbon::parse($validated['week_end']);

        $dayDiff = (int) round(abs($end->diffInDays($start)));
        if ($start->dayOfWeek !== Carbon::MONDAY || $end->dayOfWeek !== Carbon::SUNDAY || $dayDiff !== 6) {
            return response()->json([
                'error' => 'La semaine doit aller du lundi au dimanche (7 jours).',
            ], 422);
        }

        $news = $this->builder->listNewsForWeek($start, $end);
        $alreadyUsedUrls = $this->detectAlreadyUsed($start);

        return response()->json([
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'count' => $news->count(),
            'items' => $news->map(fn ($a) => [
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
                'already_used' => isset($alreadyUsedUrls['/actualites/' . $a->slug]),
            ])->values(),
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'week_start' => ['required', 'date_format:Y-m-d'],
            'week_end' => ['required', 'date_format:Y-m-d'],
            'ordered_news_ids' => ['array'],
            'ordered_news_ids.*' => ['integer'],
            'manual_urls' => ['array'],
            'manual_urls.*' => ['string'],
        ]);

        $start = Carbon::parse($validated['week_start']);
        $end = Carbon::parse($validated['week_end']);
        $ids = $validated['ordered_news_ids'] ?? [];
        $manualUrls = array_values(array_filter(
            $validated['manual_urls'] ?? [],
            fn ($u) => trim((string) $u) !== ''
        ));

        $total = count($ids) + count($manualUrls);
        if ($total < 3) {
            return response()->json(['error' => 'Sélectionne au moins 3 actualités/URLs.'], 422);
        }
        if ($total > 12) {
            return response()->json(['error' => 'Maximum 12 actualités/URLs par concentré.'], 422);
        }

        try {
            $prompt = $this->builder->build($start, $end, $ids, $manualUrls);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $run = ConcentreBuilderRun::create([
            'user_id' => auth()->id(),
            'week_start' => $start->toDateString(),
            'week_end' => $end->toDateString(),
            'selected_news_ids' => $ids,
            'manual_urls' => implode("\n", $manualUrls),
            'generated_prompt' => $prompt,
        ]);

        return response()->json([
            'success' => true,
            'run_id' => $run->id,
            'prompt' => $prompt,
            'token_estimate' => (int) ceil(mb_strlen($prompt) / 4),
        ]);
    }

    public function showRun(int $id): JsonResponse
    {
        $run = ConcentreBuilderRun::where('user_id', auth()->id())->findOrFail($id);

        return response()->json([
            'id' => $run->id,
            'week_start' => $run->week_start->toDateString(),
            'week_end' => $run->week_end->toDateString(),
            'selected_news_ids' => $run->selected_news_ids ?? [],
            'manual_urls' => $run->manual_urls,
            'generated_prompt' => $run->generated_prompt,
        ]);
    }

    /**
     * Détecte les URLs déjà utilisées dans des concentrés antérieurs (lookup sur la table runs).
     * Retourne une map [url => true] des URLs déjà incluses dans un run précédent.
     */
    private function detectAlreadyUsed(Carbon $beforeWeek): array
    {
        $previousRuns = ConcentreBuilderRun::query()
            ->where('week_start', '<', $beforeWeek->toDateString())
            ->latest('week_start')
            ->limit(20)
            ->get(['selected_news_ids', 'manual_urls']);

        $usedUrls = [];

        foreach ($previousRuns as $run) {
            $manual = preg_split('/\r\n|\r|\n/', (string) $run->manual_urls);
            foreach ($manual as $u) {
                $u = trim($u);
                if ($u !== '') {
                    $usedUrls[$u] = true;
                }
            }

            $ids = $run->selected_news_ids ?? [];
            if (! empty($ids)) {
                $slugs = \Modules\News\Models\NewsArticle::whereIn('id', $ids)->pluck('slug');
                foreach ($slugs as $slug) {
                    $usedUrls['/actualites/' . $slug] = true;
                }
            }
        }

        return $usedUrls;
    }
}
