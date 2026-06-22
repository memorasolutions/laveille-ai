<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-b - BARÈME DE LETTRES par cours (ex. A>=90, B>=80, ...). Stocké en JSON sur
 * le cours (colonne nullable). NULL = barème par défaut raisonnable (helper
 * GradebookService::defaultLetterScheme). Migration ADDITIVE (guard hasColumn) ;
 * down() retire la SEULE colonne nouvelle (aucune perte de données existantes).
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
        if (Schema::hasColumn('courses', 'grade_letter_scheme')) {
            return;
        }

        Schema::table('courses', function (Blueprint $table): void {
            $table->json('grade_letter_scheme')->nullable()->after('certificate_accent_color');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('courses') && Schema::hasColumn('courses', 'grade_letter_scheme')) {
            Schema::table('courses', function (Blueprint $table): void {
                $table->dropColumn('grade_letter_scheme');
            });
        }
    }
};
