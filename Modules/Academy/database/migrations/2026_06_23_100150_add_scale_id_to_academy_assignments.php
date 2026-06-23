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
