<?php

declare(strict_types=1);

namespace Modules\RolesPermissions\Console;

use Illuminate\Console\Command;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'app:sync-permissions';

    protected $description = 'Synchronise les permissions et vide le cache';

    public function handle(): int
    {
        $this->info('Synchronisation des permissions...');
        $this->call('permission:cache-reset');
        $this->call('cache:clear');
        $this->info('Permissions synchronisées avec succès.');

        return Command::SUCCESS;
    }
}
