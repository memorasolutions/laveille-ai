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
 *
 * Correctif #2170 (2026-09-01) - la commande mourait de faim memoire en production avant d'avoir
 * fini (~1450 outils sur 2336). Mesure locale (3 harnais independants, jusqu'a 2535 appels reels
 * a memory_limit=128M identique a la prod) : ni classify() ni la boucle complete de cette
 * commande ne fuient - memoire plate. La cause reelle, confirmee par reproduction directe :
 * ScreenshotMasterDerivationService::classify() decodait la source ENTIEREMENT en memoire avant
 * tout redimensionnement, sans plafond - un PIC PAR OUTIL (jamais une accumulation) qui fait
 * mourir tout le PROCESSUS des qu'une seule source aux dimensions demesurees se presente. Voir le
 * garde-fou (fitsInMemoryBudget()) et son detail dans ScreenshotMasterDerivationService.
 *
 * Correctif #2173 (2026-09-01) - le garde-fou #2170 ci-dessus borne le pic memoire PAR IMAGE, mais
 * jamais l'USURE cumulative d'un seul processus PHP qui enchaine des centaines d'outils sans
 * jamais redemarrer : en production, le processus mourait vers l'outil 1400 sur environ
 * 2219-2336, alors que STATUS_TOO_LARGE (ci-dessus) comptait 0 occurrence sur ces 1400 - la cause
 * n'est PAS un cas particulier mais l'entame progressive du budget memoire du PROCESSUS lui-meme.
 * Deux hypotheses sur la cause exacte de cette commande se sont deja revelees fausses le meme
 * jour : le correctif retenu ne suppose donc RIEN sur cette cause. Traitement par LOTS avec
 * REDEMARRAGE DE PROCESSUS. Les outils sont toujours EXAMINES en base par sous-lots memoire
 * (chunkById, --chunk, mecanisme inchange) ; des que --restart-every outils ont ete EXAMINES dans
 * le processus courant (250 par defaut, tres en-deca du seuil de 1400 observe en production), ce
 * processus relance un PROCESSUS FRERE tout neuf (Illuminate\Support\Facades\Process) a partir du
 * curseur --after-id (id du DERNIER outil REELLEMENT EXAMINE, jamais du dernier lot complet -
 * precision a l'outil pres, jamais de saut ni de retraitement a la frontiere), puis se termine. La
 * consommation memoire d'un seul processus ne depend donc plus jamais de la taille du catalogue,
 * seulement de --restart-every. --dry-run traverse fidelement chaque relance (jamais une
 * simulation qui degenere en execution reelle en aval) ; --limit (reseau) reste une limite GLOBALE
 * a toute la chaine, jamais reinitialisee a chaque segment - et une limite reseau atteinte arrete
 * la chaine entiere SANS relancer (comportement #2087 inchange, l'operateur relance alors
 * manuellement comme avant). Voir DispatchMarginRecaptureCommandTest pour la preuve qu'aucun outil
 * n'est jamais saute ni traite deux fois a la frontiere d'un segment.
 */

namespace Modules\Directory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Modules\Directory\Jobs\CaptureScreenshotJob;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotFocalService;
use Modules\Directory\Services\ScreenshotMasterDerivationService;

class DispatchMarginRecaptureCommand extends Command
{
    protected $signature = 'directory:dispatch-margin-recapture
        {--dry-run : Simulation - classe et rapporte sans rien ecrire ni mettre en file}
        {--limit=0 : Nombre maximum de RECAPTURES RESEAU mises en file sur TOUTE la chaine de relances (0 = illimite ; les derivations locales gratuites ne sont jamais bornees par cette limite)}
        {--chunk=50 : Taille des sous-lots de lecture en base (memoire)}
        {--restart-every=250 : Nombre d\'outils EXAMINES avant de relancer un processus frais - protection usure memoire #2173 (0 = jamais relancer, comportement pre-#2173, deconseille sur le catalogue complet)}
        {--after-id=0 : Curseur de reprise - ne considere que les outils dont l\'id est strictement superieur (aliment automatiquement par les relances ; peut aussi reprendre manuellement une execution interrompue)}';

    protected $description = "Identifie les outils publies sans marge de recadrage (master absent ou <= 630px), derive un master local quand la vignette existante le permet deja, et met en file une recapture reseau (queue 'screenshots') pour le reste.";

    public function handle(ScreenshotMasterDerivationService $derivation): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(0, (int) $this->option('limit'));
        $chunkSize = max(1, (int) $this->option('chunk'));
        $restartEvery = max(0, (int) $this->option('restart-every'));
        $afterId = max(0, (int) $this->option('after-id'));

        $segmentLabel = $restartEvery > 0
            ? "segment de {$restartEvery} outil(s) (protection usure memoire #2173)"
            : 'jamais de relance automatique (#2173 desactive via --restart-every=0)';
        $this->info(sprintf(
            '%sDispatch recapture marge - limite reseau %d (0=illimite), lots de %d, %s%s.',
            $dryRun ? '[SIMULATION] ' : '',
            $limit,
            $chunkSize,
            $segmentLabel,
            $afterId > 0 ? " - reprise apres l'id {$afterId}" : ''
        ));

        $scanned = 0;
        $alreadyMargin = 0;
        $masterDerivedLocally = 0;
        $queuedForRecapture = 0;
        $lockedSkipped = 0;
        $unreadable = 0;
        $noLocalVignette = 0;
        $limitReached = false;
        $restartCapReached = false;
        $lastId = $afterId;

        $query = $this->baseQuery();
        if ($afterId > 0) {
            $query->where('id', '>', $afterId);
        }

        $query->chunkById($chunkSize, function ($tools) use (
                &$scanned, &$alreadyMargin, &$masterDerivedLocally, &$queuedForRecapture,
                &$lockedSkipped, &$unreadable, &$noLocalVignette, &$limitReached,
                &$restartCapReached, &$lastId,
                $derivation, $dryRun, $limit, $restartEvery
            ): bool {
                foreach ($tools as $tool) {
                    if ($restartEvery > 0 && $scanned >= $restartEvery) {
                        // Segment plein (protection usure memoire #2173) - $tool n'est PAS encore
                        // examine ici : $lastId pointe donc exactement sur le dernier outil
                        // REELLEMENT traite, jamais sur un lot entier. La reprise du processus
                        // frere (id > $lastId) ne saute ni ne retraite jamais rien.
                        $restartCapReached = true;

                        return false; // arrete le chunkById - une relance est evaluee par l'appelant
                    }

                    $scanned++;
                    $lastId = (int) $tool->id;

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
                        // STATUS_TOO_SMALL et STATUS_TOO_LARGE (correctif #2170 - source saine
                        // mais dont le decodage complet en memoire depasserait la marge PHP
                        // disponible, jamais tentee) : tombent toutes deux dans la branche
                        // recapture reseau ci-dessous - dans les deux cas aucun master local
                        // n'est possible, mais une nouvelle capture a la bonne taille resout le
                        // cas proprement.
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
            '%s%d outil(s) examiné(s) dans ce segment : %d avaient déjà une marge exploitable, %d master(s) dérivé(s) localement (gratuit, sans réseau), %d recapture(s) réseau %s (queue "screenshots"), %d verrouillé(s) ignoré(s), %d illisible(s), %d sans vignette locale.',
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

        // Verite terrain apres coup (existence en base), jamais deduite des seuls drapeaux
        // d'arret ci-dessus - correcte quelle que soit la raison de l'arret (segment plein,
        // limite reseau, ou catalogue reellement epuise). $restartCapReached implique deja
        // $hasMoreWork par construction (le tool qui a declenche le plafond existe forcement
        // encore), mais revalider ici rend la decision de relance evidente sans avoir a
        // re-demontrer cette implication a chaque lecture du code.
        $hasMoreWork = $this->baseQuery()->where('id', '>', $lastId)->exists();
        $shouldRelaunch = $restartCapReached && ! $limitReached && $hasMoreWork;

        if ($limitReached) {
            $this->comment("Limite réseau de {$limit} atteinte - relancer la commande pour poursuivre (idempotent, reprend automatiquement là où elle s'est arrêtée).");
        } elseif ($shouldRelaunch) {
            $this->comment("Segment de {$restartEvery} outil(s) complet - relance immédiate d'un processus frais à partir de l'id {$lastId} (protection usure mémoire #2173).");
        }

        Log::channel('directory_screenshots')->info('directory:dispatch-margin-recapture [résumé segment]', [
            'dry_run' => $dryRun,
            'limit' => $limit,
            'after_id' => $afterId,
            'restart_every' => $restartEvery,
            'scanned' => $scanned,
            'already_margin' => $alreadyMargin,
            'master_derived_locally' => $masterDerivedLocally,
            'queued_for_recapture' => $queuedForRecapture,
            'locked_skipped' => $lockedSkipped,
            'unreadable' => $unreadable,
            'no_local_vignette' => $noLocalVignette,
            'limit_reached' => $limitReached,
            'last_id' => $lastId,
            'has_more_work' => $hasMoreWork,
            'will_relaunch' => $shouldRelaunch,
        ]);

        if ($shouldRelaunch) {
            return $this->relaunch($lastId, $limit, $queuedForRecapture, $dryRun, $chunkSize, $restartEvery);
        }

        return self::SUCCESS;
    }

    /**
     * Requete de base commune a chaque segment ET a la verification hasMoreWork - seule
     * occurrence de ces trois clauses where dans ce fichier (DRY, jamais recopiees).
     */
    private function baseQuery(): Builder
    {
        return Tool::published()
            ->whereNotNull('screenshot')
            ->where('screenshot', '!=', '');
    }

    /**
     * Relance un PROCESSUS PHP FRERE, totalement neuf, a partir du curseur donne - protection
     * usure memoire #2173. Bloquant (Process::run()) mais jamais imbrique a plus d'un niveau a la
     * fois : ce processus-ci n'a plus rien d'autre a faire qu'attendre celui-ci puis propager son
     * code de sortie, il ne retraite plus aucun outil lui-meme. --dry-run et --chunk/--restart-every
     * traversent la relance a l'identique ; --limit (reseau) est diminue de ce qui a deja ete mis
     * en file dans TOUTE LA CHAINE jusqu'ici, pour rester une limite globale a la chaine plutot
     * que de se reinitialiser a chaque segment (jamais atteint ici puisque $limitReached aurait
     * deja arrete la chaine avant d'appeler cette methode - voir le garde $shouldRelaunch).
     */
    private function relaunch(int $afterId, int $limit, int $alreadyQueuedThisChain, bool $dryRun, int $chunkSize, int $restartEvery): int
    {
        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'directory:dispatch-margin-recapture',
            '--after-id='.$afterId,
            '--chunk='.$chunkSize,
            '--restart-every='.$restartEvery,
        ];

        if ($dryRun) {
            $command[] = '--dry-run';
        }

        if ($limit > 0) {
            $command[] = '--limit='.max(0, $limit - $alreadyQueuedThisChain);
        }

        $this->info("[relance] processus frais à partir de l'id {$afterId}...");

        $result = Process::path(base_path())
            ->forever()
            ->run($command, function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });

        return $result->exitCode() ?? self::FAILURE;
    }
}
