<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Services\ToolDiscoveryService;

class DiscoverNewToolsCommand extends Command
{
    protected $signature = 'tools:discover-new
        {--dry-run : Simuler sans insérer}
        {--source= : Filtrer une source (producthunt|rss)}';

    protected $description = 'Découvre de nouveaux outils IA depuis Product Hunt et flux RSS';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $source = $this->option('source');

        if ($source !== null && ! in_array($source, ['producthunt', 'rss'], true)) {
            $this->error("Source invalide : {$source}. Valeurs acceptées : producthunt, rss.");

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Mode simulation — aucune insertion.');
        }

        $this->info('Découverte de nouveaux outils IA...');

        try {
            $service = new ToolDiscoveryService;

            $discovered = match ($source) {
                'producthunt' => $service->fetchProductHunt(),
                'rss' => $service->fetchRssFeeds(),
                default => $service->discoverAll(),
            };

            $sourceLabel = $source ?? 'toutes les sources';
            $this->info("Source : {$sourceLabel}");

            $countDiscovered = count($discovered);
            $countIngested = 0;
            $countDuplicates = 0;

            if ($countDiscovered === 0) {
                $this->warn('Aucun nouvel outil découvert.');

                return self::SUCCESS;
            }

            $this->info("{$countDiscovered} outil(s) découvert(s).");
            $this->newLine();

            foreach ($discovered as $toolData) {
                $name = $toolData['name'] ?? 'Sans nom';
                $url = $toolData['url'] ?? 'N/A';

                $this->line("  {$name} — {$url}");

                if ($dryRun) {
                    $this->line("    [DRY] Serait ingéré : {$name}");

                    continue;
                }

                $result = $service->ingest($toolData);

                if ($result) {
                    $countIngested++;
                    $this->info("    Créé (ID:{$result->id})");
                } else {
                    $countDuplicates++;
                    $this->line("    Doublon, ignoré.");
                }
            }

            $this->newLine();
            $this->info("=== BILAN : {$countDiscovered} découverts, {$countIngested} ingérés, {$countDuplicates} doublons ===");

            Log::info('[DiscoverNewTools] Terminé', [
                'source' => $sourceLabel,
                'dry_run' => $dryRun,
                'discovered' => $countDiscovered,
                'ingested' => $countIngested,
                'duplicates' => $countDuplicates,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Erreur : {$e->getMessage()}");
            Log::error('[DiscoverNewTools] Échec', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}
