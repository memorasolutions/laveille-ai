<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Modules\Core\Services\MetaScraperService;
use Modules\Core\Services\TranslationService;
use Modules\Directory\Models\Tool;

class DirectoryEnrichToolsCommand extends Command
{
    protected $signature = 'directory:enrich-tools {--force : Force re-enrichment}';

    protected $description = 'Enrich directory tools with screenshots (og:image) and FR translations';

    public function handle(): int
    {
        $tools = Tool::published()->get();
        $force = $this->option('force');

        $this->info("Enrichissement de {$tools->count()} outils...");
        $bar = $this->output->createProgressBar($tools->count());

        $stats = ['screenshots' => 0, 'translations' => 0, 'errors' => 0];

        foreach ($tools as $tool) {
            if (! $tool->url) {
                $bar->advance();
                continue;
            }

            // Screenshot (og:image)
            if ($force || ! $tool->screenshot) {
                try {
                    $screenshot = MetaScraperService::captureScreenshot($tool->url);
                    if ($screenshot) {
                        $tool->screenshot = $screenshot;
                        $stats['screenshots']++;
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("  Screenshot echoue pour {$tool->name}: {$e->getMessage()}");
                    $stats['errors']++;
                }
            }

            // Traduction si description semble anglaise
            $desc = $tool->getTranslation('description', 'fr_CA', false) ?? '';
            if ($desc && preg_match('/\b(the|and|for|this|that|with|is|are|can|will|your)\b/i', $desc)) {
                try {
                    $translated = TranslationService::translate($desc, 'en', 'fr');
                    if ($translated !== $desc) {
                        $tool->setTranslation('description', 'fr_CA', $translated);
                        $stats['translations']++;
                    }

                    $shortDesc = $tool->getTranslation('short_description', 'fr_CA', false) ?? '';
                    if ($shortDesc && preg_match('/\b(the|and|for|this|that|with)\b/i', $shortDesc)) {
                        $translatedShort = TranslationService::translate($shortDesc, 'en', 'fr');
                        if ($translatedShort !== $shortDesc) {
                            $tool->setTranslation('short_description', 'fr_CA', $translatedShort);
                        }
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("  Traduction echouee pour {$tool->name}: {$e->getMessage()}");
                    $stats['errors']++;
                }
            }

            $tool->save();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Operation', 'Nombre'],
            [
                ['Screenshots captures', $stats['screenshots']],
                ['Descriptions traduites', $stats['translations']],
                ['Erreurs', $stats['errors']],
            ]
        );

        return self::SUCCESS;
    }
}
