<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Correctif #2087 (2026-08-31) - des fiches d'annuaire dont la vignette n'offre aucune marge de
 * recadrage : le point focal (ScreenshotFocalService::deriveThumbnail) ne peut bouger que si le
 * master associe depasse THUMB_HEIGHT (630px) de hauteur. Sans marge, aucun deplacement n'est
 * possible - qu'un master existe deja et soit trop court, ou qu'il n'existe pas du tout.
 *
 * Deux chemins de correction, tous deux REUTILISES sans la moindre duplication de la regle de
 * seuil (scale a THUMB_WIDTH puis compare a THUMB_HEIGHT, qui n'existe que dans
 * ScreenshotMasterDerivationService::classify()) :
 * - gratuit et sans risque : si aucun master n'existe mais que la vignette DEJA en place suffirait
 *   (meme regle que directory:backfill-screenshot-masters), le master est derive localement, tout
 *   de suite, sans le moindre appel reseau ;
 * - seul chemin restant quand la vignette existante est structurellement trop courte (ou le
 *   master existant l'est) : une RECAPTURE reseau, mise en file sur la queue 'screenshots' via
 *   CaptureScreenshotJob - JAMAIS executee en synchrone ici. La file et ses deux workers
 *   (planificateur Laravel + cron cPanel, voir config/queue.php et
 *   Modules/Directory/tests/Feature/QueueRetryAfterCoherenceTest.php) sont deja calibres a un
 *   --timeout de 330s pour un pire cas mesure a 276s - cette commande ne fait qu'ALIMENTER cette
 *   file deja sure, jamais attendre ou executer la capture elle-meme.
 *
 * Idempotente par construction : un outil qui gagne sa marge (recapture reussie ou derivation
 * locale) n'est plus jamais recompte au run suivant, donc relancer la commande REPREND
 * naturellement la ou elle s'est arretee - meme logique de reprise que le backfill du 2026-08-14.
 */

namespace Modules\Directory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Jobs\CaptureScreenshotJob;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotFocalService;
use Modules\Directory\Services\ScreenshotMasterDerivationService;

class DispatchMarginRecaptureCommand extends Command
{
    protected $signature = 'directory:dispatch-margin-recapture
        {--dry-run : Simulation - classe et rapporte sans rien ecrire ni mettre en file}
        {--limit=0 : Nombre maximum de RECAPTURES RESEAU mises en file cette execution (0 = illimite ; les derivations locales gratuites ne sont jamais bornees par cette limite)}
        {--chunk=50 : Taille des sous-lots de lecture en base (memoire)}';

    protected $description = "Identifie les outils publies sans marge de recadrage (master absent ou <= 630px), derive un master local quand la vignette existante le permet deja, et met en file une recapture reseau (queue 'screenshots') pour le reste.";

    public function handle(ScreenshotMasterDerivationService $derivation): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $chunkSize = max(1, (int) $this->option('chunk'));

        $this->info($dryRun
            ? "[SIMULATION] Dispatch recapture marge - limite reseau {$limit} (0=illimite), lots de {$chunkSize}."
            : "Dispatch recapture marge - limite reseau {$limit} (0=illimite), lots de {$chunkSize}.");

        $scanned = 0;
        $alreadyMargin = 0;
        $masterDerivedLocally = 0;
        $queuedForRecapture = 0;
        $lockedSkipped = 0;
        $unreadable = 0;
        $noLocalVignette = 0;
        $limitReached = false;

        Tool::published()
            ->whereNotNull('screenshot')
            ->where('screenshot', '!=', '')
            ->chunkById($chunkSize, function ($tools) use (
                &$scanned, &$alreadyMargin, &$masterDerivedLocally, &$queuedForRecapture,
                &$lockedSkipped, &$unreadable, &$noLocalVignette, &$limitReached,
                $derivation, $dryRun, $limit
            ): bool {
                foreach ($tools as $tool) {
                    $scanned++;

                    $slug = (string) ($tool->getTranslation('slug', 'fr_CA', false)
                        ?: $tool->getTranslation('slug', 'fr', false)
                        ?: '');

                    if ($slug === '') {
                        $noLocalVignette++;

                        continue;
                    }

                    $sourcePath = public_path("screenshots/{$slug}.jpg");
                    if (! File::exists($sourcePath) || File::size($sourcePath) < 1000) {
                        // Pas de vignette locale exploitable - hors perimetre (meme regle que le
                        // backfill : rien a recadrer si rien n'existe deja localement).
                        $noLocalVignette++;

                        continue;
                    }

                    $masterPath = public_path("screenshots/masters/{$slug}.jpg");

                    if (File::exists($masterPath)) {
                        // Un master derive de CETTE MEME vignette existe deja : le rederiver ne
                        // changerait rien (classify() donnerait le meme resultat). Seule une
                        // recapture reseau peut encore apporter une marge.
                        $dimensions = @getimagesize($masterPath);
                        $masterHeight = is_array($dimensions) ? (int) $dimensions[1] : 0;

                        if ($masterHeight > ScreenshotFocalService::THUMB_HEIGHT) {
                            $alreadyMargin++;

                            continue;
                        }
                    } else {
                        // Aucun master : verifier si la vignette DEJA en place suffirait a en
                        // deriver un avec marge - gratuit, sans le moindre appel reseau. Reutilise
                        // integralement ScreenshotMasterDerivationService (meme regle que
                        // directory:backfill-screenshot-masters), aucun seuil recopie ici.
                        $status = $dryRun
                            ? $derivation->classify($sourcePath)['status']
                            : $derivation->deriveFromSourcePath($sourcePath, $slug);

                        if ($status === ScreenshotMasterDerivationService::STATUS_CREATED) {
                            $masterDerivedLocally++;

                            continue;
                        }

                        if ($status === ScreenshotMasterDerivationService::STATUS_ERROR) {
                            // Source illisible/corrompue : une recapture reseau peut la remplacer,
                            // mais ne pas insister ici - compte a part pour investigation manuelle
                            // eventuelle plutot que de gonfler silencieusement la file.
                            $unreadable++;

                            continue;
                        }
                        // STATUS_TOO_SMALL : tombe dans la branche recapture reseau ci-dessous.
                    }

                    // A ce stade : aucune marge possible sans une nouvelle capture reseau.
                    if ($tool->screenshot_locked) {
                        // capture() refuse structurellement de toucher un screenshot verrouille -
                        // le mettre en file serait un no-op garanti. Compte a part, jamais mis en
                        // file (voir ScreenshotService::capture()).
                        $lockedSkipped++;

                        continue;
                    }

                    if ($limit > 0 && $queuedForRecapture >= $limit) {
                        $limitReached = true;

                        return false; // arrete le chunkById - limite reseau atteinte pour cette execution
                    }

                    if (! $dryRun) {
                        CaptureScreenshotJob::dispatch($tool);
                    }
                    $queuedForRecapture++;
                }

                $this->info(sprintf(
                    '[lot] %d outils examinés jusqu\'ici - déjà avec marge %d, master local dérivé %d, recaptures mises en file %d, verrouillés ignorés %d, illisibles %d, sans vignette locale %d.',
                    $scanned,
                    $alreadyMargin,
                    $masterDerivedLocally,
                    $queuedForRecapture,
                    $lockedSkipped,
                    $unreadable,
                    $noLocalVignette
                ));

                return true;
            });

        $this->newLine();
        $this->info(sprintf(
            '%s%d outil(s) examiné(s) au total : %d avaient déjà une marge exploitable, %d master(s) dérivé(s) localement (gratuit, sans réseau), %d recapture(s) réseau %s (queue "screenshots"), %d verrouillé(s) ignoré(s), %d illisible(s), %d sans vignette locale.',
            $dryRun ? '[SIMULATION] ' : '',
            $scanned,
            $alreadyMargin,
            $masterDerivedLocally,
            $queuedForRecapture,
            $dryRun ? 'auraient été mises en file' : 'mise(s) en file',
            $lockedSkipped,
            $unreadable,
            $noLocalVignette
        ));

        if ($limitReached) {
            $this->comment("Limite réseau de {$limit} atteinte - relancer la commande pour poursuivre (idempotent, reprend automatiquement là où elle s'est arrêtée).");
        }

        Log::channel('directory_screenshots')->info('directory:dispatch-margin-recapture [résumé]', [
            'dry_run' => $dryRun,
            'limit' => $limit,
            'scanned' => $scanned,
            'already_margin' => $alreadyMargin,
            'master_derived_locally' => $masterDerivedLocally,
            'queued_for_recapture' => $queuedForRecapture,
            'locked_skipped' => $lockedSkipped,
            'unreadable' => $unreadable,
            'no_local_vignette' => $noLocalVignette,
            'limit_reached' => $limitReached,
        ]);

        return self::SUCCESS;
    }
}
