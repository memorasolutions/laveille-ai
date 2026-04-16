<?php

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\Order;

class CleanupPendingOrdersCommand extends Command
{
    protected $signature = 'shop:cleanup-pending {--hours=168}';

    protected $description = 'Supprime les commandes en attente (pending) expirées (défaut: 168h / 7 jours pour laisser la place aux rappels d\'abandon)';

    public function handle(): int
    {
        if (! class_exists(Order::class)) {
            $this->error('Module Shop introuvable.');

            return self::FAILURE;
        }

        $hours = max(1, (int) $this->option('hours'));

        $count = Order::where('status', 'pending')
            ->where('created_at', '<', now()->subHours($hours))
            ->delete();

        if ($count > 0) {
            Log::info("[Shop] {$count} commande(s) fantôme(s) supprimée(s) (pending > {$hours}h).");
        }

        $this->info("{$count} commande(s) nettoyée(s).");

        return self::SUCCESS;
    }
}
