<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Backoffice\Services\AnalyticsService;

class StatsController
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    public function __invoke(Request $request): View
    {
        $days = $request->integer('days', 30);

        $overview = $this->analytics->getOverview($days);
        $userGrowth = $this->analytics->getUserGrowth();
        $contentStats = $this->analytics->getContentStats($days);
        $activityTimeline = $this->analytics->getActivityTimeline($days);
        $webhookStats = $this->analytics->getWebhookStats($days);

        return view('backoffice::stats.index', [
            'title' => 'Statistiques',
            'subtitle' => 'Analytiques',
            'overview' => $overview,
            'userGrowth' => $userGrowth,
            'contentStats' => $contentStats,
            'activityTimeline' => $activityTimeline,
            'webhookStats' => $webhookStats,
            'days' => $days,
        ]);
    }
}
