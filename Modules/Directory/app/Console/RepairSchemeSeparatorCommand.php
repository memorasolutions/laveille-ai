<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * ACTION : porte de rattrapage RÉVERSIBLE du ticket #2289 (2026-09-05) - un espace insécable
 *          (U+00A0) s'est glissé entre `https` et `://` dans des descriptions rédigées par le
 *          pipeline d'enrichissement IA. Cette commande corrige les fiches DÉJÀ écrites en base
 *          avant que Modules/Directory/app/Observers/ToolObserver::saving() ne ferme la porte à
 *          l'écriture, pour TOUT appelant (pas seulement les commandes d'enrichissement).
 *
 *          Modèle suivi : Modules/News/app/Console/RetireArticlesCommand.php (news:retire) -
 *          sauvegarde horodatée AVANT toute mutation, --dry-run explicite, --restore pour revenir
 *          en arrière.
 *
 *          Détection = correction : un champ est « affecté » si lv_repare_jonction_schema_url()
 *          (app/Helpers/typo.php) en change la valeur. Aucune deuxième expression régulière de
 *          détection - la même fonction sert à repérer ET à corriger, comme l'exige le ticket.
 *
 *          TOUTES les locales sont scrutées, jamais seulement fr_CA (mesuré 2026-09-05: certaines
 *          fiches portent aussi des traductions 'fr' et 'en' sur ces deux champs) - même principe
 *          que ToolObserver::saving().
 *
 *          --restore contourne DÉLIBÉRÉMENT ToolObserver (Tool::withoutEvents()) : la garde
 *          répare désormais TOUTE écriture, y compris celle d'un --restore qui replacerait la
 *          valeur défectueuse - sans ce contournement, --restore serait immédiatement re-corrigé
 *          et ne pourrait plus jamais reproduire l'état d'avant, ce qui est précisément sa raison
 *          d'être (audit, ou rollback si la correction se révélait un jour trop agressive).
 *
 * MCP: SELF (<5 lignes utiles par branche)
 * RAISON: design doc du ticket - porte serveur unique, jamais d'écriture directe SQL ailleurs.
 */

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;

