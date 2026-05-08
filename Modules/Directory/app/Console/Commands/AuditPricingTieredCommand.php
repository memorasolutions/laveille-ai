<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Directory\Console\Commands;

use Illuminate\Console\Command;
use Modules\Directory\Services\PricingAudit\PricingAuditScheduler;
use Modules\Directory\Services\PricingAudit\Sources\BrowserFetchPricingSource;
use Modules\Directory\Services\PricingAudit\Sources\LlmPricingSource;
use Modules\Directory\Services\PricingAudit\Sources\PlaywrightScreenshotSource;
use Modules\Directory\Services\PricingAudit\Sources\PpSearchPricingSource;
use Modules\Directory\Services\PricingAudit\ToolPricingAuditor;

class AuditPricingTieredCommand extends Command
{
    protected $signature = 'tools:audit-pricing-tiered
                            {--limit=10 : Nombre max d\'outils à auditer en 1 run}
                            {--dry-run : Simulation sans persistance}
                            {--skip-screenshot : Skip Playwright capture (CI/local sans Node)}';

    protected $description = 'Audit pricing tiered (top100 30j, mid 60j, longtail 90j) avec multi-source consensus.';

    public function handle(PricingAuditScheduler $scheduler): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');
        $skipScreenshot = (bool) $this->option('skip-screenshot');

        $due = $scheduler->nextDueTools($limit);
        $this->info("Outils à auditer : {$due->count()}");

        if ($due->isEmpty()) {
            $this->info('Aucun outil dû selon les SLA tiers.');

            return self::SUCCESS;
        }

        $auditor = $this->makeAuditor($skipScreenshot);

        $changes = [];
        foreach ($due as $tool) {
            $tier = $scheduler->tierFor($tool);
            $this->line("[{$tier}] Audit ID={$tool->id} {$tool->name}");

            if ($dryRun) {
                continue;
            }

            $audit = $auditor->auditTool($tool);
            $change = $scheduler->detectChange($tool, $audit);
            if ($change) {
                $changes[] = $change;
                $this->warn("  CHANGE detected : {$change['old']} -> {$change['new']} (conf={$change['confidence']})");
            } else {
                $this->info("  OK ({$audit->real_pricing}, conf={$audit->confidence})");
            }
        }

        if (! empty($changes) && ! $dryRun) {
            $this->info("\n=== {$this->colorize(count($changes))} changements détectés ===");
            foreach ($changes as $c) {
                $this->line("  - {$c['name']} : {$c['old']} -> {$c['new']}");
            }
            // TODO S+1 : Notification email admin via Mail::to(env('SUPER_ADMIN_EMAIL'))->send(...)
        }

        return self::SUCCESS;
    }

    private function makeAuditor(bool $skipScreenshot): ToolPricingAuditor
    {
        $auditor = new ToolPricingAuditor();
        $auditor->addSource(app(BrowserFetchPricingSource::class));
        $auditor->addSource(app(PpSearchPricingSource::class));
        $auditor->addSource(app(LlmPricingSource::class));
        if (! $skipScreenshot) {
            $auditor->addSource(app(PlaywrightScreenshotSource::class));
        }

        return $auditor;
    }

    private function colorize(int $count): string
    {
        return $count > 5 ? "<fg=red>{$count}</>" : (string) $count;
    }
}
