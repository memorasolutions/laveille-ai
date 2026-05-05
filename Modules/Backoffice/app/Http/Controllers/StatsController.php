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
        $newsletterGrowth = $this->analytics->getNewsletterGrowth($days);
        $topArticles = $this->analytics->getTopArticles($days, 5);
        $deltaKpis = $this->analytics->getDeltaKpis($days);
        $activityByType = $this->analytics->getActivityTimelineByType($days);
        $topDirectoryTools = $this->analytics->getTopDirectoryTools(10);
        $topDictionaryTerms = $this->analytics->getTopDictionaryTerms(10);
        $publicToolsActivity = $this->analytics->getPublicToolsActivity($days);
        $shortUrlStats = $this->analytics->getShortUrlStats($days);

        return view('backoffice::stats.index', [
            'title' => 'Statistiques',
            'subtitle' => 'Analytiques',
            'overview' => $overview,
            'userGrowth' => $userGrowth,
            'contentStats' => $contentStats,
            'activityTimeline' => $activityTimeline,
            'webhookStats' => $webhookStats,
            'newsletterGrowth' => $newsletterGrowth,
            'topArticles' => $topArticles,
            'deltaKpis' => $deltaKpis,
            'activityByType' => $activityByType,
            'topDirectoryTools' => $topDirectoryTools,
            'topDictionaryTerms' => $topDictionaryTerms,
            'publicToolsActivity' => $publicToolsActivity,
            'shortUrlStats' => $shortUrlStats,
            'days' => $days,
        ]);
    }
}
