<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Introduit la date de modification ÉDITORIALE (`content_updated_at`), distincte de
 * `updated_at` - voir Modules\Core\Traits\TracksEditorialModification et
 * docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md (MESURE B). `updated_at` est réécrit
 * par une simple consultation (Modules\Core\Services\ViewCounterService::record()) depuis
 * toujours, et publié à tort comme `dateModified` (JSON-LD) / `lastmod` (sitemap) d'une actualité.
 *
 * Colonne ADDITIVE, nullable : aucune ligne existante n'est modifiée avant le backfill explicite
 * ci-dessous, aucune colonne existante n'est touchée.
 *
 * Valeur de départ HONNÊTE pour l'existant (jamais une date plus récente que ce qu'on peut
 * prouver) :
 *   1. Toutes les lignes reçoivent d'abord leur propre `created_at` (seule certitude disponible
 *      pour une ligne sans aucune trace d'édition).
 *   2. Les lignes qui ont AU MOINS une entrée dans `activity_log` (log_name = 'news_article',
 *      écrite par Modules\Core\Traits\LogsActivityStandard sur un `save()` explicite - jamais
 *      par ViewCounterService, qui passe par le query builder brut) reçoivent la date de leur
 *      dernière entrée journalisée à la place, plus proche de la réalité éditoriale.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_articles', function (Blueprint $table) {
            if (! Schema::hasColumn('news_articles', 'content_updated_at')) {
                $table->timestamp('content_updated_at')->nullable()->after('updated_at');
            }
        });

        // Étape 1 : repli honnête pour toutes les lignes - la date de création.
        DB::table('news_articles')->update(['content_updated_at' => DB::raw('created_at')]);

        // Étape 2 : override par la dernière trace éditoriale RÉELLE quand elle existe (sous-
        // ensemble restreint des lignes - jamais une requête sur la totalité de la table).
        DB::table('activity_log')
            ->select('subject_id', DB::raw('MAX(created_at) as last_edit'))
            ->where('subject_type', \Modules\News\Models\NewsArticle::class)
            ->where('log_name', 'news_article')
            ->groupBy('subject_id')
            ->get()
            ->each(function (object $edit): void {
                DB::table('news_articles')
                    ->where('id', $edit->subject_id)
                    ->update(['content_updated_at' => $edit->last_edit]);
            });
    }

    public function down(): void
    {
        // Donnée entièrement DÉRIVÉE (reconstituée depuis created_at/activity_log ci-dessus,
        // jamais une saisie humaine irremplaçable) : un rollback qui retire la colonne ne perd
        // aucune information qu'on ne pourrait pas recalculer en la relançant.
        Schema::table('news_articles', function (Blueprint $table) {
            if (Schema::hasColumn('news_articles', 'content_updated_at')) {
                $table->dropColumn('content_updated_at');
            }
        });
    }
};
