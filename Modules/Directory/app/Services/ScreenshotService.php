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

        try {
            $result = Process::timeout(90)->run([
                env('BROWSERSHOT_NODE_PATH', '/usr/local/bin/node'),
                base_path('scripts/capture-screenshot.cjs'),
                $tool->url,
                $absolutePath,
            ]);

            $json = json_decode(trim($result->output()), true);

            if (is_array($json) && ($json['success'] ?? false) === true && File::exists($absolutePath)) {
                $tool->screenshot = "screenshots/{$filename}";
                $tool->saveQuietly();

                return true;
            }

            Log::warning("Screenshot echoue pour {$slug}: " . ($json['error'] ?? $result->errorOutput() ?: 'Erreur inconnue'));
        } catch (Throwable $e) {
            Log::warning("Screenshot exception pour {$slug}: {$e->getMessage()}");
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
