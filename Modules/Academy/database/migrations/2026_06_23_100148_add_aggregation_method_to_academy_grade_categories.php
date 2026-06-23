<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F14 - MÉTHODE D'AGRÉGATION par catégorie de notes (parité Moodle). Colonne
 * ADDITIVE guardée (hasColumn). Défaut « weighted_mean » = comportement ACTUEL
 * (moyenne pondérée) → RÉTROCOMPAT STRICTE : un carnet existant donne EXACTEMENT
 * les mêmes notes. down() = retrait de la SEULE colonne nouvelle.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_grade_categories')) {
            return;
        }
        if (Schema::hasColumn('academy_grade_categories', 'aggregation_method')) {
            return;
        }

        Schema::table('academy_grade_categories', function (Blueprint $table): void {
            // Liste blanche appliquée côté service/composant. Défaut = moyenne pondérée
            // (poids des items) = exactement l'agrégation V2-b existante.
            $table->string('aggregation_method', 32)->default('weighted_mean')->after('weight');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_grade_categories')) {
            return;
        }
        if (! Schema::hasColumn('academy_grade_categories', 'aggregation_method')) {
            return;
        }

        Schema::table('academy_grade_categories', function (Blueprint $table): void {
            $table->dropColumn('aggregation_method');
        });
    }
};
