<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Api\Http\Controllers\BaseApiController;
use Modules\Ecommerce\Models\Order;

class OrderApiController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->latest()
            ->with('items')
            ->paginate(10);

        return $this->respondSuccess($orders);
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        if ($order->user_id !== $request->user()->id) {
            return $this->respondForbidden();
        }

        $order->load(['items.variant.product', 'shippingAddress', 'billingAddress']);

        return $this->respondSuccess($order);
    }
}
