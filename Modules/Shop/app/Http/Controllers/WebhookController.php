<?php

namespace Modules\Shop\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shop\Gelato\ZeroErreur;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\StripeService;
use Modules\Shop\Events\ShopOrderFulfilled;
use Modules\Shop\Events\ShopOrderShipped;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function stripe(Request $request, StripeService $stripeService)
    {
        try {
            $accepted = $stripeService->handleWebhook($request);
        } catch (\Throwable $e) {
            // Signature valide, erreur interne : 5xx pour que Stripe rejoue (jamais 200).
            Log::error('Stripe webhook : erreur interne apres signature valide - '.$e->getMessage());
            return response()->json(['error' => 'Internal error, retry'], 500);
        }

        // Fail-closed (drapeau ON) : signature absente/invalide ou secret manquant -> refus 400.
        if (ZeroErreur::enabled() && $accepted === false) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        return response()->json(['received' => true]);
    }

    public function gelato(Request $request)
    {
        try {
            $secret = config('shop.gelato_webhook_secret');

            if (ZeroErreur::enabled()) {
                // Fail-closed : secret absent OU en-tête invalide -> refus, rien n'est traité.
                $given = (string) $request->header('X-Gelato-Secret');
                if (! $secret || ! hash_equals((string) $secret, $given)) {
                    Log::warning('Gelato webhook refuse (fail-closed) : '.($secret ? 'signature invalide' : 'GELATO_WEBHOOK_SECRET non configure'));
                    return response()->json(['error' => 'Unauthorized'], 401);
                }
            } elseif ($secret && $request->header('X-Gelato-Secret') !== $secret) {
                Log::warning('Gelato webhook : secret invalide');
                return response()->json(['error' => 'Unauthorized'], 401);
            }

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
            if (ZeroErreur::enabled()) {
                // Signature déjà validée plus haut : erreur interne -> 5xx pour que Gelato rejoue.
                return response()->json(['error' => 'Internal error, retry'], 500);
            }
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
