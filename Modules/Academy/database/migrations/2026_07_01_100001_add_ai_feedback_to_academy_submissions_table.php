<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK IA sur réponses ouvertes (correction assistée - LMS 2026). Colonnes
 * ADDITIVES sur les remises rédigées, STRICTEMENT DISTINCTES des champs humains
 * (score/feedback), pour ne JAMAIS écraser la correction du formateur :
 *   - ai_feedback        : brouillon de rétroaction proposé par l'IA (jamais officiel) ;
 *   - ai_suggested_score : note SUGGÉRÉE par l'IA (jamais la note officielle) ;
 *   - ai_generated_at    : horodatage de la dernière génération IA.
 * Le score/feedback officiels restent ceux du formateur (override manuel). Guards
 * hasTable/hasColumn (idempotent). down() = inverse exact de up() (dropColumn).
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

        Schema::table('academy_submissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('academy_submissions', 'ai_feedback')) {
                // Brouillon IA (markdown), distinct du feedback formateur (« feedback »).
                $table->text('ai_feedback')->nullable()->after('rubric_scores');
            }
            if (! Schema::hasColumn('academy_submissions', 'ai_suggested_score')) {
                // Note suggérée par l'IA — jamais la note officielle (« score »).
                $table->decimal('ai_suggested_score', 5, 2)->nullable()->after('ai_feedback');
            }
            if (! Schema::hasColumn('academy_submissions', 'ai_generated_at')) {
                $table->timestamp('ai_generated_at')->nullable()->after('ai_suggested_score');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_submissions')) {
            return;
        }

        Schema::table('academy_submissions', function (Blueprint $table): void {
            foreach (['ai_generated_at', 'ai_suggested_score', 'ai_feedback'] as $column) {
                if (Schema::hasColumn('academy_submissions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
