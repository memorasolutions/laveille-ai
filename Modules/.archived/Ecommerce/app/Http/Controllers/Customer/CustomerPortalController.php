<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Order;
use Modules\Ecommerce\Services\DigitalDownloadService;

class CustomerPortalController extends Controller
{
    public function __construct(
        protected DigitalDownloadService $downloadService,
    ) {}

    public function dashboard(Request $request): View
    {
        $user = $request->user();

        $totalOrders = Order::where('user_id', $user->id)->count();
        $pendingCount = Order::where('user_id', $user->id)->where('status', 'pending')->count();
        $recentOrders = Order::where('user_id', $user->id)->latest()->take(5)->get();

        return view('ecommerce::customer.dashboard', compact('totalOrders', 'pendingCount', 'recentOrders'));
    }

    public function orders(Request $request): View
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(15);

        return view('ecommerce::customer.orders', compact('orders'));
    }

    public function orderShow(Request $request, Order $order): View
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(403);
        }

        $order->load(['items', 'shippingAddress', 'billingAddress', 'refunds']);

        return view('ecommerce::customer.order-show', compact('order'));
    }

    public function downloads(Request $request): View
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->whereIn('status', ['paid', 'delivered'])
            ->with(['items.variant.product.digitalAssets'])
            ->get();

        $downloads = [];

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $product = $item->variant?->product;
                if (! $product) {
                    continue;
                }

                foreach ($product->digitalAssets()->active()->get() as $asset) {
                    if ($this->downloadService->canDownload($asset, $order, $user)) {
                        $downloads[] = [
                            'asset_id' => $asset->id,
                            'filename' => $asset->original_filename,
                            'download_url' => $this->downloadService->generateDownloadUrl($asset, $order),
                            'order_number' => $order->order_number,
                            'order_id' => $order->id,
                        ];
                    }
                }
            }
        }

        return view('ecommerce::customer.downloads', compact('downloads'));
    }
}
