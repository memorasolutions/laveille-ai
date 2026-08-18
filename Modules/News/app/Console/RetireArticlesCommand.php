<?php

declare(strict_types=1);

namespace Modules\News\Console;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION : porte serveur BORNÉE du chantier AdSense « faible valeur » (2026-08-18) - retire
 *          (ou restaure) un lot de fiches d'actualités désignées par un fichier JSON d'ids
 *          {"ids": [1, 2, 3]}. Retrait SEO-sûr et RÉVERSIBLE : la fiche répond 410 et sort de
 *          l'index/sitemap/listes/widgets/recherche (voir NewsArticle::scopePublished()) mais
 *          n'est JAMAIS supprimée - retirer() se contente de poser retired_at, --restore le
 *          remet à null.
 *
 *          Backup AVANT écriture (garde-fou zéro-casse) : l'état des fiches visées est exporté
 *          dans storage/app/news-retire-backup-{timestamp}.json avant toute mutation (jamais en
 *          --restore ni en --dry-run, ces deux modes n'ont rien de nouveau à sauvegarder).
 *
 * MCP: SELF (<5 lignes utiles par branche)
 * RAISON: design doc du chantier - porte serveur unique, jamais d'écriture directe SQL ailleurs.
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Modules\News\Models\NewsArticle;

class RetireArticlesCommand extends Command
{
    protected $signature = 'news:retire
        {--ids-file= : Chemin du fichier JSON {"ids":[...]} désignant les fiches visées}
        {--restore : Restaure (retired_at = null) au lieu de retirer}
        {--dry-run : Affiche seulement le compte, n\'écrit rien}';

    protected $description = 'Retire (410, réversible) ou restaure un lot de fiches d\'actualités désignées par un fichier JSON d\'ids';

    public function handle(): int
    {
        $idsFile = (string) $this->option('ids-file');
        if ($idsFile === '') {
            $this->error('L\'option --ids-file est obligatoire (chemin d\'un fichier JSON {"ids":[...]}).');

            return self::FAILURE;
        }

        if (! is_file($idsFile)) {
            $this->error("Fichier introuvable : {$idsFile}");

            return self::FAILURE;
        }

        $decoded = json_decode((string) file_get_contents($idsFile), true);
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($decoded['ids'] ?? [])))));

        if ($ids === []) {
            $this->error('Aucun id valide trouvé dans le fichier JSON (clé "ids" attendue, tableau d\'entiers).');

            return self::FAILURE;
        }

        $isRestore = (bool) $this->option('restore');
        $isDryRun = (bool) $this->option('dry-run');

        $articles = NewsArticle::whereIn('id', $ids)->get();
        $missing = array_diff($ids, $articles->pluck('id')->all());
        if ($missing !== []) {
            $this->warn('Ids introuvables (ignorés) : '.implode(', ', $missing));
        }

        if ($isDryRun) {
            $verbe = $isRestore ? 'restaurée(s)' : 'retirée(s)';
            $this->info("Mode --dry-run : {$articles->count()} fiche(s) seraient {$verbe}. Aucune modification effectuée.");

            return self::SUCCESS;
        }

        $backupPath = null;
        if (! $isRestore) {
            $backupPath = $this->writeBackup($articles);
        }

        $count = 0;
        foreach ($articles as $article) {
            if ($isRestore) {
                $article->unretire();
            } else {
                $article->retire();
            }
            $count++;
        }

        if (class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
            try {
                \Spatie\ResponseCache\Facades\ResponseCache::clear();
            } catch (\Throwable $e) {
                $this->warn('Purge du cache public échouée (non bloquant) : '.$e->getMessage());
            }
        }

        $this->info(($isRestore ? 'Restauré' : 'Retiré').' : '.$count.' fiche(s).');
        if ($backupPath) {
            $this->info("Sauvegarde de l'état AVANT retrait : {$backupPath}");
        }

        return self::SUCCESS;
    }

    /**
     * ACTION : exporte l'état actuel des fiches visées (id, slug, title, is_published,
     * seo_status, retired_at) dans storage/app/news-retire-backup-{timestamp}.json AVANT toute
     * mutation - garde-fou zéro-casse (règle 🔴 1) : rollback rapide toujours disponible même
     * sans passer par --restore (le JSON documente l'état exact d'avant, utile en audit).
     * MCP: SELF (<5 lignes utiles)
     * RAISON: backup + historique AVANT toute écriture, non négociable.
     *
     * @param \Illuminate\Support\Collection<int, NewsArticle> $articles
     */
    private function writeBackup(\Illuminate\Support\Collection $articles): string
    {
        $snapshot = $articles->map(fn (NewsArticle $a) => [
            'id' => $a->id,
            'slug' => $a->slug,
            'title' => $a->title,
            'is_published' => $a->is_published,
            'seo_status' => $a->seo_status,
            'retired_at' => $a->retired_at?->toIso8601String(),
        ])->all();

        // ACTION : chemin EXPLICITE storage/app/... via File (jamais Storage::disk('local'),
        // dont la racine est storage/app/private en Laravel 11 - le backup ne serait plus là où
        // il est documenté ni là où l'ops le cherche).
        // MCP: SELF (<5 lignes)
        $filename = 'news-retire-backup-'.now('America/Toronto')->format('Ymd-His').'.json';
        $fullPath = storage_path('app/'.$filename);
        \Illuminate\Support\Facades\File::put($fullPath, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $fullPath;
    }
}
