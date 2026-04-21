<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;

class ExpireFeaturedToolsCommand extends Command
{
    protected $signature = 'tools:expire-featured {--dry-run : Affiche sans modifier}';

    protected $description = 'Désactive le sponsoring des outils dont featured_until est dépassé (daily)';

    public function handle(): int
    {
        $tools = Tool::query()->expiredSponsorship()->get();
        $dryRun = (bool) $this->option('dry-run');
        $count = 0;

        foreach ($tools as $tool) {
            if ($dryRun) {
                Log::info("Dry-run: sponsoring expiré pour l'outil {$tool->id} {$tool->name}");
            } else {
                $tool->deactivateSponsorship();
                Log::info("{$tool->id} {$tool->name} expiré");
                $count++;
            }
        }

        if ($count > 0 && ! $dryRun) {
            activity('directory')->log("Auto-expire {$count} sponsoring(s)");
        }

        return self::SUCCESS;
    }
}
