<?php

declare(strict_types=1);

namespace Modules\News\Console;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION : fenêtre glissante SÛRE des brouillons bruts (design doc SPEC-PRUNE-DRAFTS, approuvé
 *          par le propriétaire le 2026-08-20 : « purge sûre des brouillons seulement ») - la page
 *          /admin/news/composition affiche déjà les 200 fiches les plus récentes ; cette commande
 *          borne le BACKLOG en supprimant le surplus de BROUILLONS BRUTS au-delà des `--keep`
 *          (défaut 200) plus récents par `pub_date`, jamais une fiche à valeur.
 *
 *          Garde-fou ABSOLU (le coeur de la sûreté) : la cible de suppression exige les QUATRE
 *          conditions à la fois - `is_published = false`, `retired_at` nul, `reviewed_at` nul, ET
 *          `hasComposedSummary() === false`. Le critère composé vit dans une colonne JSON : un
 *          DELETE SQL massif ne peut pas le tester fiablement, donc la sélection charge les
 *          candidats par lot (`chunk`) et filtre en PHP via le helper existant du modèle - jamais
 *          de requête SQL brute sur `structured_summary`. Une fiche qui échoue UNE seule de ces
 *          conditions n'entre même pas dans le décompte des « brouillons bruts éligibles » : elle
 *          ne concurrence jamais la fenêtre des `--keep` plus récents.
 *
 *          Hard delete (ce modèle n'a pas de SoftDeletes) mais RÉVERSIBLE : backup JSON complet
 *          (toutes les colonnes brutes, lignes entières) écrit dans
 *          storage/app/news-prune-drafts-backup-{timestamp}.json AVANT toute suppression - même
 *          garde-fou zéro-casse que RetireArticlesCommand::writeBackup() (DRY : même convention de
 *          nom de fichier horodaté America/Toronto, même chemin storage_path('app/...') explicite
 *          plutôt que Storage::disk('local'), dont la racine est storage/app/private en
 *          Laravel 11). `--restore={fichier}` réinsère les lignes exactement telles que
 *          sauvegardées (insertOrIgnore, idempotent).
 *
 * MCP: SELF (<5 lignes utiles par branche)
 * RAISON: design doc du chantier - porte serveur unique, jamais d'écriture directe SQL ailleurs.
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Modules\News\Models\NewsArticle;

class PruneDraftsCommand extends Command
{
    protected $signature = 'news:prune-drafts
        {--dry-run : Compte et liste les ids candidats SANS supprimer ni écrire de backup}
        {--keep=200 : Nombre de brouillons bruts les plus récents (par pub_date) à conserver}
        {--restore= : Chemin (ou nom de fichier dans storage/app) d\'une sauvegarde JSON à restaurer}';

    protected $description = 'Purge sûre des vieux brouillons bruts au-delà des N plus récents (backup JSON réversible avant suppression)';

    /**
     * Nombre de backups conservés lors de la rotation (spec : « garder les 14 derniers »).
     */
    private const BACKUPS_TO_KEEP = 14;

    /**
     * Taille des lots pour la lecture/écriture/suppression - borne mémoire CLI (128 Mo).
     */
    private const CHUNK_SIZE = 500;

    public function handle(): int
    {
        $table = (new NewsArticle())->getTable();
        $restoreFile = (string) ($this->option('restore') ?? '');

        if ($restoreFile !== '') {
            return $this->handleRestore($restoreFile, (bool) $this->option('dry-run'), $table);
        }

        $keep = max(0, (int) $this->option('keep'));
        $isDryRun = (bool) $this->option('dry-run');

        [$eligibleCount, $keptCount, $candidateIds] = $this->collectCandidates($keep);

        if ($isDryRun) {
            $this->table(['Mesure', 'Valeur'], [
                ['Brouillons bruts éligibles', $eligibleCount],
                ["Gardés ({$keep} plus récents)", $keptCount],
                ['Candidats à suppression', count($candidateIds)],
            ]);
            if ($candidateIds !== []) {
                $apercu = array_slice($candidateIds, 0, 50);
                $suffixe = count($candidateIds) > 50 ? '…' : '';
                $this->line('Ids candidats (aperçu) : '.implode(', ', $apercu).$suffixe);
            }
            $this->info('Mode --dry-run : aucune modification effectuée, aucun backup écrit.');

            return self::SUCCESS;
        }

        if ($candidateIds === []) {
            $this->info("Aucun brouillon brut à élaguer ({$eligibleCount} éligible(s), {$keptCount} gardé(s)).");

            return self::SUCCESS;
        }

        $backupPath = $this->writeBackup($candidateIds, $table);
        $this->deleteCandidates($candidateIds, $table);
        $this->rotateBackups();

        $this->info("Élagage des brouillons : {$eligibleCount} éligible(s), {$keptCount} gardé(s), ".count($candidateIds).' supprimé(s).');
        $this->info("Sauvegarde écrite AVANT suppression : {$backupPath}");

        return self::SUCCESS;
    }

    /**
     * ACTION : identifie les brouillons bruts éligibles et sépare les `--keep` plus récents (par
     * pub_date DESC, id DESC pour un ordre déterministe en cas d'égalité) des candidats à
     * suppression. Lecture SEULE (aucune écriture) - `chunk()` classique est sûr ici car aucune
     * ligne du jeu de résultats n'est modifiée pendant l'itération (pas de dérive de pagination),
     * contrairement à `chunkById()` qui imposerait un tri par id incompatible avec le tri par
     * pub_date exigé par la spec.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: le critère composé est en JSON - seul un filtre PHP après hydratation du modèle
     * peut appeler hasComposedSummary() ; jamais un DELETE/WHERE SQL sur cette colonne.
     *
     * @return array{0: int, 1: int, 2: array<int, int>}
     */
    private function collectCandidates(int $keep): array
    {
        $eligibleCount = 0;
        $keptCount = 0;
        $candidateIds = [];

        NewsArticle::query()
            ->where('is_published', false)
            ->whereNull('retired_at')
            ->whereNull('reviewed_at')
            ->orderByDesc('pub_date')
            ->orderByDesc('id')
            ->chunk(self::CHUNK_SIZE, function ($rows) use ($keep, &$eligibleCount, &$keptCount, &$candidateIds) {
                foreach ($rows as $article) {
                    if ($article->hasComposedSummary()) {
                        continue; // composée : jamais un brouillon « brut », intouchable, hors décompte.
                    }
                    $eligibleCount++;
                    if ($keptCount < $keep) {
                        $keptCount++;
                        continue;
                    }
                    $candidateIds[] = $article->id;
                }
            });

        return [$eligibleCount, $keptCount, $candidateIds];
    }

    /**
     * ACTION : exporte l'état COMPLET (toutes les colonnes brutes, telles que stockées en base -
     * aucun cast Eloquent) des lignes candidates, par lot, AVANT toute suppression. Lignes brutes
     * (DB::table plutôt qu'Eloquent) pour un aller-retour fidèle avec --restore : les colonnes
     * JSON (structured_summary, editorial_proof_pairs...) restent la chaîne JSON exacte déjà en
     * base, réinsérée telle quelle.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: backup + historique AVANT toute écriture, non négociable (garde-fou zéro-casse).
     *
     * @param array<int, int> $candidateIds
     */
    private function writeBackup(array $candidateIds, string $table): string
    {
        $backupRows = [];
        foreach (array_chunk($candidateIds, self::CHUNK_SIZE) as $chunkIds) {
            foreach (DB::table($table)->whereIn('id', $chunkIds)->get() as $row) {
                $backupRows[] = (array) $row;
            }
        }

        $filename = 'news-prune-drafts-backup-'.now('America/Toronto')->format('Ymd-His').'.json';
        $fullPath = storage_path('app/'.$filename);
        File::put($fullPath, json_encode($backupRows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $fullPath;
    }

    /**
     * ACTION : supprime (hard delete) les lignes candidates, par lot - appelé UNIQUEMENT après
     * que writeBackup() a fini d'écrire sur disque (séquence garantie par handle()).
     * MCP: SELF (<5 lignes)
     * RAISON: borne mémoire CLI, jamais un DELETE massif sans WHERE ni un seul aller SQL sur des
     * milliers d'ids.
     *
     * @param array<int, int> $candidateIds
     */
    private function deleteCandidates(array $candidateIds, string $table): void
    {
        foreach (array_chunk($candidateIds, self::CHUNK_SIZE) as $chunkIds) {
            DB::table($table)->whereIn('id', $chunkIds)->delete();
        }
    }

    /**
     * ACTION : `--restore={fichier}` - réinsère les lignes de la sauvegarde désignée. `INSERT
     * IGNORE` (idempotent) : une ligne dont l'id existe déjà en base (déjà restaurée, ou jamais
     * supprimée) est silencieusement ignorée plutôt que de faire échouer tout le lot.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: réversibilité explicitement exigée par la spec, même mécanisme --dry-run que le
     * mode purge (prévisualiser avant d'écrire).
     */
    private function handleRestore(string $restoreFile, bool $isDryRun, string $table): int
    {
        $fullPath = str_starts_with($restoreFile, '/') ? $restoreFile : storage_path('app/'.$restoreFile);

        if (! is_file($fullPath)) {
            $this->error("Fichier de sauvegarde introuvable : {$fullPath}");

            return self::FAILURE;
        }

        $decoded = json_decode((string) File::get($fullPath), true);
        if (! is_array($decoded) || $decoded === []) {
            $this->error('Sauvegarde vide ou illisible, rien à restaurer.');

            return self::FAILURE;
        }

        $ids = array_map(static fn (array $row): int => (int) $row['id'], $decoded);
        $existingIds = DB::table($table)->whereIn('id', $ids)->pluck('id')
            ->map(static fn ($v) => (int) $v)->all();
        $toRestore = array_values(array_filter(
            $decoded,
            static fn (array $row): bool => ! in_array((int) $row['id'], $existingIds, true)
        ));
        $dejaPresentes = count($decoded) - count($toRestore);

        if ($isDryRun) {
            $this->info('Mode --dry-run : '.count($toRestore)." fiche(s) sur ".count($decoded).' seraient restaurée(s) ('.$dejaPresentes.' déjà présente(s)). Aucune écriture.');

            return self::SUCCESS;
        }

        $restored = 0;
        foreach (array_chunk($toRestore, self::CHUNK_SIZE) as $chunk) {
            $restored += DB::table($table)->insertOrIgnore($chunk);
        }

        $this->info("Restauré : {$restored} fiche(s) sur ".count($decoded).' dans la sauvegarde ('.$dejaPresentes.' déjà présente(s), ignorée(s)).');

        return self::SUCCESS;
    }

    /**
     * ACTION : rotation simple des backups (spec : « garder les 14 derniers backups, supprimer
     * les plus vieux FICHIERS de backup - PAS les données »). Le format de nom horodaté
     * (Ymd-His) trie lexicographiquement dans l'ordre chronologique - aucun tri par mtime requis.
     * MCP: SELF (<5 lignes)
     * RAISON: le backup quotidien s'accumule indéfiniment sinon (planification quotidienne).
     */
    private function rotateBackups(): void
    {
        $files = glob(storage_path('app/news-prune-drafts-backup-*.json')) ?: [];
        sort($files);

        $excess = count($files) - self::BACKUPS_TO_KEEP;
        if ($excess <= 0) {
            return;
        }

        foreach (array_slice($files, 0, $excess) as $oldFile) {
            File::delete($oldFile);
        }
    }
}
