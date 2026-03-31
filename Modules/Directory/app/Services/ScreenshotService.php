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
                Log::warning("Screenshot {$slug}: {$reason}".($blocked ? ' [BLOQUE]' : '').($tooSmall ? ' [TROP PETIT]' : ''));
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

            Log::info("Screenshot {$slug}: OK via {$method} (".round($newSize / 1024).' KB)');

            return true;
        } catch (Throwable $e) {
            Log::warning("Screenshot exception {$slug}: {$e->getMessage()}");
            @unlink($outputDir."/_tmp_{$filename}");
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

        // Fallback : gradient colore si toutes les tentatives echouent
        $slug = $tool->getTranslation('slug', 'fr_CA');
        $existingPath = public_path($tool->screenshot ?? '');
        if (! File::exists($existingPath) || File::size($existingPath) < 20000) {
            Log::info("Screenshot {$slug}: generation gradient fallback");

            return self::generateFallbackGradient($tool);
        }

        return false;
    }

    /**
     * Genere un gradient colore avec le nom de l'outil (fallback quand capture impossible).
     */
    public static function generateFallbackGradient(Tool $tool): bool
    {
        $slug = $tool->getTranslation('slug', 'fr_CA');
        $name = $tool->getTranslation('name', 'fr_CA');
        if (empty($slug)) {
            return false;
        }

        $outputDir = public_path('screenshots');
        if (! File::isDirectory($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        $path = "{$outputDir}/{$slug}.jpg";
        $w = 1200;
        $h = 630;

        $palettes = [
            [[11, 114, 133], [26, 54, 93]],   // teal → navy
            [[26, 54, 93], [11, 114, 133]],    // navy → teal
            [[142, 68, 173], [44, 62, 80]],    // purple → dark
            [[231, 76, 60], [142, 68, 173]],   // red → purple
            [[46, 204, 113], [22, 160, 133]],  // green → teal
            [[52, 152, 219], [41, 128, 185]],  // blue → darker
            [[243, 156, 18], [211, 84, 0]],    // orange → burnt
            [[44, 62, 80], [52, 73, 94]],      // charcoal → slate
        ];

        $idx = abs(crc32($slug)) % count($palettes);
        [$c1, $c2] = $palettes[$idx];

        $img = imagecreatetruecolor($w, $h);

        // Gradient vertical par bandes (rapide)
        for ($y = 0; $y < $h; $y++) {
            $r = (float) $y / $h;
            $color = imagecolorallocate($img, (int) ($c1[0] + ($c2[0] - $c1[0]) * $r), (int) ($c1[1] + ($c2[1] - $c1[1]) * $r), (int) ($c1[2] + ($c2[2] - $c1[2]) * $r));
            imagefilledrectangle($img, 0, $y, $w, $y, $color);
        }

        $white = imagecolorallocate($img, 255, 255, 255);
        $whiteAlpha = imagecolorallocatealpha($img, 255, 255, 255, 50);

        // Police TTF (Inter SemiBold pour le nom, Regular pour le sous-titre)
        $fontBold = resource_path('fonts/Inter-SemiBold.ttf');
        $fontRegular = resource_path('fonts/Inter-Regular.ttf');
        $hasTtf = file_exists($fontBold) && file_exists($fontRegular);

        if ($hasTtf) {
            // Nom de l'outil en TTF (taille adaptative)
            $fontSize = mb_strlen($name) > 20 ? 36 : (mb_strlen($name) > 12 ? 42 : 52);
            $bbox = imagettfbbox($fontSize, 0, $fontBold, $name);
            $textW = $bbox[2] - $bbox[0];
            $textH = $bbox[1] - $bbox[7];
            $nameX = (int) (($w - $textW) / 2);
            $nameY = (int) (($h + $textH) / 2) - 20;
            imagettftext($img, $fontSize, 0, $nameX, $nameY, $white, $fontBold, $name);

            // Sous-titre dynamique
            $subSize = 18;
            $sub = str_replace(['https://', 'http://'], '', config('app.url'));
            $subBbox = imagettfbbox($subSize, 0, $fontRegular, $sub);
            $subW = $subBbox[2] - $subBbox[0];
            $subX = (int) (($w - $subW) / 2);
            imagettftext($img, $subSize, 0, $subX, $nameY + 40, $whiteAlpha, $fontRegular, $sub);
        } else {
            // Fallback GD bitmap si TTF absent
            $nameLen = strlen($name);
            $scale = 4;
            $charW = imagefontwidth(5);
            $charH = imagefontheight(5);
            $textW = $nameLen * $charW;
            $textImg = imagecreatetruecolor($textW, $charH);
            $bg = imagecolorallocate($textImg, $c1[0], $c1[1], $c1[2]);
            imagefill($textImg, 0, 0, $bg);
            imagestring($textImg, 5, 0, 0, $name, imagecolorallocate($textImg, 255, 255, 255));
            $scaledW = $textW * $scale;
            $scaledH = $charH * $scale;
            $x = (int) (($w - $scaledW) / 2);
            $y = (int) (($h - $scaledH) / 2) - 20;
            imagecopyresized($img, $textImg, max($x, 10), $y, 0, 0, min($scaledW, $w - 20), $scaledH, $textW, $charH);
            imagedestroy($textImg);
            $subLabel = str_replace(['https://', 'http://'], '', config('app.url'));
            imagestring($img, 3, (int) (($w - strlen($subLabel) * imagefontwidth(3)) / 2), $y + $scaledH + 20, $subLabel, $white);
        }

        imagejpeg($img, $path, 90);
        imagedestroy($img);

        $tool->screenshot = "screenshots/{$slug}.jpg";
        $tool->saveQuietly();

        return true;
    }

    public static function isAvailable(): bool
    {
        return file_exists(env('BROWSERSHOT_NODE_PATH', '/usr/local/bin/node'))
            && file_exists(base_path('scripts/capture-screenshot.cjs'));
    }
}
