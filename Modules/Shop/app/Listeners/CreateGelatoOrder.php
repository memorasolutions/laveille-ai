<?php

namespace Modules\Shop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Services\GelatoService;

class CreateGelatoOrder implements ShouldQueue
{
    public function __construct(
        protected GelatoService $gelatoService,
    ) {}

    public function handle(ShopOrderPaid $event): void
    {
        $order = $event->order;

        if (! $this->gelatoService->isConfigured()) {
            Log::warning("Gelato non configuré — commande #{$order->id} ignorée");
            return;
        }

        try {
            $gelatoOrderId = $this->gelatoService->createOrder($order);

            if (! $gelatoOrderId) {
                throw new \RuntimeException('Gelato createOrder a retourné null');
            }

            $order->update([
                'gelato_order_id' => $gelatoOrderId,
                'status' => 'processing',
            ]);

            Log::info("Commande Gelato créée : {$gelatoOrderId} pour commande #{$order->id}");
        } catch (\Throwable $e) {
            Log::error("Échec création commande Gelato pour commande #{$order->id}: {$e->getMessage()}");

            $order->update([
                'status' => 'gelato_failed',
                'notes' => "Échec Gelato : {$e->getMessage()}",
            ]);
        }
    }
}
