<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Ecommerce\Models\Order;

class SalesAnalyticsService
{
    public function getSummary(?Carbon $from = null, ?Carbon $to = null): array
    {
        $from ??= now()->subDays(30);
        $to ??= now();

        $orders = Order::whereBetween('created_at', [$from, $to]);

        return [
            'total_orders' => (clone $orders)->count(),
            'total_revenue' => (float) (clone $orders)->whereIn('status', ['paid', 'delivered'])->sum('total'),
            'average_order_value' => (float) (clone $orders)->whereIn('status', ['paid', 'delivered'])->avg('total'),
            'pending_orders' => (clone $orders)->where('status', 'pending')->count(),
            'paid_orders' => (clone $orders)->where('status', 'paid')->count(),
            'shipped_orders' => (clone $orders)->where('status', 'shipped')->count(),
            'delivered_orders' => (clone $orders)->where('status', 'delivered')->count(),
            'cancelled_orders' => (clone $orders)->where('status', 'cancelled')->count(),
            'refunded_orders' => (clone $orders)->where('status', 'refunded')->count(),
        ];
    }

    public function getRevenueByDay(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= now()->subDays(30);
        $to ??= now();

        return Order::whereIn('status', ['paid', 'delivered'])
            ->whereBetween('created_at', [$from, $to])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as revenue'), DB::raw('COUNT(*) as orders'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    public function getTopProducts(int $limit = 10, ?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from ??= now()->subDays(30);
        $to ??= now();

        return DB::table('ecommerce_order_items')
            ->join('ecommerce_orders', 'ecommerce_order_items.order_id', '=', 'ecommerce_orders.id')
            ->whereIn('ecommerce_orders.status', ['paid', 'delivered'])
            ->whereBetween('ecommerce_orders.created_at', [$from, $to])
            ->select(
                'ecommerce_order_items.product_name',
                DB::raw('SUM(ecommerce_order_items.quantity) as total_quantity'),
                DB::raw('SUM(ecommerce_order_items.total) as total_revenue'),
            )
            ->groupBy('ecommerce_order_items.product_name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();
    }

    public function getOrdersByStatus(): Collection
    {
        return Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');
    }
}
