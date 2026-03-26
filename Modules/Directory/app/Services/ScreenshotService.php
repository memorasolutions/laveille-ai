<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Modules\Directory\Models\Tool;
use Throwable;

class ScreenshotService
{
    /**
     * Capture le screenshot d'un outil. Ne jamais ecraser un bon screenshot par un mauvais.
     */
    public function capture(Tool $tool): bool
    {
        if (! self::isAvailable()) {
            Log::warning('ScreenshotService: Node.js ou script introuvable.');

            return false;
        }

        $slug = $tool->getTranslation('slug', 'fr_CA');
        if (empty($slug) || empty($tool->url)) {
            return false;
        }

        $outputDir = public_path('screenshots');
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $filename = "{$slug}.jpg";
        $absolutePath = "{$outputDir}/{$filename}";
        $existingSize = File::exists($absolutePath) ? File::size($absolutePath) : 0;

        try {
            // Capturer dans un fichier temporaire pour ne pas ecraser l'existant
            $tempPath = "{$outputDir}/_tmp_{$filename}";

            $result = Process::timeout(90)->run([
                env('BROWSERSHOT_NODE_PATH', '/usr/local/bin/node'),
                base_path('scripts/capture-screenshot.cjs'),
                $tool->url,
                $tempPath,
            ]);

            $json = json_decode(trim($result->output()), true);

            if (! is_array($json)) {
                Log::warning("Screenshot {$slug}: reponse JSON invalide");
                @unlink($tempPath);

                return false;
            }

            // Echec explicite (bloque, trop petit, erreur)
            if (($json['success'] ?? false) !== true) {
                $reason = $json['error'] ?? 'Erreur inconnue';
                $blocked = $json['blocked'] ?? false;
                $tooSmall = $json['tooSmall'] ?? false;
                Log::warning("Screenshot {$slug}: {$reason}" . ($blocked ? ' [BLOQUE]' : '') . ($tooSmall ? ' [TROP PETIT]' : ''));
                @unlink($tempPath);

                return false;
            }

            // Succes : verifier que le fichier temporaire est valide
            if (! File::exists($tempPath) || File::size($tempPath) < 5000) {
                Log::warning("Screenshot {$slug}: fichier temporaire invalide");
                @unlink($tempPath);

                return false;
            }

            $newSize = File::size($tempPath);
            $method = $json['method'] ?? 'screenshot';

            // Protection : ne pas ecraser un bon screenshot (> 20 KB) par un plus petit
            if ($existingSize > 20000 && $newSize < $existingSize * 0.5) {
                Log::warning("Screenshot {$slug}: nouveau fichier ({$newSize}) beaucoup plus petit que l'existant ({$existingSize}) - conserve l'ancien");
                @unlink($tempPath);

                return false;
            }

            // Remplacer le fichier
            File::move($tempPath, $absolutePath);

            $tool->screenshot = "screenshots/{$filename}";
            $tool->saveQuietly();

            Log::info("Screenshot {$slug}: OK via {$method} (" . round($newSize / 1024) . ' KB)');

            return true;
        } catch (Throwable $e) {
            Log::warning("Screenshot exception {$slug}: {$e->getMessage()}");
            @unlink($outputDir . "/_tmp_{$filename}");
        }

        return false;
    }

    public function captureWithRetry(Tool $tool, int $maxAttempts = 3): bool
    {
        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($this->capture($tool)) {
                return true;
            }

            if ($attempt < $maxAttempts) {
                sleep((int) pow(2, $attempt));
            }
        }

        return false;
    }

    public static function isAvailable(): bool
    {
        return file_exists(env('BROWSERSHOT_NODE_PATH', '/usr/local/bin/node'))
            && file_exists(base_path('scripts/capture-screenshot.cjs'));
    }
}
