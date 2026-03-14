<?php

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use Illuminate\View\View;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Models\Product;
use Modules\Ecommerce\Models\ProductVariant;

class DashboardController
{
    public function index(): View
    {
        $totalRevenue = Order::where('status', 'completed')->sum('total');
        $totalOrders = Order::count();
        $totalProducts = Product::count();
        $recentOrders = Order::with('user')->latest()->limit(10)->get();
        $lowStockThreshold = (int) config('modules.ecommerce.stock.low_threshold', 5);
        $lowStockVariants = ProductVariant::with('product')
            ->where('stock', '<', $lowStockThreshold)
            ->where('is_active', true)
            ->get();

        return view('ecommerce::admin.dashboard', compact(
            'totalRevenue',
            'totalOrders',
            'totalProducts',
            'recentOrders',
            'lowStockVariants'
        ));
    }
}
