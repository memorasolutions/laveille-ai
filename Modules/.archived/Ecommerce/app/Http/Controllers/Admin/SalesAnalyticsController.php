<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Ecommerce\Services\SalesAnalyticsService;

class SalesAnalyticsController extends Controller
{
    public function __construct(
        protected SalesAnalyticsService $analytics,
    ) {}

    public function index(Request $request): View
    {
        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : now()->subDays(30);
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : now();

        $summary = $this->analytics->getSummary($from, $to);
        $revenueByDay = $this->analytics->getRevenueByDay($from, $to);
        $topProducts = $this->analytics->getTopProducts(10, $from, $to);
        $ordersByStatus = $this->analytics->getOrdersByStatus();

        return view('ecommerce::admin.analytics.index', compact(
            'summary', 'revenueByDay', 'topProducts', 'ordersByStatus', 'from', 'to',
        ));
    }
}
