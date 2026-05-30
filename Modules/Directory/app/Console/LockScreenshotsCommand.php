<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Modules\Directory\Models\Tool;

class LockScreenshotsCommand extends Command
{
    protected $signature = 'directory:lock-screenshots {slug? : slug fr_CA de l\'outil} {--all : verrouiller tous les outils ayant un screenshot non vide} {--unlock : déverrouiller au lieu de verrouiller}';

    protected $description = 'Verrouille (ou déverrouille) les screenshots pour empêcher leur écrasement par la régénération automatique.';

    public function handle(): int
    {
        $lock = ! $this->option('unlock');

        if ($this->option('all')) {
            $query = Tool::whereNotNull('screenshot')->where('screenshot', '!=', '');
        } elseif ($slug = $this->argument('slug')) {
            $query = Tool::where('slug->fr_CA', $slug);
        } else {
            $this->error('Fournir un slug ou --all');

            return self::FAILURE;
        }

        $count = $query->update(['screenshot_locked' => $lock]);

        $action = $lock ? 'verrouillé(s)' : 'déverrouillé(s)';
        $this->info("{$count} outil(s) {$action}.");

        return self::SUCCESS;
    }
}
