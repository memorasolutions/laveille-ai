<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Providers;

use Carbon\Carbon;
use Modules\Core\Contracts\MetricProviderInterface;
use Modules\Core\DataTransferObjects\MetricWidget;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\Refund;

class EcommerceMetricProvider implements MetricProviderInterface
{
    private const PAID_STATUSES = ['paid', 'shipped', 'delivered'];

    public function getMetricName(): string
    {
        return 'ecommerce';
    }

    /** @return list<MetricWidget> */
    public function getWidgets(): array
    {
        $metrics = $this->getMetrics(now()->startOfMonth(), now()->endOfMonth());
        $currency = (string) config('modules.ecommerce.currency', 'CAD');

        return [
            new MetricWidget(
                name: 'Revenu total',
                value: $metrics['revenue'].' '.$currency,
                type: 'currency',
                icon: 'dollar-sign',
                route: '/admin/ecommerce/analytics',
            ),
            new MetricWidget(
                name: 'Commandes ce mois',
                value: (string) $metrics['orders_count'],
                type: 'number',
                icon: 'shopping-cart',
            ),
            new MetricWidget(
                name: 'Panier moyen',
                value: $metrics['avg_order'].' '.$currency,
                type: 'currency',
                icon: 'trending-up',
            ),
            new MetricWidget(
                name: 'Produits actifs',
                value: (string) $metrics['products_active'],
                type: 'number',
                icon: 'package',
            ),
            new MetricWidget(
                name: 'Taux remboursement',
                value: $metrics['refund_rate'].'%',
                type: 'percent',
                icon: 'rotate-ccw',
            ),
        ];
    }

    /** @return array<string, mixed> */
    public function getMetrics(Carbon $from, Carbon $to): array
    {
        $paidOrders = Order::whereIn('status', self::PAID_STATUSES)
            ->whereBetween('created_at', [$from, $to]);

        $totalRevenue = (float) (clone $paidOrders)->sum('total');
        $paidCount = (clone $paidOrders)->count();
        $ordersCount = Order::whereBetween('created_at', [$from, $to])->count();
        $avgOrder = $paidCount > 0 ? $totalRevenue / $paidCount : 0.0;
        $productsActive = Product::where('is_active', true)->count();

        $refundsApproved = Refund::where('status', 'approved')
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $refundRate = $ordersCount > 0 ? ($refundsApproved / $ordersCount) * 100 : 0.0;

        return [
            'revenue' => number_format($totalRevenue, 2),
            'orders_count' => $ordersCount,
            'avg_order' => number_format($avgOrder, 2),
            'products_active' => $productsActive,
            'refund_rate' => number_format($refundRate, 1),
        ];
    }
}
