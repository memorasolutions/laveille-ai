<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Services;

use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\Dictionary\Models\Term;
use Modules\Directory\Models\Tool;
use Modules\Newsletter\Models\Subscriber;
use Modules\Webhooks\Models\WebhookCall;
use Spatie\Activitylog\Models\Activity;

class AnalyticsService
{
    public function getOverview(int $days = 30): array
    {
        $since = now()->subDays($days);
        $webhooksAvailable = \Schema::hasTable('webhook_calls');
        $totalWebhookCalls = $webhooksAvailable ? WebhookCall::count() : 0;

        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'new_users' => User::where('created_at', '>=', $since)->count(),
            'total_articles' => Article::count(),
            'published_articles' => Article::where('status', 'published')->count(),
            'total_comments' => Comment::count(),
            'pending_comments' => Comment::where('status', 'pending')->count(),
            'total_subscribers' => Subscriber::count(),
            'total_webhook_calls' => $totalWebhookCalls,
            'webhook_success_rate' => $webhooksAvailable && $totalWebhookCalls > 0
                ? round((WebhookCall::where('status', 'success')->count() / $totalWebhookCalls) * 100, 1)
                : 0,
            'total_activities' => Activity::count(),
        ];
    }

    public function getWebhookStats(int $days = 30): array
    {
        if (! \Schema::hasTable('webhook_calls')) {
            return ['total' => 0, 'successful' => 0, 'failed' => 0, 'pending' => 0, 'success_rate' => 0, 'by_event' => []];
        }

        $since = now()->subDays($days);
        $total = WebhookCall::where('created_at', '>=', $since)->count();
        $successful = WebhookCall::where('status', 'success')->where('created_at', '>=', $since)->count();
        $failed = WebhookCall::where('status', 'failed')->where('created_at', '>=', $since)->count();
        $pending = WebhookCall::where('status', 'pending')->where('created_at', '>=', $since)->count();

        $byEvent = WebhookCall::where('created_at', '>=', $since)
            ->selectRaw('event, count(*) as cnt')
            ->groupBy('event')
            ->pluck('cnt', 'event')
            ->toArray();

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'pending' => $pending,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 1) : 0,
            'by_event' => $byEvent,
        ];
    }

    public function getContentStats(int $days = 30): array
    {
        $since = now()->subDays($days);

        $byCategory = Category::where('is_active', true)
            ->withCount(['articles' => fn ($q) => $q->where('articles.created_at', '>=', $since)])
            ->get()
            ->pluck('articles_count', 'name')
            ->toArray();

        return [
            'articles_created' => Article::where('created_at', '>=', $since)->count(),
            'articles_published' => Article::where('status', 'published')->where('created_at', '>=', $since)->count(),
            'comments_created' => Comment::where('created_at', '>=', $since)->count(),
            'comments_approved' => Comment::where('status', 'approved')->where('created_at', '>=', $since)->count(),
            'by_category' => $byCategory,
        ];
    }

    public function getActivityTimeline(int $days = 30): array
    {
        $start = now()->subDays($days)->startOfDay();
        $end = now()->endOfDay();

        $counts = Activity::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, count(*) as cnt')
            ->groupBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        $timeline = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->format('Y-m-d');
            $timeline[] = [
                'date' => $key,
                'count' => $counts[$key] ?? 0,
            ];
        }

        return $timeline;
    }

    public function getUserGrowth(int $months = 12): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();

        $counts = User::where('created_at', '>=', $start)
            ->pluck('created_at')
            ->groupBy(fn ($d) => $d->format('Y-n'))
            ->map->count();

        return collect(range($months - 1, 0))->map(function ($i) use ($counts) {
            $date = now()->subMonths($i);

            return [
                'label' => $date->locale('fr')->isoFormat('MMM'),
                'count' => $counts->get($date->format('Y-n'), 0),
            ];
        })->values()->all();
    }

    public function getNewsletterGrowth(int $days = 30): array
    {
        $start = now()->subDays($days)->startOfDay();
        $end = now()->endOfDay();

        $confirmed = Subscriber::where('created_at', '>=', $start)
            ->whereNull('unsubscribed_at')
            ->selectRaw('DATE(created_at) as date, count(*) as cnt')
            ->groupBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        $unsubscribed = Subscriber::whereNotNull('unsubscribed_at')
            ->where('unsubscribed_at', '>=', $start)
            ->selectRaw('DATE(unsubscribed_at) as date, count(*) as cnt')
            ->groupBy('date')
            ->pluck('cnt', 'date')
            ->toArray();

        $timeline = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $key = $date->format('Y-m-d');
            $timeline[] = [
                'date' => $key,
                'subscribed' => $confirmed[$key] ?? 0,
                'unsubscribed' => $unsubscribed[$key] ?? 0,
            ];
        }

        return $timeline;
    }

    public function getTopArticles(int $days = 30, int $limit = 5): array
    {
        $since = now()->subDays($days);

        return Article::where('status', 'published')
            ->where('published_at', '>=', $since)
            ->withCount(['comments as approved_comments' => fn ($q) => $q->where('status', 'approved')])
            ->orderByDesc('approved_comments')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'published_at'])
            ->map(fn ($a) => [
                'title' => mb_strimwidth((string) $a->title, 0, 60, '…'),
                'slug' => $a->slug,
                'engagement' => (int) $a->approved_comments,
                'published_at' => optional($a->published_at)->format('Y-m-d'),
            ])
            ->values()
            ->all();
    }

    public function getActivityTimelineByType(int $days = 30): array
    {
        $start = now()->subDays($days)->startOfDay();
        $end = now()->endOfDay();

        $rows = Activity::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as date, subject_type, count(*) as cnt')
            ->groupBy('date', 'subject_type')
            ->get();

        $bucketMap = [
            'Modules\\Blog\\Models\\Article' => 'Articles blog',
            'Modules\\Blog\\Models\\Comment' => 'Articles blog',
            'Modules\\News\\Models\\NewsArticle' => 'News auto',
            'Modules\\Directory\\Models\\Tool' => 'Annuaire & glossaire',
            'Modules\\Dictionary\\Models\\Term' => 'Annuaire & glossaire',
            'Modules\\Acronyms\\Models\\Acronym' => 'Annuaire & glossaire',
        ];

        $defaultBucket = 'Système & users';
        $buckets = ['Articles blog', 'News auto', 'Annuaire & glossaire', $defaultBucket];

        $byDate = [];
        foreach ($rows as $row) {
            $key = (string) $row->date;
            $bucket = $bucketMap[(string) $row->subject_type] ?? $defaultBucket;
            $byDate[$key][$bucket] = ($byDate[$key][$bucket] ?? 0) + (int) $row->cnt;
        }

        $dates = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $dates[] = $date->format('Y-m-d');
        }

        $series = [];
        foreach ($buckets as $bucket) {
            $series[$bucket] = array_map(fn ($d) => $byDate[$d][$bucket] ?? 0, $dates);
        }

        return [
            'dates' => $dates,
            'series' => $series,
        ];
    }

    public function getTopDirectoryTools(int $limit = 10): array
    {
        return Tool::where('status', 'published')
            ->where('clicks_count', '>', 0)
            ->orderByDesc('clicks_count')
            ->limit($limit)
            ->get(['id', 'slug', 'name', 'clicks_count', 'pricing'])
            ->map(fn ($t) => [
                'name' => mb_strimwidth((string) $t->name, 0, 50, '…'),
                'slug' => $t->slug,
                'clicks' => (int) $t->clicks_count,
                'pricing' => $t->pricing,
            ])
            ->values()
            ->all();
    }

    public function getTopDictionaryTerms(int $limit = 10): array
    {
        if (! \Schema::hasColumn('dictionary_terms', 'views_count')) {
            return [];
        }

        return Term::where('is_published', true)
            ->where('views_count', '>', 0)
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'views_count'])
            ->map(function ($t) {
                $name = is_array($t->name) ? ($t->name['fr_CA'] ?? $t->name['fr'] ?? array_values($t->name)[0] ?? '') : (string) $t->name;
                $slug = is_array($t->slug) ? ($t->slug['fr_CA'] ?? $t->slug['fr'] ?? array_values($t->slug)[0] ?? '') : (string) $t->slug;

                return [
                    'name' => mb_strimwidth($name, 0, 50, '…'),
                    'slug' => $slug,
                    'views' => (int) $t->views_count,
                ];
            })
            ->values()
            ->all();
    }

    public function getPublicToolsActivity(int $days = 30): array
    {
        if (! \Schema::hasTable('public_tool_usages')) {
            return ['by_tool' => [], 'total' => 0];
        }

        $since = now()->subDays($days)->toDateString();

        $byTool = DB::table('public_tool_usages')
            ->where('day', '>=', $since)
            ->selectRaw('slug, SUM(count) as total')
            ->groupBy('slug')
            ->orderByDesc('total')
            ->pluck('total', 'slug')
            ->toArray();

        $labelMap = [
            'mots-croises' => 'Mots-croisés',
            'code-qr' => 'Code QR',
            'roue-tirage' => 'Roue de tirage',
            'generateur-equipes' => 'Générateur d\'équipes',
            'liens-google' => 'Liens Google',
        ];

        $items = [];
        foreach ($byTool as $slug => $total) {
            $items[] = [
                'slug' => $slug,
                'name' => $labelMap[$slug] ?? ucfirst(str_replace('-', ' ', (string) $slug)),
                'count' => (int) $total,
            ];
        }

        return [
            'by_tool' => $items,
            'total' => array_sum(array_column($items, 'count')),
        ];
    }

    public function getDeltaKpis(int $days = 30): array
    {
        $end = now();
        $startCurrent = now()->subDays($days);
        $startPrevious = now()->subDays($days * 2);

        $delta = function (int $current, int $previous): array {
            if ($previous === 0) {
                return ['current' => $current, 'previous' => 0, 'delta_pct' => $current > 0 ? 100.0 : 0.0, 'direction' => $current > 0 ? 'up' : 'flat'];
            }
            $pct = round((($current - $previous) / $previous) * 100, 1);
            $direction = $pct > 0 ? 'up' : ($pct < 0 ? 'down' : 'flat');

            return ['current' => $current, 'previous' => $previous, 'delta_pct' => $pct, 'direction' => $direction];
        };

        $newUsersCurrent = User::whereBetween('created_at', [$startCurrent, $end])->count();
        $newUsersPrevious = User::whereBetween('created_at', [$startPrevious, $startCurrent])->count();

        $articlesCurrent = Article::where('status', 'published')->whereBetween('published_at', [$startCurrent, $end])->count();
        $articlesPrevious = Article::where('status', 'published')->whereBetween('published_at', [$startPrevious, $startCurrent])->count();

        $subsCurrent = Subscriber::whereBetween('created_at', [$startCurrent, $end])->count();
        $subsPrevious = Subscriber::whereBetween('created_at', [$startPrevious, $startCurrent])->count();

        $activitiesCurrent = Activity::whereBetween('created_at', [$startCurrent, $end])->count();
        $activitiesPrevious = Activity::whereBetween('created_at', [$startPrevious, $startCurrent])->count();

        return [
            'new_users' => $delta($newUsersCurrent, $newUsersPrevious),
            'published_articles' => $delta($articlesCurrent, $articlesPrevious),
            'subscribers' => $delta($subsCurrent, $subsPrevious),
            'activities' => $delta($activitiesCurrent, $activitiesPrevious),
        ];
    }

    /**
     * 2026-05-05 #98 : statistiques shorturl pour admin /admin/stats.
     * Cache 5min (data agrégée non temps-réel critique).
     * Retourne : total / new sur période / clicks total période / avg clicks per link /
     *            ratio slug user-named (contient -/_) vs auto / top 10 users / sparkline créations + clics 30j.
     */
    public function getShortUrlStats(int $days = 30): array
    {
        if (! \Schema::hasTable('short_urls')) {
            return ['available' => false];
        }

        return \Cache::remember("admin.shorturl.stats.{$days}", now()->addMinutes(5), function () use ($days) {
            $since = now()->subDays($days);

            $totalActive = DB::table('short_urls')->whereNull('deleted_at')->where('is_active', true)->count();
            $totalAll = DB::table('short_urls')->whereNull('deleted_at')->count();
            $newOnPeriod = DB::table('short_urls')->whereNull('deleted_at')->where('created_at', '>=', $since)->count();
            $expired = DB::table('short_urls')->whereNull('deleted_at')->whereNotNull('expires_at')->where('expires_at', '<', now())->count();
            $totalClicks = (int) DB::table('short_urls')->whereNull('deleted_at')->sum('clicks_count');
            $avgClicks = $totalAll > 0 ? round($totalClicks / $totalAll, 1) : 0.0;

            // Ratio slug user-named (contient - ou _, donc non auto-gen 6 alphanum) vs auto-style.
            $userNamed = DB::table('short_urls')->whereNull('deleted_at')->where(function ($q) {
                $q->where('slug', 'like', '%-%')->orWhere('slug', 'like', '%\_%');
            })->count();
            $autoStyle = $totalAll - $userNamed;
            $userNamedRatio = $totalAll > 0 ? round(($userNamed / $totalAll) * 100, 1) : 0.0;

            // Anonymes vs authentifiés (si colonne is_anonymous existe).
            $hasAnonCol = \Schema::hasColumn('short_urls', 'is_anonymous');
            $anonymous = $hasAnonCol ? DB::table('short_urls')->whereNull('deleted_at')->where('is_anonymous', true)->count() : 0;
            $authenticated = $totalAll - $anonymous;

            // Top 10 users par count (ignore anonymes).
            $topUsers = DB::table('short_urls')
                ->whereNull('deleted_at')
                ->whereNotNull('user_id')
                ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(clicks_count) as total_clicks'))
                ->groupBy('user_id')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->map(function ($row) {
                    $user = User::find($row->user_id);
                    return [
                        'user_id' => $row->user_id,
                        'name' => $user?->name ?? 'Utilisateur supprimé',
                        'email' => $user?->email,
                        'count' => (int) $row->count,
                        'total_clicks' => (int) $row->total_clicks,
                    ];
                })
                ->all();

            // Sparkline 30 jours : créations + somme clicks par jour.
            $period = CarbonPeriod::create(now()->subDays(30)->startOfDay(), now()->startOfDay());
            $createdByDay = DB::table('short_urls')
                ->whereNull('deleted_at')
                ->where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
                ->groupBy('date')
                ->pluck('count', 'date')
                ->all();

            $hasClicksTable = \Schema::hasTable('short_url_clicks');
            $clicksByDay = $hasClicksTable
                ? DB::table('short_url_clicks')
                    ->where('clicked_at', '>=', now()->subDays(30))
                    ->select(DB::raw('DATE(clicked_at) as date'), DB::raw('COUNT(*) as count'))
                    ->groupBy('date')
                    ->pluck('count', 'date')
                    ->all()
                : [];

            $sparklineCreated = [];
            $sparklineClicks = [];
            foreach ($period as $day) {
                $key = $day->format('Y-m-d');
                $sparklineCreated[] = ['date' => $key, 'count' => (int) ($createdByDay[$key] ?? 0)];
                $sparklineClicks[] = ['date' => $key, 'count' => (int) ($clicksByDay[$key] ?? 0)];
            }

            // Lifetime moyen (création → expiration ou now si pas d'expiration).
            $lifetimeAvgDays = (int) DB::table('short_urls')
                ->whereNull('deleted_at')
                ->whereNotNull('expires_at')
                ->selectRaw('AVG(DATEDIFF(expires_at, created_at)) as avg_days')
                ->value('avg_days') ?: 0;

            return [
                'available' => true,
                'total_active' => $totalActive,
                'total_all' => $totalAll,
                'new_on_period' => $newOnPeriod,
                'expired' => $expired,
                'total_clicks' => $totalClicks,
                'avg_clicks_per_link' => $avgClicks,
                'user_named' => $userNamed,
                'auto_style' => $autoStyle,
                'user_named_ratio_pct' => $userNamedRatio,
                'anonymous' => $anonymous,
                'authenticated' => $authenticated,
                'lifetime_avg_days' => $lifetimeAvgDays,
                'top_users' => $topUsers,
                'sparkline_created_30d' => $sparklineCreated,
                'sparkline_clicks_30d' => $sparklineClicks,
            ];
        });
    }
}
