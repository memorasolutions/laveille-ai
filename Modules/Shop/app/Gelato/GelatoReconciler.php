<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

declare(strict_types=1);

namespace Modules\Shop\Gelato;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\GelatoService;

/**
 * Réconciliation d'un verrou de soumission Gelato orphelin / UNKNOWN (source unique : listener ET commande artisan).
 *
 * RÈGLE ABSOLUE : on peut seulement ADOPTER l'id d'une commande Gelato EXISTANTE, trouvée de façon certaine.
 * Un résultat vide, null ou une recherche en échec ne prouve PAS l'absence de commande (index en retard, panne) :
 * l'état reste « unknown », l'admin est alerté, et RIEN n'est créé.
 *
 * TODO : auto-retry (re-création) possible seulement une fois l'en-tête Idempotency-Key confirmé honoré par Gelato en réel.
 */
class GelatoReconciler
{
    public const ADOPTED = 'adopted';
    public const UNKNOWN = 'unknown';

    public function __construct(private GelatoService $gelato) {}

    /** @return self::ADOPTED|self::UNKNOWN */
    public function reconcile(Order $order): string
    {
        $reference = (string) ($order->order_number ?? $order->id);

        try {
            $existing = $this->gelato->findOrderIdByReference($reference);
        } catch (\Throwable $e) {
            Log::error("Reconciliation Gelato impossible pour commande #{$order->id} : {$e->getMessage()}");
            $this->markUnknown($order, 'Réconciliation impossible : '.$e->getMessage());
            $this->alert($order, 'réconciliation Gelato impossible ou ambiguë - aucune adoption, aucune création', [
                $e->getMessage(),
                'Intervention manuelle requise : vérifier le tableau de bord Gelato, puis adopter l\'id ou libérer la clé.',
            ]);

            return self::UNKNOWN;
        }

        if ($existing !== null && $existing !== '') {
            Order::whereKey($order->id)->whereNull('gelato_order_id')->update([
                'gelato_order_id' => $existing, 'status' => 'processing', 'gelato_submit_state' => 'submitted',
                'gelato_issue' => 'Commande Gelato retrouvée par réconciliation ('.$existing.')',
            ]);
            Log::warning("Gelato reconciliation : commande #{$order->id} adoptee -> {$existing}");

            return self::ADOPTED;
        }

        // Non trouvée : on ne CRÉE PAS. Vérification manuelle dans le tableau de bord Gelato.
        $this->markUnknown($order, 'Aucune commande Gelato retrouvée par la recherche, absence NON prouvée : vérifier le tableau de bord Gelato puis trancher manuellement.');
        $this->alert($order, 'verrou de soumission non résolu - création automatique REFUSÉE', [
            "Clé {$order->gelato_submit_key} sans gelato_order_id (état : unknown). La recherche Gelato n'a rien retourné; cela ne prouve pas qu'aucune commande n'existe.",
            'Aucune nouvelle commande créée (anti double impression). Vérifier chez Gelato, puis adopter l\'id ou libérer la clé manuellement.',
        ]);

        return self::UNKNOWN;
    }

    private function markUnknown(Order $order, string $issue): void
    {
        Order::whereKey($order->id)->whereNull('gelato_order_id')
            ->update(['gelato_submit_state' => 'unknown', 'gelato_issue' => mb_substr($issue, 0, 1000)]);
    }

    /** Alerte courriel admin (canal Mail commun). */
    public function alert(Order $order, string $subjectSuffix, array $reasons): void
    {
        $adminEmail = config('shop.admin_email') ?: config('mail.from.address');
        if (! $adminEmail) {
            Log::warning('GelatoReconciler::alert : aucun ADMIN_EMAIL configure');

            return;
        }

        try {
            $body = "Commande #{$order->id} ({$order->order_number}) - {$subjectSuffix}.\n\n"
                ."Email client : {$order->email}\n"
                ."Total : {$order->total} {$order->currency}\n\n"
                ."Détails :\n  - ".implode("\n  - ", $reasons)."\n\n"
                .'Lien admin : '.url('/admin/shop/orders');

            Mail::raw($body, function ($m) use ($adminEmail, $order, $subjectSuffix) {
                $m->to($adminEmail)->subject("[laveille.ai] Commande Gelato #{$order->id} : {$subjectSuffix}");
            });
        } catch (\Throwable $e) {
            Log::error("Echec alerte admin commande #{$order->id} : {$e->getMessage()}");
        }
    }
}
