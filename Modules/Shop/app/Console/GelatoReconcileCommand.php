<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Modules\Shop\Gelato\GelatoReconciler;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\GelatoService;

/**
 * Usage manuel ou planifié (AUCUN cron enregistré automatiquement). N'ADOPTE jamais à l'aveugle et ne CRÉE jamais de commande.
 */
class GelatoReconcileCommand extends Command
{
    protected $signature = 'shop:gelato-reconcile {--minutes=10 : âge minimal du verrou avant réconciliation} {--dry-run : liste seulement}';

    protected $description = 'Réconcilie les verrous de soumission Gelato unknown/orphelins : adopte la commande existante si trouvée, sinon alerte (ne crée jamais).';

    public function handle(GelatoReconciler $reconciler, GelatoService $gelato): int
    {
        // Aucune dépendance au drapeau « zéro erreur » : le verrou de soumission est posé par les DEUX chemins, et la
        // réconciliation n'adopte qu'une commande existante (elle ne crée jamais). Elle doit donc pouvoir débloquer
        // une commande UNKNOWN même drapeau OFF.
        if (! $gelato->isConfigured()) {
            $this->error('Clé API Gelato absente : réconciliation impossible.');

            return self::FAILURE;
        }

        $orders = Order::whereNull('gelato_order_id')->whereNotNull('gelato_submit_key')
            ->where(fn ($q) => $q->where('gelato_submit_state', 'unknown')
                ->orWhere(fn ($q2) => $q2->where('gelato_submit_state', 'submitting')
                    ->where('gelato_submit_started_at', '<', now()->subMinutes(max(1, (int) $this->option('minutes'))))))
            ->get();

        $adopted = $unknown = 0;
        foreach ($orders as $order) {
            if ($this->option('dry-run')) {
                $this->line("#{$order->id} {$order->order_number} [{$order->gelato_submit_state}]");
                continue;
            }
            $outcome = $reconciler->reconcile($order);
            $outcome === GelatoReconciler::ADOPTED ? $adopted++ : $unknown++;
            $this->line("#{$order->id} {$order->order_number} -> {$outcome}");
        }

        $this->info("Verrous examinés : {$orders->count()} ; adoptés : {$adopted} ; restés unknown (admin alerté) : {$unknown}.");

        return self::SUCCESS;
    }
}
