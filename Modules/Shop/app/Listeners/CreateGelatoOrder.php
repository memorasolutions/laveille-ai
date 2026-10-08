<?php

namespace Modules\Shop\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Shop\Events\ShopOrderPaid;
use Modules\Shop\Gelato\GelatoReconciler;
use Modules\Shop\Gelato\GelatoRoutingException;
use Modules\Shop\Gelato\OrderItemRouter;
use Modules\Shop\Gelato\GelatoSubmitException;
use Modules\Shop\Gelato\PrintFileNotApprovedException;
use Modules\Shop\Gelato\ZeroErreur;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\GelatoService;

class CreateGelatoOrder implements ShouldQueue
{
    /** Statuts où la commande ne doit plus jamais partir chez Gelato ni être remise « en traitement ». */
    private const DEAD_STATUSES = ['cancelled', 'refunded'];

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
                    Order::whereKey($order->id)->whereNull('gelato_order_id')->whereNull('gelato_submit_key')
                        ->whereNotIn('status', self::DEAD_STATUSES)
                        ->update([
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
        // (catalogue = store_variant_map, éditeur = PrintFile approuvé; jamais print_file_url).
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

        // Même verrou atomique que le chemin strict : le drapeau ne décide QUE de la manière d'envoyer
        // (strict = issue jamais masquée, simple = createOrder historique), jamais de l'absence de verrou.
        $this->lockAndSubmit($order, false);
    }

    /**
     * Chemin « zéro erreur » (drapeau shop.gelato_zero_erreur ON) :
     * 1. chaque item doit être routable (catalogue = mapping store, éditeur = fichier APPROVED), sinon refus, aucun envoi;
     * 2. idempotence : un seul envoi Gelato par commande, même si ShopOrderPaid arrive deux fois.
     */
    private function handleZeroErreur(Order $order): void
    {
        $order->refresh();
        if (! empty($order->gelato_order_id)) {
            Log::info("Gelato idempotence : commande #{$order->id} deja soumise ({$order->gelato_order_id}), ignoree");
            return;
        }

        $this->lockAndSubmit($order, true);
    }

    /**
     * Verrou + soumission PARTAGÉS par les deux chemins (drapeau ON/OFF) :
     * 1. relecture de gelato_order_id (idempotence);
     * 2. corps de commande construit UNE fois AVANT le verrou : un échec de routage est DÉFINITIF (aucune commande n'a été envoyée);
     * 3. verrou atomique compare-and-set sur gelato_submit_key, puis envoi.
     */
    private function lockAndSubmit(Order $order, bool $strict): void
    {
        $order->refresh();
        if (! empty($order->gelato_order_id)) {
            Log::info("Gelato idempotence : commande #{$order->id} deja soumise ({$order->gelato_order_id}), ignoree");
            return;
        }
        // Commande annulée/remboursée entre le paiement et ce job : on ne commande JAMAIS chez Gelato.
        if (in_array($order->status, self::DEAD_STATUSES, true)) {
            Log::warning("Gelato : commande #{$order->id} {$order->status} avant soumission, aucun envoi");
            return;
        }

        $order->loadMissing('items.product');
        try {
            if ($order->items->isEmpty()) {
                throw new PrintFileNotApprovedException('Commande sans article.');
            }
            $body = $this->gelatoService->buildOrderBody($order);
        } catch (PrintFileNotApprovedException|GelatoRoutingException $e) {
            // Rejeu après un envoi ambigu (timeout) : l'impossibilité de router MAINTENANT (produit retiré, fichier invalide)
            // ne prouve PAS qu'aucune commande n'est partie. Tant qu'une soumission a été tentée, on réconcilie, on ne « refuse » pas.
            if (! empty($order->gelato_submit_key)) {
                $this->handleExistingLock($order, (string) $order->gelato_submit_key);
                return;
            }
            $this->refuseUnroutable($order, $e);
            return;
        }

        $key = 'laveille-shop-order-'.($order->order_number ?? $order->id);

        // Verrou atomique (compare-and-set) : un seul processus obtient la clé; une annulation survenue entre-temps l'interdit.
        $claimed = Order::whereKey($order->id)
            ->whereNull('gelato_order_id')
            ->whereNull('gelato_submit_key')
            ->whereNotIn('status', self::DEAD_STATUSES)
            ->update(['gelato_submit_key' => $key, 'gelato_submit_started_at' => now(), 'gelato_submit_state' => 'submitting']);

        if ($claimed !== 1) {
            $this->handleExistingLock($order, $key);
            return;
        }

        $this->submit($order, $key, $body, $strict);
    }

    /** Commande PAYÉE mais non honorable (routage impossible, aucun envoi) : statut explicite + enregistrement persistant (notes intactes). */
    private function refuseUnroutable(Order $order, \Throwable $e): void
    {
        Log::error("Commande #{$order->id} PAYEE mais non honorable (routage Gelato) : {$e->getMessage()}");
        // Écriture CONDITIONNELLE : seulement si aucune commande Gelato n'existe, qu'aucune soumission n'est en vol
        // (clé posée) et que la commande n'est pas annulée/remboursée. Sinon le statut réel de la commande prime.
        Order::whereKey($order->id)->whereNull('gelato_order_id')->whereNull('gelato_submit_key')
            ->whereNotIn('status', self::DEAD_STATUSES)
            ->update([
                'status' => 'awaiting_print_file',
                'gelato_issue' => mb_substr('['.now()->toIso8601String().'] '.$e->getMessage(), 0, 1000),
            ]);
        $this->notifyAdmin($order, 'payee, non routable vers Gelato (fichier d\'impression ou mapping catalogue)', [$e->getMessage(),
            'La commande est PAYEE : approuver un fichier d\'impression puis relancer la soumission, ou rembourser (aucun remboursement automatique).']);
    }

    /** Clé déjà posée sans gelato_order_id : en cours, orphelin ou UNKNOWN. Jamais de création à l'aveugle ni sur résultat négatif. */
    private function handleExistingLock(Order $order, string $key): void
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
        // M6 (documenté, revue fable 2026-10-08) : la clé d'idempotence reste figée après un 400 suivi d'une correction
        // manuelle des données; une nouvelle tentative exige une intervention de l'admin (nouvelle clé).
        // TODO : auto-retry possible seulement une fois l'en-tête Idempotency-Key confirmé honoré par Gelato en réel.
        $this->notifyAdmin($fresh, 'verrou de soumission orphelin', ["Clé {$fresh->gelato_submit_key} sans gelato_order_id depuis "
            .($startedAt?->toDateTimeString() ?? 'inconnu').' (état : '.($fresh->gelato_submit_state ?? 'inconnu').'). Réconciliation tentée.']);

        app(GelatoReconciler::class)->reconcile($fresh);
    }

