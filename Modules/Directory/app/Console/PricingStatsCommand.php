<?php declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolPricingReport;

class PricingStatsCommand extends Command
{
    protected $signature = 'directory:pricing-stats';

    protected $description = 'Affiche les statistiques actuelles du pipeline pricing';

    public function handle(): int
    {
        $this->info("=== Statistiques pricing ===");

        $metrics = Tool::healthMetrics();
        $distribution = $metrics['distribution'];
        $rows = [];
        foreach ($distribution as $k => $v) {
            $rows[] = [$k, $v];
        }
        $this->table(['Tarification', 'Outils'], $rows);

        $autoFlagged = ToolPricingReport::pending()->autoFlagged()->count();
        $userSubmitted = ToolPricingReport::pending()->userSubmitted()->count();

        $this->info("Files de revision pending:");
        $this->table(['Type', 'Count'], [
            ['Auto-flag systeme', $autoFlagged],
            ['Soumis utilisateurs', $userSubmitted]
        ]);

        $this->info("Outils avec derive (>= 90j ou jamais verifies):");
        $this->table(['Metrique', 'Count'], [
            ['Total derive', $metrics['drift_90']],
            ['Jamais verifies', $metrics['never_checked']]
        ]);

        return self::SUCCESS;
    }
}
