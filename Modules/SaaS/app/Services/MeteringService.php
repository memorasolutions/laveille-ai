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
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\UsageRecord;

class MeteringService
{
    public function record(User $user, string $metric, int $quantity = 1, ?array $metadata = null): UsageRecord
    {
        return UsageRecord::create([
            'user_id' => $user->id,
            'metric' => $metric,
            'quantity' => $quantity,
            'metadata' => $metadata,
            'recorded_at' => now(),
        ]);
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

    public function checkLimit(User $user, string $metric, int $limit): bool
    {
        return $this->getUsageByPeriod($user, $metric) < $limit;
    }

    public function resetMonthly(): int
    {
        return UsageRecord::where('recorded_at', '<', now()->startOfMonth())->delete();
    }

    /**
     * @return array<string, int>
     */
    public function getMetrics(User $user): array
    {
        return UsageRecord::forUser($user->id)
            ->select('metric', DB::raw('SUM(quantity) as total'))
            ->groupBy('metric')
            ->pluck('total', 'metric')
            ->map(fn ($v) => (int) $v)
            ->toArray();
    }
}
