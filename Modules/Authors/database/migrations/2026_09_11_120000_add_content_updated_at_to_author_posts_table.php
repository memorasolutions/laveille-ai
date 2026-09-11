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
 * toujours, et publié à tort comme `dateModified` (JSON-LD, app/Helpers/jsonld.php) et `lastmod`
 * du sitemap dédié `sitemap-authors.xml`.
 *
 * Colonne ADDITIVE, nullable : aucune ligne existante n'est modifiée avant le backfill explicite
 * ci-dessous, aucune colonne existante n'est touchée.
 *
 * Valeur de départ HONNÊTE pour l'existant (jamais une date plus récente que ce qu'on peut
 * prouver) :
 *   1. Toutes les lignes reçoivent d'abord leur propre `created_at`.
 *   2. Les lignes qui ont AU MOINS une entrée dans `activity_log` (log_name = 'author_post',
 *      écrite par Spatie\Activitylog\Traits\LogsActivity sur un `save()` explicite - jamais par
 *      ViewCounterService, qui passe par le query builder brut) reçoivent la date de leur
 *      dernière entrée journalisée à la place.
 *
 * `author_posts` est actuellement VIDE en production (confirmé le 2026-09-11, MESURE A du même
 * document) : ce backfill n'a donc aucun effet mesurable aujourd'hui, mais reste correct pour la
 * première ligne qui y sera créée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('author_posts', function (Blueprint $table) {
            if (! Schema::hasColumn('author_posts', 'content_updated_at')) {
                $table->timestamp('content_updated_at')->nullable()->after('updated_at');
            }
        });

        DB::table('author_posts')->update(['content_updated_at' => DB::raw('created_at')]);

        DB::table('activity_log')
            ->select('subject_id', DB::raw('MAX(created_at) as last_edit'))
            ->where('subject_type', \Modules\Authors\Models\AuthorPost::class)
            ->where('log_name', 'author_post')
            ->groupBy('subject_id')
            ->get()
            ->each(function (object $edit): void {
                DB::table('author_posts')
                    ->where('id', $edit->subject_id)
                    ->update(['content_updated_at' => $edit->last_edit]);
            });
    }

    public function down(): void
    {
        // Donnée entièrement DÉRIVÉE (reconstituée depuis created_at/activity_log ci-dessus,
        // jamais une saisie humaine irremplaçable) : un rollback qui retire la colonne ne perd
        // aucune information qu'on ne pourrait pas recalculer en la relançant.
        Schema::table('author_posts', function (Blueprint $table) {
            if (Schema::hasColumn('author_posts', 'content_updated_at')) {
                $table->dropColumn('content_updated_at');
            }
        });
    }
};
