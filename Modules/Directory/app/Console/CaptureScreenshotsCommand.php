<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Process;
use Modules\Directory\Models\Tool;
use Throwable;

class CaptureScreenshotsCommand extends Command
{
    protected $signature = 'directory:capture-screenshots
                            {--slug= : Slug specifique de l\'outil (fr_CA)}
                            {--force : Forcer la recapture meme si le fichier existe}
                            {--missing : Traiter uniquement les outils sans screenshot ou fichier manquant}';

    protected $description = 'Capture les screenshots des outils via Puppeteer + idcac (cookie dismiss)';

    private const MAX_ATTEMPTS = 3;

    private const RATE_LIMIT_SECONDS = 5;

    public function handle(): int
    {
        $nodePath = env('BROWSERSHOT_NODE_PATH', '/usr/local/bin/node');
        $scriptPath = base_path('scripts/capture-screenshot.cjs');

        if (! file_exists($scriptPath)) {
            $this->error("Script Node.js introuvable : {$scriptPath}");

            return self::FAILURE;
        }

        $storagePath = public_path('screenshots');
        if (! File::isDirectory($storagePath)) {
            File::makeDirectory($storagePath, 0755, true);
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

            $filename = "{$slug}.jpg";
            $relativePath = "screenshots/{$filename}";
            $absolutePath = public_path($relativePath);
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

            $success = false;
            $lastError = '';

            for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
                try {
                    $result = Process::timeout(90)->run([
                        $nodePath, $scriptPath, $tool->url, $absolutePath,
                    ]);

                    $json = json_decode(trim($result->output()), true);

                    if (is_array($json) && ($json['success'] ?? false) === true && File::exists($absolutePath)) {
                        $success = true;

                        break;
                    }

                    $lastError = $json['error'] ?? $result->errorOutput() ?: 'Erreur inconnue';
                } catch (Throwable $e) {
                    $lastError = $e->getMessage();
                }

                if ($attempt < self::MAX_ATTEMPTS) {
                    sleep((int) pow(2, $attempt)); // 2s, 4s
                }
            }

            if ($success) {
                $tool->screenshot = $relativePath;
                $tool->saveQuietly();
                $stats['captured']++;
            } else {
                $this->newLine();
                $this->warn("  {$slug} : echec apres " . self::MAX_ATTEMPTS . " tentatives - {$lastError}");
                Log::warning("Screenshot echoue pour {$slug} ({$tool->url}): {$lastError}");
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
