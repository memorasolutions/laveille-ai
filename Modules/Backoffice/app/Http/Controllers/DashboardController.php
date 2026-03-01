<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;
use Modules\Blog\Models\Article;
use Modules\Newsletter\Models\Subscriber;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class DashboardController
{
    public function __invoke(): View
    {
        $start = now()->subMonths(11)->startOfMonth();

        $userDates = User::where('created_at', '>=', $start)
            ->pluck('created_at')
            ->groupBy(fn ($d) => $d->format('Y-n'))
            ->map->count();

        $articleDates = Article::where('created_at', '>=', $start)
            ->pluck('created_at')
            ->groupBy(fn ($d) => $d->format('Y-n'))
            ->map->count();

        $usersByMonth = collect(range(11, 0))->map(function ($i) use ($userDates) {
            $date = now()->subMonths($i);

            return ['label' => $date->locale('fr')->isoFormat('MMM'), 'count' => $userDates->get($date->format('Y-n'), 0)];
        })->values()->all();

        $articlesByMonth = collect(range(11, 0))->map(function ($i) use ($articleDates) {
            $date = now()->subMonths($i);

            return ['label' => $date->locale('fr')->isoFormat('MMM'), 'count' => $articleDates->get($date->format('Y-n'), 0)];
        })->values()->all();

        return view('backoffice::dashboard.index', [
            'usersCount' => User::count(),
            'activeUsersCount' => User::where('is_active', true)->count(),
            'newUsersThisMonth' => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'newUsersThisWeek' => User::where('created_at', '>=', now()->subWeek())->count(),
            'rolesCount' => Role::count(),
            'articlesCount' => Article::count(),
            'publishedCount' => Article::where('status', 'published')->count(),
            'subscribersCount' => Subscriber::active()->count(),
            'subscribersGrowth' => Subscriber::where('created_at', '>=', now()->subDays(30))->count(),
            'recentActivities' => Activity::with('causer')->latest()->limit(10)->get(),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'environment' => app()->environment(),
            'usersByMonth' => $usersByMonth,
            'articlesByMonth' => $articlesByMonth,
            'isMaintenanceMode' => app()->isDownForMaintenance(),
        ]);
    }
}
