<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\SaaS\Services\SaasMetricsService;

class RevenueController extends Controller
{
    public function index(SaasMetricsService $metrics): View
    {
        $activeCount = $metrics->getActiveSubscribersCount();
        $trialCount = $metrics->getTrialSubscribersCount();
        $mrr = $metrics->getMrr();
        $arr = $metrics->getArr();
        $newSubsThisMonth = $metrics->getNewSubscribersThisMonth();
        $cancelledThisMonth = $metrics->getCancelledThisMonth();
        $churnRate = $metrics->getChurnRate();
        $revenueByPlan = $metrics->getRevenueByPlan();

        $graceCount = DB::table('subscriptions')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->count();

        return view('backoffice::revenue.index', compact(
            'activeCount',
            'trialCount',
            'graceCount',
            'mrr',
            'arr',
            'newSubsThisMonth',
            'cancelledThisMonth',
            'churnRate',
            'revenueByPlan',
        ));
    }

    public function metrics(SaasMetricsService $metrics): JsonResponse
    {
        return response()->json([
            'success' => true,
            'metrics' => $metrics->getAllMetrics(),
            'revenue_by_plan' => $metrics->getRevenueByPlan(),
        ]);
    }
}
