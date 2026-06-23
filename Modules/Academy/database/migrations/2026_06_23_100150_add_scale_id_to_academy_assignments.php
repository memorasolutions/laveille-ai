<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F14 - Référence d'ÉCHELLE optionnelle sur un devoir. Colonne ADDITIVE nullable
 * guardée (hasColumn). scale_id null = devoir NOTÉ NUMÉRIQUEMENT comme aujourd'hui
 * (rétrocompat stricte). nullOnDelete : supprimer une échelle ne casse jamais le
 * devoir (il redevient numérique). down() = retrait de la seule colonne nouvelle.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_assignments')) {
            return;
        }
        if (Schema::hasColumn('academy_assignments', 'scale_id')) {
            return;
        }

        Schema::table('academy_assignments', function (Blueprint $table): void {
            $table->unsignedBigInteger('scale_id')->nullable()->after('max_points');

            // Index posé inconditionnellement pour garantir la performance sur scale_id
            // même si la table academy_scales est absente et que la FK n'est pas créée.
            // Le try/catch absorbe un éventuel conflit si la FK crée l'index implicitement
            // (MySQL le fait ; le guard Schema::hasColumn ci-dessus empêche le double-run
            // en temps normal, mais on reste défensif pour les re-runs manuels).
            try {
                $table->index('scale_id');
            } catch (\Throwable) {
                // Index déjà posé (ex. via FK implicite) : ignoré.
            }

            if (Schema::hasTable('academy_scales')) {
                $table->foreign('scale_id')
                    ->references('id')
                    ->on('academy_scales')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_assignments')) {
            return;
        }
        if (! Schema::hasColumn('academy_assignments', 'scale_id')) {
            return;
        }

        Schema::table('academy_assignments', function (Blueprint $table): void {
            // Drop FK défensif (SQLite ignore ; MySQL exige le nom de la contrainte).
            try {
                $table->dropForeign(['scale_id']);
            } catch (\Throwable) {
                // Pas de FK (ex. table scales absente au up) → on ignore.
            }
            $table->dropColumn('scale_id');
        });
    }
};
