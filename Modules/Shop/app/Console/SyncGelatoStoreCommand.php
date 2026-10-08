<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

namespace Modules\Shop\Console;

use Illuminate\Console\Command;
use Modules\Shop\Services\GelatoSyncService;

class SyncGelatoStoreCommand extends Command
{
    protected $signature = 'shop:sync-gelato
        {--dry-run : Afficher les changements sans modifier la DB}
        {--force : Forcer la mise à jour même si inchangé}';

    protected $description = 'Synchronise les produits depuis le store Gelato vers la base de données (un produit retiré de Gelato est dépublié, jamais supprimé)';

    public function handle(GelatoSyncService $sync): int
    {
        $this->info('Récupération des produits du store Gelato...');

        $result = $sync->syncStore(
            ['dry_run' => (bool) $this->option('dry-run'), 'force' => (bool) $this->option('force')],
            fn (string $label, string $detail) => $this->components->twoColumnDetail($label, $detail),
        );

        if (! $result['ok']) {
            $this->error($result['error'] ?? 'Échec de la synchronisation.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->twoColumnDetail('Créés', (string) $result['created']);
        $this->components->twoColumnDetail('Mis à jour', (string) $result['updated']);
        $this->components->twoColumnDetail('Inchangés', (string) $result['unchanged']);
        $this->components->twoColumnDetail('Dépubliés (retirés de Gelato)', (string) $result['unpublished']);
        $this->components->twoColumnDetail('Erreurs', (string) $result['errors']);

        return $result['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
