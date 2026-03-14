<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Health\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Health\Models\HealthIncident;
use Spatie\Health\ResultStores\ResultStore;

class StatusPageController extends Controller
{
    public function index(): View
    {
        $results = app(ResultStore::class)->latestResults();

        $checks = [];
        $okCount = 0;
        $totalChecks = 0;

        if ($results) {
            foreach ($results->storedCheckResults as $result) {
                $totalChecks++;
                $status = $result->status ?? 'unknown';

                if ($status === 'ok') {
                    $okCount++;
                }

                $checks[] = [
                    'name' => $result->name,
                    'status' => $status,
                    'summary' => $result->shortSummary ?? '',
                ];
            }
        }

        $uptimePercentage = $totalChecks > 0 ? round(($okCount / $totalChecks) * 100, 1) : 100;

        $incidents = HealthIncident::recent()
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('health::public.status', [
            'checks' => $checks,
            'uptimePercentage' => $uptimePercentage,
            'totalChecks' => $totalChecks,
            'okCount' => $okCount,
            'incidents' => $incidents,
        ]);
    }
}
