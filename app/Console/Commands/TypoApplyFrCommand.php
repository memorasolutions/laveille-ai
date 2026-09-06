<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Commande Artisan one-shot idempotente : applique lv_typo_fr() sur les
 * colonnes texte des tables user-facing. Sûr à ré-exécuter (idempotent).
 *
 * Usage :
 *   php artisan typo:apply-fr --dry              # affiche les diffs, n'écrit pas
 *   php artisan typo:apply-fr                    # écrit en base
 *   php artisan typo:apply-fr --table=articles   # cible une seule table
 *
 * ACTION : ticket #2290 (2026-09-06) - le plan déclarait 8 colonnes qui n'existent PAS dans le
 * schéma réel (dictionary_terms.term/context, news_articles.excerpt/content,
 * articles.meta_title/meta_description, static_pages sous le mauvais nom de table 'pages',
 * testimonials.name). L'ancien code les filtrait EN SILENCE (array_filter sur Schema::hasColumn),
 * ou sautait la table absente avec un simple warn() sans jamais faire échouer la commande : ces
 * colonnes n'étaient donc JAMAIS typographiées, sans qu'aucun signal fort ne le révèle. Un
 * traitement qui vise une colonne inexistante ne produit ni erreur ni correction - il produit du
 * SILENCE, pire qu'un échec visible. guardPlanIntegrity() ci-dessous remplace ce filtrage muet
 * par un refus explicite (table + colonne fautives nommées, code de sortie non-zéro), AVANT toute
 * lecture ou écriture de ligne.
 * MCP: SELF (correctif de configuration + garde, pas de génération de contenu)
 * RAISON: mandat explicite du ticket #2290 - « fais échouer la commande, bruyamment ».
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Services\ModuleChecker;

class TypoApplyFrCommand extends Command
{
    protected $signature = 'typo:apply-fr
                            {--dry : Show changes without saving}
                            {--table=* : Specific tables to process (default: full curated list)}';

    protected $description = 'Applique la typographie française (NBSP) sur les colonnes texte user-facing — idempotent.';

    /**
     * Plan de traitement : table => colonnes texte à normaliser.
     *
     * Seules les colonnes user-facing (titre, résumé, contenu visible) sont
     * touchées. On ignore meta technique (slug, codes, JSON config, etc.).
     *
     * ACTION : plan corrigé le 2026-09-06 (ticket #2290) contre le schéma RÉEL
     * (Schema::getColumnListing(), jamais une déduction sur le nom du modèle - c'est
     * précisément l'erreur qui avait produit ce plan fautif) :
     *   - dictionary_terms.term  → 'name'  (colonne réelle, JSON Spatie translatable dès la
     *     migration de création - jamais appelée 'term'). .context RETIRÉE : n'a jamais existé,
     *     aucune migration ne l'a créée, aucun équivalent plausible sur ce modèle.
     *   - news_articles.excerpt → 'summary' (« le chapeau », colonne texte PLATE - preuve :
     *     NewsArticle::displayExcerpt() lit $this->summary en premier repli, searchableFields()
     *     l'expose comme le résumé public). .content RETIRÉE : aucune colonne plate n'y
     *     correspond - le corps réel vit dans structured_summary (JSON imbriqué avec des clés
     *     hook/why_important/key_points[]/quote.text/reperes_dates[]), et quote.text est une
     *     CITATION VERBATIM qu'une typographie générique ne doit jamais toucher (cf. docblock de
     *     lv_strip_em_dash() dans app/Helpers/typo.php). L'ajouter exigerait un traitement
     *     dédié qui exclut les clés de citation - hors mandat de ce ticket, à traiter séparément.
     *   - articles.meta_title / .meta_description → une seule colonne réelle 'meta' (JSON, PAS
     *     traduisible - preuve : migration de création `$table->json('meta')->nullable(); //
     *     SEO meta title/description`, et Article::getSeoTitleAttribute() lit meta['title']).
     *   - articles.title / .excerpt / .content : DÉJÀ CORRECTES (colonnes réelles), inchangées.
     *   - clé de table 'pages' → 'static_pages' (le module Pages est ACTIF mais sa table s'est
     *     toujours appelée 'static_pages' - aucune migration n'a jamais créé de table 'pages').
     *     Colonnes inchangées : title/content/meta_title/meta_description existent bel et bien
     *     sur static_pages (contrairement à articles, ce sont ici deux colonnes plates réelles,
     *     pas un JSON 'meta' unique - confirmé par Schema::getColumnListing('static_pages')).
     *   - testimonials.name → 'author_name' (colonne réelle - la migration de création ne
     *     déclare jamais 'name', seulement 'author_name'). Module Testimonials désactivé dans ce
     *     déploiement (modules_statuses.json) : voir $optionalModuleTables ci-dessous, c'est
     *     pourquoi cette entrée n'a pu être vérifiée que par lecture de la migration, jamais par
     *     Schema::getColumnListing() en direct.
     */
    protected array $plan = [
        'articles' => ['title', 'excerpt', 'content', 'meta'],
        'ads_placements' => ['name'],
        'directory_tools' => ['name', 'short_description', 'description'],
        'dictionary_terms' => ['name', 'definition'],
        'news_articles' => ['title', 'summary'],
        'static_pages' => ['title', 'content', 'meta_title', 'meta_description'],
        'faqs' => ['question', 'answer'],
        'testimonials' => ['author_name', 'content'],
    ];

