<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * ACHÈVEMENT DE COURS CONFIGURABLE (« course completion » façon Moodle).
 * Stocke le CRITÈRE de complétion du cours en JSON sur la colonne nullable
 * `completion_criteria`. NULL = critère par défaut « all_required » (= comportement
 * actuel : cours complété quand TOUS les items requis le sont). Forme stockée :
 *   { "type": "all_required" }
 *   { "type": "percent",  "value": 80 }            // ≥ 80 % des items requis
 *   { "type": "min_grade","value": 70 }            // note finale du carnet ≥ 70 %
 *   { "type": "selected_activities", "items": [..] } // sous-ensemble d'items requis
 *
 * Migration ADDITIVE et idempotente (guard Schema::hasColumn). down() retire la SEULE
 * colonne nouvelle (aucune perte de données existantes ; rétrocompatibilité totale).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }
        if (Schema::hasColumn('courses', 'completion_criteria')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            // Placée après grade_letter_scheme si présente, sinon en fin de table
            // (défensif : after() est ignoré si la colonne de référence manque).
            $table->json('completion_criteria')->nullable()->after('grade_letter_scheme');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'completion_criteria')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->dropColumn('completion_criteria');
            });
        }
    }
};
