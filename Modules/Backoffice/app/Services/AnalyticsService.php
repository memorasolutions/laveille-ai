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
use Modules\Blog\Models\Article;
use Modules\Blog\Models\Category;
use Modules\Blog\Models\Comment;
use Modules\Newsletter\Models\Subscriber;
use Modules\Webhooks\Models\WebhookCall;
use Spatie\Activitylog\Models\Activity;

class AnalyticsService
{
    public function getOverview(int $days = 30): array
    {
        $since = now()->subDays($days);
        $totalWebhookCalls = WebhookCall::count();

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
            'webhook_success_rate' => $totalWebhookCalls > 0
                ? round((WebhookCall::where('status', 'success')->count() / $totalWebhookCalls) * 100, 1)
                : 0,
            'total_activities' => Activity::count(),
        ];
    }

    public function getWebhookStats(int $days = 30): array
    {
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
}
