<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SaaS\Services\MeteringService;

class UsageApiController extends Controller
{
    public function __construct(private readonly MeteringService $metering) {}

    public function current(Request $request): JsonResponse
    {
        $request->validate(['metric' => 'required|string']);

        $metric = $request->string('metric')->toString();
        $userId = $request->user()->id;
        $usage = $this->metering->getCurrentUsage($userId, $metric);
        $limit = config("saas.limits.{$metric}");
        $remaining = $limit !== null ? $this->metering->getRemainingQuota($userId, $metric, (int) $limit) : null;

        return response()->json(compact('metric', 'usage', 'limit', 'remaining'));
    }

    public function summary(Request $request): JsonResponse
    {
        $request->validate(['from' => 'nullable|date', 'to' => 'nullable|date']);

        $from = $request->filled('from') ? Carbon::parse($request->input('from')) : null;
        $to = $request->filled('to') ? Carbon::parse($request->input('to')) : null;

        $metrics = $this->metering->getUsageSummary($request->user()->id, $from, $to);

        return response()->json([
            'period' => [
                'from' => ($from ?? now()->startOfMonth())->toDateString(),
                'to' => ($to ?? now()->endOfMonth())->toDateString(),
            ],
            'metrics' => $metrics,
        ]);
    }

    public function daily(Request $request): JsonResponse
    {
        $request->validate([
            'metric' => 'required|string',
            'days' => 'integer|min:1|max:365',
        ]);

        $metric = $request->string('metric')->toString();
        $days = $request->integer('days', 30);
        $data = $this->metering->getUsageByDay($request->user()->id, $metric, $days);

        return response()->json(compact('metric', 'days', 'data'));
    }

    public function record(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'metric' => 'required|string',
            'quantity' => 'required|integer|min:1',
            'metadata' => 'nullable|array',
        ]);

        $record = $this->metering->record(
            $request->user(),
            $validated['metric'],
            (int) $validated['quantity'],
            $validated['metadata'] ?? null,
        );

        return response()->json(['record' => $record], 201);
    }
}
