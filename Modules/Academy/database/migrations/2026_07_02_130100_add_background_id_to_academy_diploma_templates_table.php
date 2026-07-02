<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Phase 3 du système de diplomation moderne — association GABARIT → ARRIÈRE-PLAN
 * réutilisable (academy_diploma_backgrounds). `null` = comportement actuel inchangé
 * (fond uni existant, aucune régression). `nullOnDelete()` : un arrière-plan supprimé
 * fait simplement retomber le gabarit sur un fond uni, jamais de perte du gabarit.
 * Migration ADDITIVE et réversible (guard hasTable/hasColumn, comme
 * add_diploma_template_id_to_courses_table).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_diploma_templates') || ! Schema::hasTable('academy_diploma_backgrounds')) {
            return;
        }

        Schema::table('academy_diploma_templates', function (Blueprint $table): void {
            if (! Schema::hasColumn('academy_diploma_templates', 'background_id')) {
                $table->unsignedBigInteger('background_id')->nullable()->after('layout_config');

                $table->foreign('background_id')
                    ->references('id')
                    ->on('academy_diploma_backgrounds')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_diploma_templates')) {
            return;
        }

        Schema::table('academy_diploma_templates', function (Blueprint $table): void {
            if (Schema::hasColumn('academy_diploma_templates', 'background_id')) {
                $table->dropForeign(['background_id']);
                $table->dropColumn('background_id');
            }
        });
    }
};
