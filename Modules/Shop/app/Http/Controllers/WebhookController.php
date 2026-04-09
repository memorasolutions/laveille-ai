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

            if (! $event) {
                Log::info('Gelato webhook : événement manquant dans le payload');
                return response()->json(['received' => true]);
            }

            $order = $this->resolveOrder($payload);

            if (! $order) {
                Log::warning('Gelato webhook : commande introuvable', [
                    'orderReferenceId' => $payload['orderReferenceId'] ?? null,
                    'orderId' => $payload['orderId'] ?? null,
                    'event' => $event,
                ]);
                return response()->json(['received' => true]);
            }

            match ($event) {
                'order_status_updated' => $this->handleStatusUpdated($order, $payload),
                'order_item_tracking_code_updated' => $this->handleTrackingUpdated($order, $payload),
                default => Log::info("Gelato webhook : événement non géré « {$event} »"),
            };

            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            Log::error('Gelato webhook : erreur — ' . $e->getMessage());
            return response()->json(['received' => true]);
        }
    }

    private function resolveOrder(array $payload): ?Order
    {
        $orderReferenceId = $payload['orderReferenceId']
            ?? ($payload['order']['orderReferenceId'] ?? null);

        if ($orderReferenceId) {
            $order = Order::where('order_number', $orderReferenceId)->first();
            if ($order) {
                return $order;
            }
        }

        $gelatoOrderId = $payload['orderId']
            ?? ($payload['order']['orderId'] ?? null);

        if ($gelatoOrderId) {
            $order = Order::where('gelato_order_id', $gelatoOrderId)->first();
            if ($order) {
                return $order;
            }
        }

        return null;
    }

    private function handleStatusUpdated(Order $order, array $payload): void
    {
        $fulfillmentStatus = $payload['fulfillmentStatus']
            ?? ($payload['order']['fulfillmentStatus'] ?? null);

        if (! $fulfillmentStatus) {
            Log::warning('Gelato webhook : fulfillmentStatus manquant pour la commande ' . $order->order_number);
            return;
        }

        $fulfillmentStatus = strtolower($fulfillmentStatus);

        match ($fulfillmentStatus) {
            'processing' => $this->handleProcessing($order),
            'shipped' => $this->handleShipped($order, $payload),
            'fulfilled' => $this->handleFulfilled($order),
            'cancelled', 'canceled' => $this->handleCancelled($order),
            default => Log::info("Gelato webhook : statut non géré « {$fulfillmentStatus} » pour la commande {$order->order_number}"),
        };
    }

    private function handleProcessing(Order $order): void
    {
        $order->update(['status' => 'processing']);
        Log::info("Gelato webhook : commande {$order->order_number} en cours de traitement");
    }

    private function handleFulfilled(Order $order): void
    {
        $order->update(['status' => 'fulfilled']);
        event(new ShopOrderFulfilled($order));
        Log::info("Gelato webhook : commande {$order->order_number} complétée");
    }

    private function handleShipped(Order $order, array $payload): void
    {
        $tracking = $this->extractTracking($payload);

        $order->update([
            'status' => 'shipped',
            'tracking_number' => $tracking['trackingCode'],
            'tracking_url' => $tracking['trackingUrl'],
        ]);

        event(new ShopOrderShipped($order, $order->tracking_number, $order->tracking_url));
        Log::info("Gelato webhook : commande {$order->order_number} expédiée", [
            'tracking_number' => $order->tracking_number,
        ]);
    }

    private function handleCancelled(Order $order): void
    {
        $order->update(['status' => 'cancelled']);
        Log::info("Gelato webhook : commande {$order->order_number} annulée");
    }

    private function handleTrackingUpdated(Order $order, array $payload): void
    {
        $tracking = $this->extractTracking($payload);

        if (! $tracking['trackingCode'] && ! $tracking['trackingUrl']) {
            return;
        }

        $updateData = [];
        if ($tracking['trackingCode']) {
            $updateData['tracking_number'] = $tracking['trackingCode'];
        }
        if ($tracking['trackingUrl']) {
            $updateData['tracking_url'] = $tracking['trackingUrl'];
        }

        if (! empty($updateData)) {
            $order->update($updateData);
            Log::info("Gelato webhook : suivi mis à jour pour la commande {$order->order_number}");
        }
    }

    private function extractTracking(array $payload): array
    {
        $trackingCode = null;
        $trackingUrl = null;

        $items = $payload['items'] ?? ($payload['order']['items'] ?? []);
        if (! empty($items) && is_array($items)) {
            $firstItem = $items[0] ?? [];
            $tracking = $firstItem['tracking'] ?? $firstItem;
            $trackingCode = $tracking['trackingCode'] ?? null;
            $trackingUrl = $tracking['trackingUrl'] ?? null;
        }

        if (! $trackingCode) {
            $trackingCode = $payload['trackingCode'] ?? ($payload['tracking']['trackingCode'] ?? null);
        }
        if (! $trackingUrl) {
            $trackingUrl = $payload['trackingUrl'] ?? ($payload['tracking']['trackingUrl'] ?? null);
        }

        return ['trackingCode' => $trackingCode, 'trackingUrl' => $trackingUrl];
    }
}
