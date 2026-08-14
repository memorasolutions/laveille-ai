<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

namespace Modules\Directory\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\ScreenshotMasterDerivationService;

/**
 * Correctif 2026-08-14 (recadrage des vignettes livre le 2026-08-10 mais inutilisable : aucun des
 * ~2194 outils du catalogue ne possede de master, puisque seule une recapture posterieure a cette
 * date en produit un). Derive un master pour chaque outil publie qui a DEJA une vignette mais pas
 * encore de master - a partir de cette vignette existante, jamais d'une nouvelle capture reseau.
 *
 * Reutilise integralement ScreenshotMasterDerivationService (meme regle scale-puis-teste que
 * l'upload manuel admin et que le client) - aucune logique de seuil dupliquee ici.
 *
 * Garde-fous (zero casse) :
 * - n'ecrit QUE des fichiers manquants (screenshots/masters/{slug}.jpg absent) - un master deja
 *   present n'est jamais touche, jamais recalcule ;
 * - ne modifie AUCUNE ligne de la table directory_tools (ni screenshot, ni screenshot_focal_y,
 *   ni screenshot_master_stale) - un master fraichement derive part avec un focal implicite a 0,
 *   deja le defaut de la colonne, rien a ecrire ;
 * - ne supprime jamais rien ;
 * - --dry-run classe et rapporte sans ecrire un seul octet (ScreenshotMasterDerivationService::classify()
 *   seul, jamais deriveFromSourcePath()) ;
 * - --limit borne le nombre d'outils REELLEMENT traites (crees/rejetes) dans cette execution -
 *   idempotent par construction (un master deja cree n'est plus jamais recompte au run suivant),
 *   donc relancer la commande REPREND naturellement la ou elle s'est arretee, sans etat a
 *   persister ;
 * - journalise un resume apres chaque lot de lecture (--chunk).
 */
