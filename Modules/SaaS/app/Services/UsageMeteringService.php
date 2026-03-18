<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\UsageRecord;

final class UsageMeteringService
{
    public function record(int $userId, string $metric, int $quantity = 1, array $metadata = []): UsageRecord
    {
        $record = UsageRecord::create([
            'user_id' => $userId,
            'metric' => $metric,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'recorded_at' => now(),
        ]);

        $this->forgetCache($userId, $metric);

        return $record;
    }

    public function getCurrentUsage(int $userId, string $metric): int
    {
        return (int) Cache::remember(
            $this->getCacheKey($userId, $metric),
            now()->addMinutes(5),
            fn () => UsageRecord::forUser($userId)
                ->forMetric($metric)
                ->inPeriod(now()->startOfMonth(), now()->endOfMonth())
                ->sum('quantity')
        );
    }

    public function checkLimit(int $userId, string $metric, int $limit): bool
    {
        return $this->getCurrentUsage($userId, $metric) < $limit;
    }

    public function getRemainingQuota(int $userId, string $metric, int $limit): int
    {
        return max(0, $limit - $this->getCurrentUsage($userId, $metric));
    }

    /** @return array<string, int> */
    public function getUsageSummary(int $userId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        return UsageRecord::forUser($userId)
            ->inPeriod($from, $to)
            ->select('metric', DB::raw('SUM(quantity) as total'))
            ->groupBy('metric')
            ->pluck('total', 'metric')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    /** @return array<string, int> */
    public function getUsageByDay(int $userId, string $metric, int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();
        $endDate = now()->endOfDay();

        $records = UsageRecord::forUser($userId)
            ->forMetric($metric)
            ->inPeriod($startDate, $endDate)
            ->select(DB::raw('DATE(recorded_at) as date'), DB::raw('SUM(quantity) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total', 'date')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $result = [];
        $current = $startDate->copy();
        while ($current->lte($endDate)) {
            $dateKey = $current->format('Y-m-d');
            $result[$dateKey] = $records[$dateKey] ?? 0;
            $current->addDay();
        }

        return $result;
    }

    private function getCacheKey(int $userId, string $metric): string
    {
        return 'usage_metering_'.$userId.'_'.$metric.'_'.now()->format('Y_m');
    }

    private function forgetCache(int $userId, string $metric): void
    {
        Cache::forget($this->getCacheKey($userId, $metric));
    }
}
