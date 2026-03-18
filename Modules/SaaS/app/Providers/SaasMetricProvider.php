<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Providers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Core\Contracts\MetricProviderInterface;
use Modules\Core\DataTransferObjects\MetricWidget;
use Modules\SaaS\Models\Plan;

class SaasMetricProvider implements MetricProviderInterface
{
    public function getMetricName(): string
    {
        return 'saas';
    }

    /** @return list<MetricWidget> */
    public function getWidgets(): array
    {
        $metrics = $this->getMetrics(now()->startOfMonth(), now()->endOfMonth());

        return [
            new MetricWidget(name: 'MRR', value: $metrics['mrr'].' CAD', type: 'currency', icon: 'dollar-sign'),
            new MetricWidget(name: 'Abonnes actifs', value: (string) $metrics['active'], type: 'number', icon: 'users'),
            new MetricWidget(name: 'Taux churn', value: $metrics['churn_rate'].'%', type: 'percent', icon: 'user-minus'),
            new MetricWidget(name: 'Nouveaux ce mois', value: (string) $metrics['new_this_month'], type: 'number', icon: 'user-plus'),
        ];
    }

    /** @return array<string, mixed> */
    public function getMetrics(Carbon $from, Carbon $to): array
    {
        $active = DB::table('subscriptions')->where('stripe_status', 'active')->count();
        $trial = DB::table('subscriptions')->where('stripe_status', 'trialing')->count();

        $cancelled = DB::table('subscriptions')
            ->whereNotNull('ends_at')
            ->where('ends_at', '>=', $from)
            ->where('ends_at', '<=', $to)
            ->count();

        $total = $active + $cancelled;
        $churnRate = $total > 0 ? round($cancelled / $total * 100, 1) : 0.0;

        $newThisMonth = DB::table('subscriptions')
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $activePrices = DB::table('subscriptions')
            ->where('stripe_status', 'active')
            ->pluck('stripe_price');

        $monthlyMrr = $activePrices->isEmpty() ? 0.0 : (float) Plan::whereIn('stripe_price_id', $activePrices)
            ->where('interval', 'monthly')->sum('price');
        $yearlyMrr = $activePrices->isEmpty() ? 0.0 : (float) Plan::whereIn('stripe_price_id', $activePrices)
            ->where('interval', 'yearly')->sum(DB::raw('price / 12'));

        return [
            'mrr' => number_format($monthlyMrr + $yearlyMrr, 2),
            'active' => $active,
            'trial' => $trial,
            'cancelled' => $cancelled,
            'churn_rate' => number_format($churnRate, 1),
            'new_this_month' => $newThisMonth,
        ];
    }
}
