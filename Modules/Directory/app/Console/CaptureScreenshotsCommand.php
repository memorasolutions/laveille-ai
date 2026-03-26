<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotService;

class CaptureScreenshotsCommand extends Command
{
    protected $signature = 'directory:capture-screenshots
                            {--slug= : Slug specifique de l\'outil (fr_CA)}
                            {--force : Forcer la recapture meme si le fichier existe}
                            {--missing : Traiter uniquement les outils sans screenshot ou fichier manquant}';

    protected $description = 'Capture les screenshots des outils via Puppeteer + idcac (cookie dismiss)';

    private const RATE_LIMIT_SECONDS = 5;

    public function handle(ScreenshotService $service): int
    {
        if (! ScreenshotService::isAvailable()) {
            $this->error('Node.js ou script capture-screenshot.cjs introuvable.');

            return self::FAILURE;
        }

        $query = Tool::published();

        if ($slug = $this->option('slug')) {
            $query->where('slug->fr_CA', $slug);
        }

        $tools = $query->get();
        $total = $tools->count();

        if ($total === 0) {
            $this->info('Aucun outil a traiter.');

            return self::SUCCESS;
        }

        $this->info("Traitement de {$total} outil(s)...");
        $stats = ['captured' => 0, 'errors' => 0, 'skipped' => 0];
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($tools as $index => $tool) {
            $slug = $tool->getTranslation('slug', 'fr_CA');

            if (empty($slug) || empty($tool->url)) {
                $stats['skipped']++;
                $bar->advance();

                continue;
            }

            $absolutePath = public_path("screenshots/{$slug}.jpg");
            $fileExists = File::exists($absolutePath);

            if ($this->option('force')) {
                // Toujours capturer
            } elseif ($this->option('missing')) {
                if (! empty($tool->screenshot) && $fileExists) {
                    $stats['skipped']++;
                    $bar->advance();

                    continue;
                }
            } else {
                if ($fileExists) {
                    $stats['skipped']++;
                    $bar->advance();

                    continue;
                }
            }

            if ($service->captureWithRetry($tool)) {
                $stats['captured']++;
            } else {
                $this->newLine();
                $this->warn("  {$slug} : echec apres 3 tentatives");
                $stats['errors']++;
            }

            $bar->advance();

            if ($index < $total - 1) {
                sleep(self::RATE_LIMIT_SECONDS);
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->table(
            ['Resultat', 'Nombre'],
            [
                ['Captures', $stats['captured']],
                ['Erreurs', $stats['errors']],
                ['Ignores', $stats['skipped']],
                ['Total', $total],
            ]
        );

        return self::SUCCESS;
    }
}
