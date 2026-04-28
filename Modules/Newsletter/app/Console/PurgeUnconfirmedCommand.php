<?php

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Newsletter\Models\Subscriber;

class PurgeUnconfirmedCommand extends Command
{
    protected $signature = 'newsletter:purge-unconfirmed {--dry-run : Aperçu sans modification}';

    protected $description = 'Marque comme désabonnés (unsubscribed_at) les abonnés non confirmés depuis 7+ jours (conforme Loi 25 - aucune suppression de données)';

    public function handle(): int
    {
        $subscribers = Subscriber::whereNull('confirmed_at')
            ->whereNull('unsubscribed_at')
            ->where('created_at', '<', now()->subDays(7))
            ->get();

        if ($this->option('dry-run')) {
            if ($subscribers->isEmpty()) {
                $this->info('Aucun abonné non confirmé depuis plus de 7 jours à traiter.');
            } else {
                $this->info('Mode dry-run - Liste des abonnés qui seraient marqués comme désabonnés :');
                foreach ($subscribers as $subscriber) {
                    $this->line("- {$subscriber->email} (créé le {$subscriber->created_at->format('Y-m-d H:i:s')})");
                }
                $this->info("Total : {$subscribers->count()} abonnés.");
            }

            return self::SUCCESS;
        }

        $ok = 0;
        $fail = 0;

        foreach ($subscribers as $subscriber) {
            try {
                $subscriber->update([
                    'unsubscribed_at' => now(),
                    'bounce_reason' => 'auto_purge_unconfirmed_j7',
                ]);
                $ok++;
            } catch (\Throwable $e) {
                Log::error('Erreur lors de la mise à jour de l\'abonné non confirmé', [
                    'subscriber_id' => $subscriber->id,
                    'email' => $subscriber->email,
                    'error' => $e->getMessage(),
                ]);
                $fail++;
            }
        }

        Log::info("Purge J+7 marqués unsubscribed : {$ok} OK, {$fail} fail");
        $this->info("Purge J+7 marqués unsubscribed : {$ok} OK, {$fail} fail");

        return self::SUCCESS;
    }
}
