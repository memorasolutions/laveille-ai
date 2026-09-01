<?php

declare(strict_types=1);

namespace Modules\Directory\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Intervention\Image\Drivers\Gd\Driver as ImageGdDriver;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Monolog\Utils as MonologUtils;
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
 * Correctif #2170 (2026-09-01) - directory:dispatch-margin-recapture mourait de faim memoire en
 * production (Allowed memory size ... exhausted, vendor/intervention/image, apres ~1450 outils
 * sur 2336). Mesure locale (3 harnais independants, jusqu'a 2535 appels reels a memory_limit=128M
 * identique a la prod) : classify() et la boucle complete de la commande NE FUIENT PAS - memoire
 * plate, 0 octet reclame par gc_collect_cycles() a chaque palier. La cause reelle, confirmee par
 * reproduction directe (meme signature d'erreur, meme famille de fichier vendor/.../Gd/) : cette
 * methode decode la source ENTIEREMENT en memoire (largeur x hauteur x 4 octets, independant du
 * poids en octets sur disque - un JPEG tres compresse peut peser quelques centaines de Ko et
 * decoder a plus de 150 Mo) AVANT tout redimensionnement. Ce n'est pas une fuite qui grossit avec
 * le nombre d'outils traites - c'est un PIC PAR OUTIL sans plafond, qui fait mourir tout le
 * PROCESSUS (fatal PHP non rattrapable par un try/catch) des qu'une seule source aux dimensions
 * demesurees se presente, peu importe combien d'outils sains ont deja ete traites avant elle.
 * fitsInMemoryBudget() borne desormais ce pic AVANT le decodage complet, via un simple appel a
 * getimagesize() (qui ne lit que l'entete, jamais les pixels).
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
     * Source lisible et saine, mais dont le decodage complet en memoire (largeur x hauteur x 4
     * octets) depasserait la marge memoire PHP disponible - correctif #2170. Traitee comme
     * STATUS_TOO_SMALL par les trois appelants existants (dispatch-margin-recapture,
     * backfill-screenshot-masters, et l'upload admin via son fallback generique deja en place) :
     * aucun master local possible, mais une recapture/un nouvel upload a la bonne taille
     * resoudrait le cas proprement - jamais comme STATUS_ERROR, qui suggere a tort un fichier
     * corrompu.
     */
    public const STATUS_TOO_LARGE = 'too_large';

    /**
     * Facteur de securite applique a la taille brute (largeur x hauteur x 4 octets) d'une source
     * pour estimer le pic memoire transitoire d'un decodage GD + redimensionnement Intervention :
     * le buffer source ET la copie redimensionnee (scale(), puis crop() eventuel) coexistent
     * brievement en memoire, plus une marge pour une eventuelle conversion de palette/orientation
     * EXIF. Valeur choisie genereusement au-dessus de x1 (jamais x1 exact - un pic mesure a x1
     * ferait passer des sources parfaitement raisonnables pour "trop grandes"). Cette marge x2
     * EST le garde-fou - volontairement PAS de second rabais (ex. une fraction du memory_limit
     * total) par-dessus : mesure locale (2026-09-01), le bootstrap Laravel a lui seul consomme
     * deja 67 a 90 Mo sur 128 Mo - un second rabais aurait rejete a tort des sources parfaitement
     * raisonnables (ex. une vignette "retina" 2400x1260 deja presente dans le catalogue reel).
     */
    private const DECODE_MEMORY_SAFETY_FACTOR = 2.0;

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
        // ACTION: garde-fou memoire AVANT tout decodage complet - correctif #2170.
        // RAISON: getimagesize() ne lit que l'entete du fichier (quelques octets), jamais les
        // pixels - un moyen quasi gratuit de connaitre les dimensions REELLES avant de demander a
        // GD d'allouer un buffer de largeur x hauteur x 4 octets. Sans ce garde-fou, une source
        // aux dimensions demesurees (independant de son poids en octets sur disque) fait mourir
        // tout le PROCESSUS par un fatal PHP non rattrapable (confirme par reproduction locale
        // 2026-09-01, meme signature d'erreur qu'en production) - jamais seulement cet appel.
        $dimensions = @getimagesize($sourcePath);
        if (! is_array($dimensions) || $dimensions[0] <= 0 || $dimensions[1] <= 0) {
            Log::channel('directory_screenshots')->warning("ScreenshotMasterDerivationService: dimensions illisibles ({$sourcePath})");

            return ['status' => self::STATUS_ERROR, 'scaled' => null, 'scaledHeight' => null];
        }

        if (! $this->fitsInMemoryBudget((int) $dimensions[0], (int) $dimensions[1])) {
            Log::channel('directory_screenshots')->warning(sprintf(
                'ScreenshotMasterDerivationService: source trop grande pour la marge memoire disponible (%s, %dx%d px) - jamais decodee entierement, traitee comme trop petite (recapture reseau).',
                $sourcePath,
                $dimensions[0],
                $dimensions[1]
            ));

            return ['status' => self::STATUS_TOO_LARGE, 'scaled' => null, 'scaledHeight' => null];
        }

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
     * Estime le pic memoire transitoire d'un decodage GD + redimensionnement Intervention pour
     * des dimensions donnees, et verifie qu'il tient dans ce qu'il reste de memory_limit APRES
     * l'usage courant du processus (bootstrap Laravel deja charge inclus). Retourne toujours true
     * si memory_limit est illimite (-1, cas de certains environnements CLI/CI) - rien a garder
     * dans ce cas, le garde-fou n'a pas de raison d'etre.
     */
    private function fitsInMemoryBudget(int $width, int $height): bool
    {
        $limitBytes = MonologUtils::expandIniShorthandBytes((string) ini_get('memory_limit'));
        if ($limitBytes === false || $limitBytes < 0) {
            return true;
        }

        $estimatedPeakBytes = $width * $height * 4 * self::DECODE_MEMORY_SAFETY_FACTOR;
        $availableBytes = $limitBytes - memory_get_usage(true);

        return $estimatedPeakBytes <= $availableBytes;
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
