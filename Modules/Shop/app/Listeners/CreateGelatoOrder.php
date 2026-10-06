<?php

namespace Modules\Shop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Gelato\GelatoReconciler;
use Modules\Shop\Gelato\GelatoSubmitException;
use Modules\Shop\Gelato\PrintFileNotApprovedException;
use Modules\Shop\Gelato\PrintFileService;
use Modules\Shop\Gelato\ZeroErreur;
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
            if (ZeroErreur::enabled()) {
                // Commande PAYÉE qui ne partira pas : jamais de retour muet.
                $order->refresh();
                if (empty($order->gelato_order_id)) {
                    $order->update([
                        'status' => 'awaiting_print_file',
                        'gelato_issue' => mb_substr('['.now()->toIso8601String().'] Clé API Gelato absente : commande payée non transmise.', 0, 1000),
                    ]);
                    $this->notifyAdmin($order, 'payée, Gelato non configuré (clé API absente)', [
                        'Aucun envoi effectué. Configurer la clé API Gelato puis relancer la soumission, ou rembourser (aucun remboursement automatique).']);
                }
            }
            return;
        }

        if (ZeroErreur::enabled()) {
            $this->handleZeroErreur($order);
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
     * Chemin « zéro erreur » (drapeau shop.gelato_zero_erreur ON) :
     * 1. fichier d'impression APPROVED obligatoire pour chaque item (sinon refus, aucun envoi);
     * 2. idempotence : un seul envoi Gelato par commande, même si ShopOrderPaid arrive deux fois.
     */
    private function handleZeroErreur(Order $order): void
    {
        $order->refresh();
        if (! empty($order->gelato_order_id)) {
            Log::info("Gelato idempotence : commande #{$order->id} deja soumise ({$order->gelato_order_id}), ignoree");
            return;
        }

        $order->loadMissing('items.product');
        try {
            $urls = $this->approvedPrintFileUrls($order);
        } catch (PrintFileNotApprovedException $e) {
            // Commande PAYÉE mais non honorable : statut explicite + enregistrement persistant (notes intactes).
            Log::error("Commande #{$order->id} PAYEE mais non honorable (print file) : {$e->getMessage()}");
            $order->update([
                'status' => 'awaiting_print_file',
                'gelato_issue' => mb_substr('['.now()->toIso8601String().'] '.$e->getMessage(), 0, 1000),
            ]);
            $this->notifyAdmin($order, 'payee, en attente du fichier d\'impression', [$e->getMessage(),
                'La commande est PAYEE : approuver un fichier d\'impression puis relancer la soumission, ou rembourser (aucun remboursement automatique).']);
            return;
        }

        $key = 'laveille-shop-order-'.($order->order_number ?? $order->id);

        // Verrou atomique (compare-and-set) : un seul processus obtient la clé.
        $claimed = Order::whereKey($order->id)
            ->whereNull('gelato_order_id')
            ->whereNull('gelato_submit_key')
            ->update(['gelato_submit_key' => $key, 'gelato_submit_started_at' => now(), 'gelato_submit_state' => 'submitting']);

        if ($claimed !== 1) {
            $this->handleExistingLock($order, $urls, $key);
            return;
        }

        $this->submit($order, $urls, $key);
    }

    /** Clé déjà posée sans gelato_order_id : en cours, orphelin ou UNKNOWN. Jamais de création à l'aveugle ni sur résultat négatif. */
    private function handleExistingLock(Order $order, array $urls, string $key): void
    {
        $fresh = Order::find($order->id);
        if (! $fresh || ! empty($fresh->gelato_order_id)) {
            Log::info("Gelato idempotence : commande #{$order->id} deja soumise, deuxieme evenement ignore");
            return;
        }

        $startedAt = $fresh->gelato_submit_started_at;
        $orphanAfter = max(1, (int) config('shop.gelato_orphan_minutes', 10));
        if ($startedAt !== null && $startedAt->gt(now()->subMinutes($orphanAfter))) {
            Log::warning("Gelato idempotence : commande #{$order->id} deja en cours, deuxieme evenement ignore");
            return;
        }
        if ($fresh->gelato_submit_key === null) {
            return; // clé libérée entre-temps (rejet confirmé) : reprise manuelle seulement
        }

        // Verrou orphelin ou état UNKNOWN : on tente d'ADOPTER une commande existante, JAMAIS de recréer.
        // Résultat négatif/vide/échec => reste unknown + alerte admin (voir GelatoReconciler).
        // TODO : auto-retry possible seulement une fois l'en-tête Idempotency-Key confirmé honoré par Gelato en réel.
        $this->notifyAdmin($fresh, 'verrou de soumission orphelin', ["Clé {$fresh->gelato_submit_key} sans gelato_order_id depuis "
            .($startedAt?->toDateTimeString() ?? 'inconnu').' (état : '.($fresh->gelato_submit_state ?? 'inconnu').'). Réconciliation tentée.']);

        app(GelatoReconciler::class)->reconcile($fresh);
    }

    private function submit(Order $order, array $urls, string $key): void
    {
        try {
            $gelatoOrderId = $this->gelatoService->submitOrderStrict($order, $urls, $key);
        } catch (GelatoSubmitException $e) {
            if ($e->definitive) {
                // Rejet CONFIRMÉ avant création : aucune commande n'existe, on libère la clé.
                Order::whereKey($order->id)->whereNull('gelato_order_id')
                    ->update(['gelato_submit_key' => null, 'gelato_submit_started_at' => null, 'gelato_submit_state' => 'rejected']);
                $state = 'rejete';
            } else {
                // Issue AMBIGUË : la commande peut exister. On NE libère PAS la clé (anti double commande).
                Order::whereKey($order->id)->whereNull('gelato_order_id')->update(['gelato_submit_state' => 'unknown']);
                $state = 'UNKNOWN (clé conservée)';
            }
            Log::error("Echec soumission Gelato commande #{$order->id} [{$state}] : {$e->getMessage()}");
            $order->update(['notes' => 'Echec Gelato : '.mb_substr($e->getMessage(), 0, 500)]);
            $this->notifyAdmin($order, "echec soumission - {$state}", ['Exception API Gelato : '.mb_substr($e->getMessage(), 0, 200)]);
            return;
        } catch (\Throwable $e) {
            Order::whereKey($order->id)->whereNull('gelato_order_id')->update(['gelato_submit_state' => 'unknown']);
            Log::error("Echec inattendu soumission Gelato commande #{$order->id} [UNKNOWN] : {$e->getMessage()}");
            $this->notifyAdmin($order, 'echec soumission - UNKNOWN (clé conservée)', [mb_substr($e->getMessage(), 0, 200)]);
            return;
        }

        $order->update(['gelato_order_id' => $gelatoOrderId, 'status' => 'processing', 'gelato_submit_state' => 'submitted']);
        Log::info("Commande Gelato creee : {$gelatoOrderId} pour commande #{$order->id}");
    }

    /**
     * @return array<int,string> orderItemId => URL publique du print file approuvé
     * @throws PrintFileNotApprovedException
     */
    private function approvedPrintFileUrls(Order $order): array
    {
        $service = app(PrintFileService::class);
        $urls = [];
        foreach ($order->items as $item) {
            $urls[$item->id] = $service->assertOrderable((int) $item->product_id, $item->gelato_variant_id, $item->gelato_variant_id)->public_url;
        }
        if ($urls === []) {
            throw new PrintFileNotApprovedException('Commande sans article.');
        }

        return $urls;
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

    /** Alerte courriel admin générique (même canal Mail que l'alerte de configuration). */
    private function notifyAdmin(Order $order, string $subjectSuffix, array $reasons): void
    {
        $adminEmail = config('shop.admin_email') ?: config('mail.from.address');
        if (! $adminEmail) {
            Log::warning('notifyAdmin : aucun ADMIN_EMAIL configure');
            return;
        }

        try {
            $body = "Commande #{$order->id} ({$order->order_number}) - {$subjectSuffix}.\n\n"
                . "Email client : {$order->email}\n"
                . "Total : {$order->total} {$order->currency}\n\n"
                . "Détails :\n  - " . implode("\n  - ", $reasons) . "\n\n"
                . 'Lien admin : ' . url('/admin/shop/orders');

            Mail::raw($body, function ($m) use ($adminEmail, $order, $subjectSuffix) {
                $m->to($adminEmail)->subject("[laveille.ai] Commande Gelato #{$order->id} : {$subjectSuffix}");
            });
        } catch (\Throwable $e) {
            Log::error("Echec alerte admin commande #{$order->id} : {$e->getMessage()}");
        }
    }

    /**
     * Notifie l'admin par email via le mailer Brevo (transport custom Memora).
     */
    private function notifyAdminInvalidConfig(Order $order, array $reasons): void
    {
        $adminEmail = config('shop.admin_email') ?: config('mail.from.address');
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
