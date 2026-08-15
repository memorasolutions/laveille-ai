<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver as ImageGdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Throwable;

/**
 * Brique 1 (design doc 2026-08-10) - derive un master de point focal (1200 de large, hauteur
 * variable jusqu'a ScreenshotFocalService::MAX_MASTER_HEIGHT) a partir d'une image source
 * quelconque (upload manuel admin OU vignette deja publiee sur disque).
 *
 * Extraite le 2026-08-14 de DirectoryAdminController::deriveMasterFromUpload() (ou elle vivait
 * enfouie et servait un seul appelant) pour etre reutilisee SANS duplication par un second
 * appelant : la commande de backfill qui derive un master pour les outils publies avant la
 * livraison de la brique 1 (aucun master ne pouvait exister avant cette date). Meme regle
 * scale-puis-teste que le client (screenshot-capture.blade.php mode cadrage) : la largeur est
 * ramenee a THUMB_WIDTH EN PREMIER, puis la hauteur RESULTANTE est comparee au minimum - jamais
 * la hauteur brute de la source.
 *
 * @author MEMORA solutions <info@memora.ca>
 */
class ScreenshotMasterDerivationService
{
    /**
     * Un master exploitable a ete produit (ou le serait, en mode classification seule).
     */
    public const STATUS_CREATED = 'created';

    /**
     * La source, une fois mise a l'echelle a THUMB_WIDTH, tombe sous THUMB_HEIGHT - aucun master
     * exploitable n'en resulterait. Jamais traite en silence par l'appelant (comptabilise et liste).
     */
    public const STATUS_TOO_SMALL = 'too_small';

    /**
     * Source illisible, corrompue, ou echec d'ecriture du fichier final.
     */
    public const STATUS_ERROR = 'error';

    /**
     * Lit la source et determine si un master exploitable en resulterait, SANS RIEN ECRIRE sur
     * disque. Reutilisee par deriveFromSourcePath() (ecriture reelle) et par le mode simulation
     * de la commande de backfill - la regle de seuil (scale puis compare) n'existe qu'ICI, jamais
     * recalculee en double ailleurs.
     *
     * @return array{status: string, scaled: ?ImageInterface, scaledHeight: ?int}
     */
    public function classify(string $sourcePath): array
    {
        try {
            $manager = new ImageManager(new ImageGdDriver());
            $source = $manager->read($sourcePath);
            $scaled = $source->scale(width: ScreenshotFocalService::THUMB_WIDTH);

            if ($scaled->height() <= ScreenshotFocalService::THUMB_HEIGHT) {
                return ['status' => self::STATUS_TOO_SMALL, 'scaled' => null, 'scaledHeight' => $scaled->height()];
            }

            if ($scaled->height() > ScreenshotFocalService::MAX_MASTER_HEIGHT) {
                $scaled = $scaled->crop(ScreenshotFocalService::THUMB_WIDTH, ScreenshotFocalService::MAX_MASTER_HEIGHT, 0, 0);
            }

            return ['status' => self::STATUS_CREATED, 'scaled' => $scaled, 'scaledHeight' => $scaled->height()];
        } catch (Throwable $e) {
            Log::channel('directory_screenshots')->warning("ScreenshotMasterDerivationService: source illisible ({$sourcePath}) - {$e->getMessage()}");

            return ['status' => self::STATUS_ERROR, 'scaled' => null, 'scaledHeight' => null];
        }
    }

    /**
     * Classifie puis ECRIT reellement le master a public/screenshots/masters/{slug}.jpg quand
     * exploitable. N'ecrit JAMAIS quand le resultat n'est pas STATUS_CREATED - l'appelant decide
     * seul de ce qu'il fait d'un master existant (jamais ecrase ici, cette methode ne fait que
     * creer un fichier manquant a l'emplacement cible).
     */
    public function deriveFromSourcePath(string $sourcePath, string $slug): string
    {
        $result = $this->classify($sourcePath);
        if ($result['status'] !== self::STATUS_CREATED) {
            return $result['status'];
        }

        $mastersDir = public_path('screenshots/masters');
        if (! File::isDirectory($mastersDir)) {
            File::makeDirectory($mastersDir, 0755, true);
        }

        $masterPath = "{$mastersDir}/{$slug}.jpg";

        try {
            file_put_contents($masterPath, $result['scaled']->toJpeg(85)->toString());

            return self::STATUS_CREATED;
        } catch (Throwable $e) {
            Log::channel('directory_screenshots')->warning("ScreenshotMasterDerivationService: ecriture master echouee pour {$slug} - {$e->getMessage()}");

            return self::STATUS_ERROR;
        }
    }
}
