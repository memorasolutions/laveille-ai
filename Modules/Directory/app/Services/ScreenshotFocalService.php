<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver as ImageGdDriver;
use Intervention\Image\ImageManager;
use Modules\Directory\Models\Tool;
use Throwable;

/**
 * Brique 1 (design doc 2026-08-10) - derive la vignette publique 1200x630 a partir du master
 * complet 1200x1400 conserve pour chaque outil et du point focal vertical choisi par l'admin.
 *
 * @author MEMORA solutions <info@memora.ca>
 */
class ScreenshotFocalService
{
    public const THUMB_WIDTH = 1200;

    public const THUMB_HEIGHT = 630;

    /**
     * Hauteur maximale conservee pour un maitre (plafond anti-page-infinie). Constante partagee
     * avec DirectoryAdminController::deriveMasterFromUpload() - jamais recopiee en dur (DRY).
     */
    public const MAX_MASTER_HEIGHT = 1400;

    /**
     * Derive la vignette 1200x630 du Tool a partir de son master et de son screenshot_focal_y
     * courant (deja borne en amont par le controleur, mais reborne ici par securite - jamais de
     * confiance dans une valeur venant d'ailleurs).
     */
    public function deriveThumbnail(Tool $tool): bool
    {
        $slug = $tool->getTranslation('slug', 'fr_CA');
        if (empty($slug)) {
            return false;
        }

        $masterPath = public_path("screenshots/masters/{$slug}.jpg");
        if (! File::exists($masterPath)) {
            Log::channel('directory_screenshots')->warning("ScreenshotFocalService: master introuvable pour {$slug}");

            return false;
        }

        try {
            $manager = new ImageManager(new ImageGdDriver());
            $master = $manager->read($masterPath);
        } catch (Throwable $e) {
            Log::channel('directory_screenshots')->warning("ScreenshotFocalService: master illisible pour {$slug} - {$e->getMessage()}");

            return false;
        }

        $masterHeight = $master->height();
        $maxFocalY = max(0, $masterHeight - self::THUMB_HEIGHT);
        $focalY = (int) max(0, min((int) ($tool->screenshot_focal_y ?? 0), $maxFocalY));

        $absolutePath = public_path("screenshots/{$slug}.jpg");

        // ACTION: ecriture de la vignette derivee via la methode centralisee existante
        // MCP: SELF (reutilisation stricte, aucune nouvelle methode d'ecriture - exigence design doc)
        // RAISON: safeWriteScreenshot() est le point d'ecriture atomique unique du module (tmp +
        // move) ; force=true car il s'agit d'une action explicite de l'admin sur son propre outil
        // (CA-5), jamais bloquee par le garde-fou anti-overwrite automatique (S79).
        $written = ScreenshotService::safeWriteScreenshot(
            $absolutePath,
            function (string $tempPath) use ($master, $focalY): bool {
                try {
                    $thumbnail = (clone $master)->crop(self::THUMB_WIDTH, self::THUMB_HEIGHT, 0, $focalY);
                    file_put_contents($tempPath, $thumbnail->toJpeg(85)->toString());

                    return true;
                } catch (Throwable $e) {
                    Log::channel('directory_screenshots')->warning('ScreenshotFocalService: derivation vignette echouee - '.$e->getMessage());

                    return false;
                }
            },
            force: true
        );

        if (! $written) {
            return false;
        }

        $tool->screenshot_focal_y = $focalY;
        $tool->screenshot = "screenshots/{$slug}.jpg";
        $tool->saveQuietly();

        ScreenshotService::purgeCloudflareFile("screenshots/{$slug}.jpg");

        return true;
    }
}
