<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Backoffice\Services\AnalyticsService;

class AnalyticsController
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {}

    public function overview(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->analytics->getOverview($days),
        ]);
    }

    public function webhooks(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->analytics->getWebhookStats($days),
        ]);
    }

    public function content(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->analytics->getContentStats($days),
        ]);
    }

    public function activity(Request $request): JsonResponse
    {
        $days = $request->integer('days', 30);

        return response()->json([
            'success' => true,
            'data' => $this->analytics->getActivityTimeline($days),
        ]);
    }
}
