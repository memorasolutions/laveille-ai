<?php

namespace Modules\Shop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Models\Order;
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
            Log::warning("Gelato non configure - commande #{$order->id} ignoree");
            return;
        }

        // Guard #213 (post-incident #210) : chaque item doit avoir un design valide
        // (storeProductVariantId via store_variant_map OU print_file_url non vide).
        // Sans guard, fallback silencieux imprime PNG local potentiellement obsolete.
        $invalidItems = $this->validateItemsDesign($order);
        if (! empty($invalidItems)) {
            $reason = "Design Gelato invalide : " . implode(' ; ', $invalidItems);
            Log::error("Commande #{$order->id} REFUSEE soumission Gelato : {$reason}");

            $order->update([
                'notes' => mb_substr("[GUARD-CONFIG] {$reason}", 0, 500),
            ]);

            $this->notifyAdminInvalidConfig($order, $invalidItems);
            return;
        }

        try {
            $gelatoOrderId = $this->gelatoService->createOrder($order);

            if (! $gelatoOrderId) {
                throw new \RuntimeException('Gelato createOrder a retourne null');
            }

            $order->update([
                'gelato_order_id' => $gelatoOrderId,
                'status' => 'processing',
            ]);

            Log::info("Commande Gelato creee : {$gelatoOrderId} pour commande #{$order->id}");
        } catch (\Throwable $e) {
            Log::error("Echec creation commande Gelato pour commande #{$order->id}: {$e->getMessage()}");

            $order->update([
                'notes' => "Echec Gelato : " . mb_substr($e->getMessage(), 0, 500),
            ]);

            $this->notifyAdminInvalidConfig($order, ["Exception API Gelato : " . mb_substr($e->getMessage(), 0, 200)]);
        }
    }

    /**
     * Valide qu'un design imprimable existe pour chaque item de la commande.
     * Retourne tableau de raisons si invalide, [] si tout OK.
     */
    private function validateItemsDesign(Order $order): array
    {
        $invalid = [];
        foreach ($order->items as $item) {
            $product = $item->product;
            if (! $product) {
                $invalid[] = "item#{$item->id}=produit_introuvable";
                continue;
            }
            if (empty($item->gelato_variant_id)) {
                $invalid[] = "item#{$item->id}=variant_id_absent";
                continue;
            }

            $meta = $product->metadata ?? [];
            $hasStoreVariant = ! empty($meta['store_variant_map'][$item->gelato_variant_id] ?? null);
            $hasPrintFile = ! empty($meta['print_file_url'] ?? null);

            if (! $hasStoreVariant && ! $hasPrintFile) {
                $invalid[] = "item#{$item->id} ({$product->name}) sans design (ni store_variant_map ni print_file_url)";
            }
        }
        return $invalid;
    }

    /**
     * Notifie l'admin par email via le mailer Brevo (transport custom Memora).
     */
    private function notifyAdminInvalidConfig(Order $order, array $reasons): void
    {
        $adminEmail = config('shop.admin_email') ?: config('mail.from.address') ?: env('ADMIN_EMAIL');
        if (! $adminEmail) {
            Log::warning("notifyAdminInvalidConfig : aucun ADMIN_EMAIL configure");
            return;
        }

        try {
            $body = "Commande #{$order->id} ({$order->order_number}) refusee avant soumission Gelato.\n\n"
                . "Email client : {$order->email}\n"
                . "Total : {$order->total} {$order->currency}\n\n"
                . "Raisons :\n  - " . implode("\n  - ", $reasons) . "\n\n"
                . "Action requise : verifier shop_products.metadata (store_variant_map ou print_file_url) pour les items concernes.\n"
                . "Lien admin : " . url('/admin/shop/orders');

            Mail::raw($body, function ($m) use ($adminEmail, $order) {
                $m->to($adminEmail)
                  ->subject("[laveille.ai] Commande Gelato #{$order->id} refusee - config invalide");
            });

            Log::info("Admin notifie config invalide commande #{$order->id} -> {$adminEmail}");
        } catch (\Throwable $e) {
            Log::error("Echec notification admin commande #{$order->id} : {$e->getMessage()}");
        }
    }
}