    /**
     * Tables du plan dont l'absence du schéma est EXPLIQUÉE par un module nwidart optionnel
     * désactivé (modules_statuses.json) - un état normal de l'architecture « module
     * activable/désactivable sans casse » (CLAUDE.md), jamais un défaut de configuration.
     * Toute AUTRE table du plan absente du schéma réel N'A PAS cette excuse : c'est soit un nom
     * de table erroné (le cas 'pages' corrigé ci-dessus), soit une table jamais créée - dans les
     * deux cas guardPlanIntegrity() la traite comme un défaut et fait échouer la commande.
     *
     * @var array<string, string> table => nom du module nwidart qui la porte
     */
    protected array $optionalModuleTables = [
        'testimonials' => 'Testimonials',
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $tablesOpt = (array) $this->option('table');
        $tables = ! empty($tablesOpt) ? $tablesOpt : array_keys($this->plan);

        // GARDE (ticket #2290) : valide le plan CONTRE LE SCHÉMA RÉEL avant toute lecture ou
        // écriture de ligne. Remplace le filtrage silencieux (array_filter sur Schema::hasColumn)
        // qui laissait une colonne fautive disparaître sans le moindre signal.
        $errors = $this->guardPlanIntegrity($tables);
        if (! empty($errors)) {
            $this->error('typo:apply-fr refuse de continuer - le plan ne correspond pas au schéma réel :');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
            $this->error('Aucune ligne lue, aucune ligne écrite. Corrige app/Console/Commands/TypoApplyFrCommand.php::$plan avant de relancer.');

            return self::FAILURE;
        }

        $this->info(($dry ? '[DRY] ' : '') . 'Application typographie FR — NBSP avant ? ! : ; » et chiffre+unité');
        $this->newLine();

        $totalChanged = 0;
        $totalRows = 0;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                // Passé la garde ci-dessus, la SEULE raison possible ici est un module optionnel
                // désactivé (whitelisté dans $optionalModuleTables) - déjà signalé par la garde.
                continue;
            }

            $cols = $this->plan[$table];

            $this->line("→ Table <fg=cyan>{$table}</> : " . implode(', ', $cols));

            $changedInTable = 0;
            $rowsInTable = 0;

            DB::table($table)
                ->select(array_merge(['id'], $cols))
                ->orderBy('id')
                ->chunkById(500, function ($rows) use ($table, $cols, $dry, &$changedInTable, &$rowsInTable): void {
                    foreach ($rows as $row) {
                        $rowsInTable++;
                        $update = [];
                        foreach ($cols as $col) {
                            $orig = $row->{$col} ?? null;
                            if ($orig === null || $orig === '') {
                                continue;
                            }
                            $new = lv_typo_fr((string) $orig);
                            if ($new !== $orig) {
                                $update[$col] = $new;
                            }
                        }
                        if (! empty($update)) {
                            $changedInTable++;
                            if (! $dry) {
                                DB::table($table)->where('id', $row->id)->update($update);
                            }
                        }
                    }
                });

            $this->line("  → {$changedInTable} ligne(s) modifiée(s) sur {$rowsInTable} scannée(s)");
            $totalChanged += $changedInTable;
            $totalRows += $rowsInTable;
        }

        $this->newLine();
        $this->info(($dry ? '[DRY] ' : '') . "Total : {$totalChanged} ligne(s) modifiée(s) sur {$totalRows} scannée(s)");

        if ($dry) {
            $this->comment('Aucune écriture effectuée (--dry). Relancer sans --dry pour appliquer.');
        }

        return self::SUCCESS;
    }

    /**
     * Valide chaque table/colonne demandée contre le schéma RÉEL (jamais une supposition sur le
     * nom du modèle). Lecture pure de métadonnées de schéma - aucune ligne de données n'est lue
     * ni modifiée ici. Retourne la liste des défauts trouvés (vide = plan cohérent).
     *
     * Deux défauts distincts sont nommés séparément, jamais fusionnés en un message générique :
     *   - une table demandée absente du plan curaté (--table avec un nom inconnu) ;
     *   - une table du plan absente du schéma et non couverte par $optionalModuleTables ;
     *   - une colonne du plan absente d'une table qui, elle, existe bel et bien - LE défaut
     *     d'origine de ce ticket, celui qui produisait du silence.
     *
     * @param  array<int, string>  $tables
     * @return array<int, string>
     */
    protected function guardPlanIntegrity(array $tables): array
    {
        $errors = [];

        foreach ($tables as $table) {
            $cols = $this->plan[$table] ?? null;
            if ($cols === null) {
                $errors[] = "table '{$table}' demandée via --table mais absente du plan curaté";

                continue;
            }

            if (! Schema::hasTable($table)) {
                $module = $this->optionalModuleTables[$table] ?? null;
                if ($module !== null && ! ModuleChecker::isAvailable($module)) {
                    $this->warn("- Table '{$table}' absente (module '{$module}' désactivé - modules_statuses.json, comportement attendu, skip)");

                    continue;
                }

                $errors[] = "table '{$table}' absente du schéma réel et aucun module optionnel désactivé connu ne l'explique - nom de table erroné dans le plan, ou table jamais créée";

                continue;
            }

            foreach ($cols as $col) {
                if (! Schema::hasColumn($table, $col)) {
                    $errors[] = "colonne '{$table}.{$col}' absente du schéma réel (déclarée dans le plan mais introuvable)";
                }
            }
        }

        return $errors;
    }
}
