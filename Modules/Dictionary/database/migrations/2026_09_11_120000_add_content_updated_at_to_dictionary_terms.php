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
 * docs/specs/2026-09-11-mesure-visibilite-et-fraicheur.md (MESURE B, section 4). `updated_at`
 * est réécrit par une simple consultation (Modules\Core\Services\ViewCounterService::record())
 * et publié à tort comme texte VISIBLE « Mis à jour le… » sur chaque fiche du glossaire
 * (show.blade.php, commentaire "signal freshness GEO 2026") - le module qui a servi de mesure
 * de référence : 78 des 80 termes vérifiables dérivaient déjà de plus de 24h, jusqu'à ~50 jours.
 *
 * Colonne ADDITIVE, nullable : aucune ligne existante n'est modifiée avant le backfill explicite
 * ci-dessous, aucune colonne existante n'est touchée.
 *
 * Valeur de départ HONNÊTE pour l'existant (jamais une date plus récente que ce qu'on peut
 * prouver) :
 *   1. Toutes les lignes reçoivent d'abord leur propre `created_at`.
 *   2. Les lignes qui ont AU MOINS une entrée dans `activity_log` (log_name = 'term', écrite
 *      par Modules\Core\Traits\LogsActivityStandard sur un `save()` explicite - jamais par
 *      ViewCounterService, qui passe par le query builder brut) reçoivent la date de leur
 *      dernière entrée journalisée à la place.
 *
 * 464 des 544 termes mesurés le 2026-09-11 n'ont AUCUNE entrée `activity_log` : ils gardent donc
 * `content_updated_at` = `created_at` (aucune révision connue). C'est volontaire, pas une
 * lacune : Modules\Core\Traits\TracksEditorialModification::hasKnownEditorialRevision() lit
 * exactement cette égalité pour décider si le texte « Révisé le… » doit s'afficher ou rester
 * masqué (jamais présenter une date de création comme une date de révision).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dictionary_terms', function (Blueprint $table) {
            if (! Schema::hasColumn('dictionary_terms', 'content_updated_at')) {
                $table->timestamp('content_updated_at')->nullable()->after('updated_at');
            }
        });

        DB::table('dictionary_terms')->update(['content_updated_at' => DB::raw('created_at')]);

        DB::table('activity_log')
            ->select('subject_id', DB::raw('MAX(created_at) as last_edit'))
            ->where('subject_type', \Modules\Dictionary\Models\Term::class)
            ->where('log_name', 'term')
            ->groupBy('subject_id')
            ->get()
            ->each(function (object $edit): void {
                DB::table('dictionary_terms')
                    ->where('id', $edit->subject_id)
                    ->update(['content_updated_at' => $edit->last_edit]);
            });
    }

    public function down(): void
    {
        // Donnée entièrement DÉRIVÉE (reconstituée depuis created_at/activity_log ci-dessus,
        // jamais une saisie humaine irremplaçable) : un rollback qui retire la colonne ne perd
        // aucune information qu'on ne pourrait pas recalculer en la relançant.
        Schema::table('dictionary_terms', function (Blueprint $table) {
            if (Schema::hasColumn('dictionary_terms', 'content_updated_at')) {
                $table->dropColumn('content_updated_at');
            }
        });
    }
};