class RepairSchemeSeparatorCommand extends Command
{
    protected $signature = 'tools:repair-scheme-separator
        {--dry-run : Affiche seulement le compte et le détail, n\'écrit rien}
        {--restore= : Chemin d\'un fichier de sauvegarde JSON produit par une exécution précédente ; restaure les valeurs d\'AVANT correction}';

    protected $description = 'Corrige (ou restaure) la jonction schéma/séparateur (ex. "https<insécable>://") dans les descriptions des fiches outils, toutes locales confondues';

    /** Les deux champs traduits touchés par le pipeline IA - mêmes champs que ToolObserver::saving(). */
    private const CHAMPS = ['description', 'short_description'];

    public function handle(): int
    {
        $restorePath = (string) $this->option('restore');
        if ($restorePath !== '') {
            return $this->restaurer($restorePath);
        }

        $isDryRun = (bool) $this->option('dry-run');

        $affectes = $this->detecter();

        if ($affectes === []) {
            $this->info('Aucune fiche à corriger : aucun champ, aucune locale ne contient la jonction schéma/séparateur cassée.');

            return self::SUCCESS;
        }

        if ($isDryRun) {
            $this->afficherRapport($affectes, dryRun: true);
            $this->info("Mode --dry-run : {$this->compterLignes($affectes)} ligne(s) seraient corrigées. Aucune modification effectuée.");

            return self::SUCCESS;
        }

        $backupPath = $this->ecrireSauvegarde($affectes);

        $corrige = 0;
        foreach ($affectes as $entree) {
            $tool = Tool::find($entree['id']);
            if (! $tool) {
                continue;
            }
            // ToolObserver::saving() répare aussi ce champ à l'écriture : appel idempotent
            // (la valeur est déjà propre), pas un doublon de règle - même connaissance,
            // un seul endroit qui la porte (app/Helpers/typo.php::lv_repare_jonction_schema_url).
            $tool->setTranslation($entree['champ'], $entree['locale'], $entree['apres']);
            $tool->save();
            $corrige++;
        }

        if (class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
            try {
                \Spatie\ResponseCache\Facades\ResponseCache::clear();
            } catch (\Throwable $e) {
                $this->warn('Purge du cache public échouée (non bloquant) : '.$e->getMessage());
            }
        }

        $this->afficherRapport($affectes, dryRun: false);
        $this->info("Corrigé : {$corrige} ligne(s).");
        $this->info("Sauvegarde de l'état AVANT correction : {$backupPath}");

        return self::SUCCESS;
    }

    /**
     * ACTION : parcourt toutes les fiches, TOUTES LOCALES de description/short_description
     * comprises, et applique lv_repare_jonction_schema_url() ; ne retient que les couples
     * (champ, locale) que la fonction a réellement changés. C'est la SEULE porte de détection -
     * jamais une expression régulière séparée.
     * MCP: SELF (<10 lignes utiles)
     * RAISON: le ticket exige la même fonction pour repérer et pour corriger.
     *
     * @return array<int, array{id: int, slug: string, champ: string, locale: string, avant: string, apres: string}>
     */
    private function detecter(): array
    {
        $affectes = [];

        Tool::query()->orderBy('id')->chunk(200, function ($tools) use (&$affectes) {
            foreach ($tools as $tool) {
                foreach (self::CHAMPS as $champ) {
                    foreach ($tool->getTranslations($champ) as $locale => $avant) {
                        if (! is_string($avant) || $avant === '') {
                            continue;
                        }

                        $apres = lv_repare_jonction_schema_url($avant);
                        if ($apres === $avant) {
                            continue;
                        }

                        $affectes[] = [
                            'id' => $tool->id,
                            'slug' => (string) ($tool->getTranslation('slug', 'fr_CA', false) ?? $tool->id),
                            'champ' => $champ,
                            'locale' => $locale,
                            'avant' => $avant,
                            'apres' => $apres,
                        ];
                    }
                }
            }
        });

        return $affectes;
    }

    /** @param array<int, array{id: int, slug: string, champ: string, locale: string, avant: string, apres: string}> $affectes */
    private function afficherRapport(array $affectes, bool $dryRun): void
    {
        $verbe = $dryRun ? 'seraient corrigés' : 'corrigés';
        $this->info(count($affectes)." champ(s) $verbe :");

        $lignes = array_map(fn (array $e) => [
            $e['id'],
            $e['slug'],
            $e['locale'],
            $e['champ'],
            $this->extraitJonction($e['avant']),
            $this->extraitJonction($e['apres']),
        ], $affectes);

        $this->table(['ID', 'Slug', 'Locale', 'Champ', 'Avant (extrait)', 'Après (extrait)'], $lignes);
    }

    /** Isole ~40 caractères autour de la jonction schéma/séparateur pour un rapport lisible. */
    private function extraitJonction(string $texte): string
    {
        if (preg_match('/.{0,10}https?['.LV_URL_BLANCS_INVISIBLES.']*:\/{1,2}.{0,20}/u', $texte, $m)) {
            return trim(preg_replace('/\s+/u', ' ', $m[0]) ?? $m[0]);
        }

        return mb_substr($texte, 0, 40);
    }

    /** @param array<int, array{id: int, slug: string, champ: string, locale: string, avant: string, apres: string}> $affectes */
    private function compterLignes(array $affectes): int
    {
        return count($affectes);
    }

    /**
     * ACTION : exporte {id, slug, champ, locale, avant, après} des lignes touchées dans
     * storage/app/directory-repair-scheme-separator-backup-{timestamp}.json AVANT toute
     * mutation - garde-fou zéro-casse (règle 🔴 1) : rollback rapide via --restore.
     * MCP: SELF (<5 lignes utiles)
     * RAISON: backup + historique AVANT toute écriture, non négociable.
     *
     * @param array<int, array{id: int, slug: string, champ: string, locale: string, avant: string, apres: string}> $affectes
     */
    private function ecrireSauvegarde(array $affectes): string
    {
        // ACTION : chemin EXPLICITE storage/app/... via File (jamais Storage::disk('local'),
        // dont la racine est storage/app/private en Laravel 11 - le backup ne serait plus là où
        // il est documenté ni là où l'ops le cherche).
        // MCP: SELF (<5 lignes)
        $filename = 'directory-repair-scheme-separator-backup-'.now('America/Toronto')->format('Ymd-His').'.json';
        $fullPath = storage_path('app/'.$filename);
        File::put($fullPath, json_encode($affectes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $fullPath;
    }

    /**
     * Restaure les valeurs d'AVANT correction à partir d'un fichier de sauvegarde produit
     * ci-dessus. Tool::withoutEvents() est OBLIGATOIRE ici : ToolObserver::saving() répare
     * désormais TOUTE écriture de ces deux champs, donc un save() normal re-corrigerait la
     * valeur défectueuse à l'instant où on tente de la restaurer, rendant --restore inopérant.
     * Contourner l'observer ici est le comportement VOULU d'un outil de rollback/audit.
     */
    private function restaurer(string $chemin): int
    {
        if (! is_file($chemin)) {
            $this->error("Fichier de sauvegarde introuvable : {$chemin}");

            return self::FAILURE;
        }

        $entrees = json_decode((string) file_get_contents($chemin), true);
        if (! is_array($entrees) || $entrees === []) {
            $this->error('Fichier de sauvegarde vide ou invalide.');

            return self::FAILURE;
        }

        $restaure = 0;
        Tool::withoutEvents(function () use ($entrees, &$restaure) {
            foreach ($entrees as $entree) {
                $tool = Tool::find($entree['id'] ?? null);
                if (! $tool || ! isset($entree['champ'], $entree['locale'], $entree['avant'])) {
                    continue;
                }
                $tool->setTranslation($entree['champ'], $entree['locale'], $entree['avant']);
                $tool->save();
                $restaure++;
            }
        });

        if (class_exists(\Spatie\ResponseCache\Facades\ResponseCache::class)) {
            try {
                \Spatie\ResponseCache\Facades\ResponseCache::clear();
            } catch (\Throwable $e) {
                $this->warn('Purge du cache public échouée (non bloquant) : '.$e->getMessage());
            }
        }

        $this->info("Restauré : {$restaure} champ(s) à partir de {$chemin}.");

        return self::SUCCESS;
    }
}