class BackfillScreenshotMastersCommand extends Command
{
    protected $signature = 'directory:backfill-screenshot-masters
        {--dry-run : Simulation - classe et rapporte sans rien ecrire sur disque}
        {--limit=200 : Nombre maximum d\'outils REELLEMENT traites (crees ou rejetes) dans cette execution}
        {--chunk=25 : Taille des sous-lots de lecture en base (memoire)}';

    protected $description = "Derive un master de point focal (screenshots/masters/{slug}.jpg) a partir de la vignette existante, pour chaque outil publie qui n'en a pas encore. N'ecrase et ne supprime jamais rien.";

    public function handle(ScreenshotMasterDerivationService $derivation): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $limit = max(1, (int) $this->option('limit'));
        $chunkSize = max(1, (int) $this->option('chunk'));

        $this->info($dryRun
            ? "[SIMULATION] Backfill des masters de vignettes - limite {$limit}, lots de {$chunkSize}."
            : "Backfill des masters de vignettes - limite {$limit}, lots de {$chunkSize}.");

        $processed = 0; // compte seulement created + too_small + error (les seuls cas ou une decision a ete prise)
        $created = [];
        $tooSmall = [];
        $error = [];
        $alreadyHasMaster = 0;
        $noLocalVignette = 0;
        $scanned = 0;

        Tool::query()
            ->where('status', 'published')
            ->whereNotNull('screenshot')
            ->where('screenshot', '!=', '')
            ->chunkById($chunkSize, function ($tools) use (
                &$processed, &$created, &$tooSmall, &$error, &$alreadyHasMaster, &$noLocalVignette, &$scanned,
                $derivation, $dryRun, $limit
            ): bool {
                foreach ($tools as $tool) {
                    if ($processed >= $limit) {
                        return false; // arrête le chunkById - limite atteinte pour cette exécution
                    }

                    $scanned++;

                    $slug = (string) ($tool->getTranslation('slug', 'fr_CA', false)
                        ?: $tool->getTranslation('slug', 'fr', false)
                        ?: '');

                    if ($slug === '') {
                        $noLocalVignette++;

                        continue;
                    }

                    $masterPath = public_path("screenshots/masters/{$slug}.jpg");
                    if (File::exists($masterPath)) {
                        // Master deja present : jamais recalcule, jamais recompte. C'est ce qui
                        // rend la commande idempotente et donc "reprenable" sans etat persiste.
                        $alreadyHasMaster++;

                        continue;
                    }

                    $sourcePath = public_path("screenshots/{$slug}.jpg");
                    if (! File::exists($sourcePath) || File::size($sourcePath) < 1000) {
                        // Pas de vignette locale exploitable (URL externe historique, fichier
                        // manquant/corrompu à la racine) - hors périmètre de ce backfill.
                        $noLocalVignette++;

                        continue;
                    }

                    $processed++;

                    if ($dryRun) {
                        // ACTION: classification SEULE - classify() ne touche jamais le disque.
                        // MCP: SELF (< 5 lignes, delegue au service)
                        // RAISON: mode simulation, aucun octet ecrit.
                        $result = $derivation->classify($sourcePath);
                        $status = $result['status'];
                    } else {
                        $status = $derivation->deriveFromSourcePath($sourcePath, $slug);
                    }

                    $entry = ['id' => $tool->id, 'slug' => $slug];

                    match ($status) {
                        ScreenshotMasterDerivationService::STATUS_CREATED => $created[] = $entry,
                        ScreenshotMasterDerivationService::STATUS_TOO_SMALL => $tooSmall[] = $entry,
                        default => $error[] = $entry,
                    };
                }

                $this->info(sprintf(
                    '[lot] %d outils examinés jusqu\'ici — créés %d, trop petits %d, erreurs %d, déjà pourvus %d, sans vignette locale %d.',
                    $scanned,
                    count($created),
                    count($tooSmall),
                    count($error),
                    $alreadyHasMaster,
                    $noLocalVignette
                ));
                \Illuminate\Support\Facades\Log::info('directory:backfill-screenshot-masters [lot]', [
                    'dry_run' => $dryRun,
                    'scanned' => $scanned,
                    'created' => count($created),
                    'too_small' => count($tooSmall),
                    'error' => count($error),
                    'already_has_master' => $alreadyHasMaster,
                    'no_local_vignette' => $noLocalVignette,
                ]);

                return $processed < $limit;
            });

        $this->newLine();
        $this->info(sprintf(
            '%s%d outil(s) examiné(s) au total, %d traité(s) cette exécution : %d %s, %d trop petit(s) (vignette < %dpx une fois mise à %dpx de large), %d en erreur.',
            $dryRun ? '[SIMULATION] ' : '',
            $scanned,
            $processed,
            count($created),
            $dryRun ? 'seraient créé(s)' : 'créé(s)',
            count($tooSmall),
            \Modules\Directory\Services\ScreenshotFocalService::THUMB_HEIGHT,
            \Modules\Directory\Services\ScreenshotFocalService::THUMB_WIDTH,
            count($error)
        ));
        $this->info("{$alreadyHasMaster} outil(s) avaient déjà un master (non touchés), {$noLocalVignette} sans vignette locale exploitable (hors périmètre).");

        if ($tooSmall !== []) {
            $this->warn(sprintf('Vignette trop petite pour produire un master exploitable (%d) :', count($tooSmall)));
            foreach ($tooSmall as $t) {
                $this->line("  - [{$t['id']}] {$t['slug']}");
            }
        }

        if ($error !== []) {
            $this->error(sprintf('Erreur de lecture/écriture (%d) :', count($error)));
            foreach ($error as $e) {
                $this->line("  - [{$e['id']}] {$e['slug']}");
            }
        }

        if (! $dryRun && $processed >= $limit) {
            $this->comment("Limite de {$limit} atteinte - relancer la commande pour poursuivre (idempotent, reprend automatiquement là où elle s'est arrêtée).");
        }

        \Illuminate\Support\Facades\Log::info('directory:backfill-screenshot-masters [résumé final]', [
            'dry_run' => $dryRun,
            'scanned_total' => $scanned,
            'processed_this_run' => $processed,
            'created' => count($created),
            'too_small' => count($tooSmall),
            'error' => count($error),
            'already_has_master' => $alreadyHasMaster,
            'no_local_vignette' => $noLocalVignette,
        ]);

        return self::SUCCESS;
    }
}
