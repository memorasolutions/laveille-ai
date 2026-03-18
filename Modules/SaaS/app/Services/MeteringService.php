<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\UsageRecord;

class MeteringService
{
    public function record(User $user, string $metric, int $quantity = 1, ?array $metadata = null): UsageRecord
    {
        $record = UsageRecord::create([
            'user_id' => $user->id,
            'metric' => $metric,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'recorded_at' => now(),
        ]);

        $this->forgetCache($user->id, $metric);

        return $record;
    }

    public function getUsage(User $user, string $metric, ?Carbon $since = null): int
    {
        $query = UsageRecord::forUser($user->id)->forMetric($metric);

        if ($since) {
            $query->where('recorded_at', '>=', $since);
        }

        return (int) $query->sum('quantity');
    }

    public function getUsageByPeriod(User $user, string $metric, string $period = 'month'): int
    {
        $startDate = match ($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        return (int) UsageRecord::forUser($user->id)
            ->forMetric($metric)
            ->where('recorded_at', '>=', $startDate)
            ->sum('quantity');
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

    public function checkLimit(User $user, string $metric, int $limit): bool
    {
        return $this->getUsageByPeriod($user, $metric) < $limit;
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

    /** @return array<string, int> */
    public function getMetrics(User $user): array
    {
        return UsageRecord::forUser($user->id)
            ->select('metric', DB::raw('SUM(quantity) as total'))
            ->groupBy('metric')
            ->pluck('total', 'metric')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }

    public function resetMonthly(): int
    {
        return UsageRecord::where('recorded_at', '<', now()->startOfMonth())->delete();
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
