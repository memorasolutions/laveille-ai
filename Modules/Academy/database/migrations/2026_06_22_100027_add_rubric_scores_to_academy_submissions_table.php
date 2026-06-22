<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-a - GRILLES D'ÉVALUATION (rubrics). Colonne ADDITIVE « rubric_scores » (json
 * nullable) sur les remises : mémorise le niveau retenu par critère sous la forme
 * {criterion_id: level_id}. null = remise corrigée à la main (aucune grille) →
 * comportement strictement inchangé (rétrocompat). Guard hasColumn ; down
 * dropColumn (la SEULE colonne nouvelle).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_submissions')) {
            return;
        }

        if (Schema::hasColumn('academy_submissions', 'rubric_scores')) {
            return;
        }

        Schema::table('academy_submissions', function (Blueprint $table): void {
            // {criterion_id: level_id} du barème appliqué ; null = correction manuelle.
            $table->json('rubric_scores')->nullable()->after('feedback');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_submissions')) {
            return;
        }

        if (! Schema::hasColumn('academy_submissions', 'rubric_scores')) {
            return;
        }

        Schema::table('academy_submissions', function (Blueprint $table): void {
            $table->dropColumn('rubric_scores');
        });
    }
};
