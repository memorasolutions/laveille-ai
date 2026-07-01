<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Phase 2 du système de diplomation moderne — association COURS → GABARIT de diplôme.
 * `null` = comportement actuel inchangé (rendu par défaut, CertificateController).
 * `nullOnDelete()` : un gabarit supprimé fait simplement retomber le cours sur le
 * rendu par défaut, jamais de perte du cours. Migration ADDITIVE et réversible
 * (guard hasTable/hasColumn, comme add_certificate_customization_to_courses_table).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('courses') || ! Schema::hasTable('academy_diploma_templates')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            if (! Schema::hasColumn('courses', 'diploma_template_id')) {
                $table->unsignedBigInteger('diploma_template_id')->nullable()->after('certificate_accent_color');

                $table->foreign('diploma_template_id')
                    ->references('id')
                    ->on('academy_diploma_templates')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('courses')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            if (Schema::hasColumn('courses', 'diploma_template_id')) {
                $table->dropForeign(['diploma_template_id']);
                $table->dropColumn('diploma_template_id');
            }
        });
    }
};
