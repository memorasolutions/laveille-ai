<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\StripeService;
use Modules\Shop\Events\ShopOrderFulfilled;
use Modules\Shop\Events\ShopOrderShipped;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function stripe(Request $request, StripeService $stripeService)
    {
        $stripeService->handleWebhook($request);

        return response()->json(['received' => true]);
    }

    public function gelato(Request $request)
    {
        try {
            $payload = $request->all();
            $event = $payload['event'] ?? null;
            $orderId = $payload['orderReferenceId'] ?? ($payload['order']['orderReferenceId'] ?? null);

            if (! $orderId || ! $event) {
                return response()->json(['received' => true]);
            }

            $order = Order::find($orderId);

            if (! $order) {
                Log::warning("Gelato webhook: order {$orderId} not found");
                return response()->json(['received' => true]);
            }

            match ($event) {
                'order:status:fulfilled', 'order_fulfilled' => $this->handleFulfilled($order),
                'order:status:shipped', 'order_shipped' => $this->handleShipped($order, $payload),
                default => Log::info("Gelato webhook: unhandled event {$event}"),
            };

            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            Log::error('Gelato webhook: ' . $e->getMessage());
            return response()->json(['received' => true]);
        }
    }

    private function handleFulfilled(Order $order): void
    {
        $order->update(['status' => 'fulfilled']);
        event(new ShopOrderFulfilled($order));
    }

    private function handleShipped(Order $order, array $payload): void
    {
        $tracking = $payload['shipment'] ?? $payload;
        $order->update([
            'status' => 'shipped',
            'tracking_number' => $tracking['trackingNumber'] ?? $tracking['tracking_number'] ?? null,
            'tracking_url' => $tracking['trackingUrl'] ?? $tracking['tracking_url'] ?? null,
        ]);
        event(new ShopOrderShipped($order, $order->tracking_number, $order->tracking_url));
    }
}
