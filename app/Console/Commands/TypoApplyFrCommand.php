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
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
     */
    protected array $plan = [
        'articles' => ['title', 'excerpt', 'content', 'meta_title', 'meta_description'],
        'ads_placements' => ['name'],
        'directory_tools' => ['name', 'short_description', 'description'],
        'dictionary_terms' => ['term', 'definition', 'context'],
        'news_articles' => ['title', 'excerpt', 'content'],
        'pages' => ['title', 'content', 'meta_title', 'meta_description'],
        'faqs' => ['question', 'answer'],
        'testimonials' => ['name', 'content'],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $tablesOpt = (array) $this->option('table');
        $tables = ! empty($tablesOpt) ? $tablesOpt : array_keys($this->plan);

        $this->info(($dry ? '[DRY] ' : '') . 'Application typographie FR — NBSP avant ? ! : ; » et chiffre+unité');
        $this->newLine();

        $totalChanged = 0;
        $totalRows = 0;

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->warn("- Table '{$table}' absente (skip)");

                continue;
            }

            $cols = $this->plan[$table] ?? null;
            if ($cols === null) {
                $this->warn("- Table '{$table}' pas dans le plan (skip)");

                continue;
            }

            // Ne garde que les colonnes qui existent vraiment
            $cols = array_values(array_filter($cols, static fn ($c) => Schema::hasColumn($table, $c)));
            if (empty($cols)) {
                $this->warn("- Table '{$table}' : aucune colonne du plan présente (skip)");

                continue;
            }

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
}
