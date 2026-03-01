<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\Plan;

final class SaasMetricsService
{
    public function getMrr(): float
    {
        $activePrices = DB::table('subscriptions')
            ->where('stripe_status', 'active')
            ->pluck('stripe_price');

        if ($activePrices->isEmpty()) {
            return 0.0;
        }

        $monthlyMrr = (float) Plan::whereIn('stripe_price_id', $activePrices)
            ->where('interval', 'monthly')
            ->sum('price');

        $yearlyMrr = (float) Plan::whereIn('stripe_price_id', $activePrices)
            ->where('interval', 'yearly')
            ->sum(DB::raw('price / 12'));

        return round($monthlyMrr + $yearlyMrr, 2);
    }

    public function getArr(): float
    {
        return round($this->getMrr() * 12, 2);
    }

    public function getActiveSubscribersCount(): int
    {
        return DB::table('subscriptions')
            ->where('stripe_status', 'active')
            ->count();
    }

    public function getTrialSubscribersCount(): int
    {
        return DB::table('subscriptions')
            ->where('stripe_status', 'trialing')
            ->count();
    }

    public function getCancelledThisMonth(): int
    {
        return DB::table('subscriptions')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>=', Carbon::now()->startOfMonth())
            ->count();
    }

    public function getChurnRate(): float
    {
        $cancelled = $this->getCancelledThisMonth();
        $active = $this->getActiveSubscribersCount();
        $total = $active + $cancelled;

        if ($total === 0) {
            return 0.0;
        }

        return round($cancelled / $total * 100, 2);
    }

    public function getNewSubscribersThisMonth(): int
    {
        return DB::table('subscriptions')
            ->where('created_at', '>=', Carbon::now()->startOfMonth())
            ->count();
    }

    /**
     * @return array<int, array{plan_name: string, subscriber_count: int, revenue: float}>
     */
    public function getRevenueByPlan(): array
    {
        $activePrices = DB::table('subscriptions')
            ->where('stripe_status', 'active')
            ->select('stripe_price', DB::raw('COUNT(*) as count'))
            ->groupBy('stripe_price')
            ->get();

        $result = [];

        foreach ($activePrices as $row) {
            $plan = Plan::where('stripe_price_id', $row->stripe_price)->first();

            if ($plan) {
                $revenue = $plan->interval === 'yearly'
                    ? (float) $plan->price / 12 * $row->count
                    : (float) $plan->price * $row->count;

                $result[] = [
                    'plan_name' => $plan->name,
                    'subscriber_count' => (int) $row->count,
                    'revenue' => round($revenue, 2),
                ];
            }
        }

        return $result;
    }

    /**
     * @return array{mrr: float, arr: float, active: int, trial: int, cancelled_this_month: int, churn_rate: float, new_this_month: int}
     */
    public function getAllMetrics(): array
    {
        return [
            'mrr' => $this->getMrr(),
            'arr' => $this->getArr(),
            'active' => $this->getActiveSubscribersCount(),
            'trial' => $this->getTrialSubscribersCount(),
            'cancelled_this_month' => $this->getCancelledThisMonth(),
            'churn_rate' => $this->getChurnRate(),
            'new_this_month' => $this->getNewSubscribersThisMonth(),
        ];
    }
}
