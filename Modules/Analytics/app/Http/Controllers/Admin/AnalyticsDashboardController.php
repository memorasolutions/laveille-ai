<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Analytics\Contracts\EditorialHistoryReader;
use Modules\Analytics\Models\AnalyticsCollectionRun;
use Modules\Analytics\Models\Ga4Daily;
use Modules\Analytics\Models\GscDaily;

/**
 * Tableau de bord de lecture seule : ce qui attire (GA4), ce qu'on trouve sur Google (GSC),
 * ce qui a été publié récemment et l'état des collectes. Aucune écriture, aucun appel réseau.
 */
class AnalyticsDashboardController
{
    private const LIMIT = 25;

    public function __construct(private readonly EditorialHistoryReader $history) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->period($request);
        $start = $from->toDateString();
        $end = $to->toDateString();

        // ACTION: agréger GA4 par URL sur la période (temps d'engagement repondéré par les sessions)
        // SELF: requête d'agrégation de lecture seule, moins de 5 lignes utiles
        // RAISON: un temps moyen quotidien ne s'additionne pas ; SUM(moy*sessions)/SUM(sessions), garde /0 en PHP
        $ga4 = Ga4Daily::query()
            ->whereBetween('date', [$start, $end])
            ->selectRaw('url, SUM(sessions) as sessions, SUM(active_users) as users, SUM(screen_page_views) as views, SUM(avg_engagement_time_seconds * sessions) as weighted_time')
            ->groupBy('url')
            ->orderByDesc('sessions')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($r) => [
                'url' => $r->url,
                'sessions' => (int) $r->sessions,
                'users' => (int) $r->users,
                'views' => (int) $r->views,
                'avg_time' => (int) $r->sessions > 0 ? (int) round((float) $r->weighted_time / (int) $r->sessions) : 0,
            ]);

        // ACTION: agréger GSC par URL sur la période (CTR recalculé, position pondérée par les impressions)
        // SELF: requête d'agrégation de lecture seule, moins de 5 lignes utiles
        // RAISON: même règle que SyncGscCommand ; le CTR moyen est clics/impressions, jamais une moyenne de CTR
        $gsc = GscDaily::query()
            ->whereBetween('date', [$start, $end])
            ->selectRaw('url, SUM(clicks) as clicks, SUM(impressions) as impressions, SUM(position * impressions) as weighted_position')
            ->groupBy('url')
            ->orderByDesc('clicks')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($r) => [
                'url' => $r->url,
                'clicks' => (int) $r->clicks,
                'impressions' => (int) $r->impressions,
                'ctr' => (int) $r->impressions > 0 ? round((int) $r->clicks / (int) $r->impressions * 100, 2) : 0.0,
                'position' => (int) $r->impressions > 0 ? round((float) $r->weighted_position / (int) $r->impressions, 1) : 0.0,
            ]);

        // ACTION: lire les dernières transitions de publication via le contrat injecté
        // SELF: appel d'un contrat existant
        // RAISON: un lecteur défaillant ne doit jamais faire planter l'écran
        try {
            $transitions = array_slice(array_reverse($this->history->transitions($from->copy()->startOfDay(), $to->copy()->endOfDay())), 0, 20);
        } catch (\Throwable $e) {
            report($e);
            $transitions = [];
        }

        $runs = AnalyticsCollectionRun::query()->orderByDesc('ran_at')->orderByDesc('id')->limit(15)->get();

        return view('analytics::admin.dashboard', [
            'enabled' => (bool) config('analytics.enabled', true),
            'from' => $start,
            'to' => $end,
            'ga4' => $ga4,
            'gsc' => $gsc,
            'transitions' => $transitions,
            'runs' => $runs,
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function period(Request $request): array
    {
        $to = $this->date($request->query('to')) ?? Carbon::today();
        $from = $this->date($request->query('from')) ?? $to->copy()->subDays(27);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from->startOfDay(), $to->startOfDay()];
    }

    private function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }
        try {
            return Carbon::createFromFormat('Y-m-d', $value)?->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }
}