    private function submit(Order $order, string $key, array $body, bool $strict): void
    {
        try {
            if ($strict) {
                $gelatoOrderId = $this->gelatoService->submitOrderStrict($order, $key, $body);
            } else {
                $gelatoOrderId = $this->gelatoService->createOrder($order, $key, $body);
                if (! $gelatoOrderId) {
                    // La cause classée est conservée par le service : un rejet 4xx de validation (400) est DÉFINITIF et libère
                    // la clé (rien n'a été créé); sinon issue AMBIGUË (clé conservée, réconciliation).
                    throw $this->gelatoService->lastFailure ?? new GelatoSubmitException('Gelato createOrder a retourne null', false);
                }
            }
        } catch (GelatoRoutingException|PrintFileNotApprovedException $e) {
            // Défense en profondeur : aucune requête n'est partie, donc aucune commande. DÉFINITIF, jamais UNKNOWN.
            Order::whereKey($order->id)->whereNull('gelato_order_id')
                ->update(['gelato_submit_key' => null, 'gelato_submit_started_at' => null, 'gelato_submit_state' => 'rejected']);
            $this->refuseUnroutable($order, $e);
            return;
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

        Order::whereKey($order->id)->update(['gelato_order_id' => $gelatoOrderId, 'gelato_submit_state' => 'submitted']);
        // « Processing » seulement si la commande n'a pas été annulée/remboursée PENDANT l'envoi (sinon l'annulation prime).
        $stillLive = Order::whereKey($order->id)->whereNotIn('status', self::DEAD_STATUSES)->update(['status' => 'processing']);
        if ($stillLive !== 1) {
            Log::warning("Commande Gelato {$gelatoOrderId} creee pour commande #{$order->id} annulee pendant l'envoi : annulation Gelato manuelle requise");
            $this->notifyAdmin($order->fresh(), 'annulée pendant l\'envoi - commande Gelato créée', ["Commande Gelato {$gelatoOrderId} créée alors que la commande était annulée/remboursée : l'annuler chez Gelato."]);
            return;
        }
        $order->refresh();
        Log::info("Commande Gelato creee : {$gelatoOrderId} pour commande #{$order->id}");
    }

    /**
     * Valide (via le résolveur unique) que chaque item est routable de la commande.
     * Retourne tableau de raisons si invalide, [] si tout OK.
     */
    private function validateItemsDesign(Order $order): array
    {
        $router = app(OrderItemRouter::class);
        $invalid = [];
        foreach ($order->items as $item) {
            $reason = $router->validate($item);
            if ($reason !== null) {
                $invalid[] = "item#{$item->id} : {$reason}";
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
                . "Action requise : verifier shop_products.metadata (store_variant_map) ou le print file approuve pour les items concernes.\n"
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
