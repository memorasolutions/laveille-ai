<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TYPE ESSAI (réponse rédigée à CORRECTION MANUELLE, type Moodle « Essay »).
 * Colonnes additives sur l'historique des tentatives pour porter le WORKFLOW DE
 * CORRECTION qui casse l'invariant « quiz auto-noté à la soumission » :
 *   - needs_grading  : true tant qu'au moins un essai de la tentative reste à corriger
 *                      (la complétion N'EST PAS posée et le score auto est PROVISOIRE) ;
 *   - manual_scores  : map {index_essai: points_attribués} (saisie du formateur) ;
 *   - manual_feedback: map {index_essai: feedback} (rétroaction du formateur) ;
 *   - graded_at      : horodatage de la dernière correction (tous essais corrigés) ;
 *   - graded_by      : utilisateur correcteur (jamais une valeur du client).
 *
 * ADDITIVE + GUARDÉE : needs_grading défaut false → TOUTES les lignes existantes
 * restent « auto-notées / finalisées » (rétrocompat stricte). Un quiz SANS essai ne
 * pose jamais needs_grading=true → comportement strictement INCHANGÉ. down() retire
 * les colonnes (rollback garanti).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academy_quiz_attempts')) {
            return;
        }

        Schema::table('academy_quiz_attempts', function (Blueprint $table): void {
            if (! Schema::hasColumn('academy_quiz_attempts', 'needs_grading')) {
                $table->boolean('needs_grading')->default(false)->after('passed');
            }
            if (! Schema::hasColumn('academy_quiz_attempts', 'manual_scores')) {
                $table->json('manual_scores')->nullable()->after('answers');
            }
            if (! Schema::hasColumn('academy_quiz_attempts', 'manual_feedback')) {
                $table->json('manual_feedback')->nullable()->after('manual_scores');
            }
            if (! Schema::hasColumn('academy_quiz_attempts', 'graded_at')) {
                $table->timestamp('graded_at')->nullable()->after('submitted_at');
            }
            if (! Schema::hasColumn('academy_quiz_attempts', 'graded_by')) {
                $table->unsignedBigInteger('graded_by')->nullable()->after('graded_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('academy_quiz_attempts')) {
            return;
        }

        Schema::table('academy_quiz_attempts', function (Blueprint $table): void {
            foreach (['graded_by', 'graded_at', 'manual_feedback', 'manual_scores', 'needs_grading'] as $col) {
                if (Schema::hasColumn('academy_quiz_attempts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
