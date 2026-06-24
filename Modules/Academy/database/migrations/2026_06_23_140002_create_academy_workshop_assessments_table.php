<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : ÉVALUATIONS d'un travail par un pair (type Moodle « Workshop »).
 * Une évaluation = un évaluateur (assessor_id) qui note un travail (submission_id) selon la
 * grille. L'attribution (allocation) crée la ligne d'évaluation ; le pair la remplit ensuite
 * (scores par critère + feedback). Table ADDITIVE et idempotente (guard Schema::hasTable).
 *
 *   - submission_id  : travail évalué (FK cascade).
 *   - assessor_id NULLABLE (nullOnDelete) : pair évaluateur ; null si compte supprimé.
 *   - feedback       : retour qualitatif facultatif (texte, rendu strippé : anti-XSS).
 *   - computed_score : note DÉRIVÉE du travail selon la grille (0..100), calculée au service
 *     à l'enregistrement ; null tant que l'évaluation n'est pas rendue.
 *   - submitted_at   : horodatage de remise de l'évaluation (null = non rendue).
 *   - SoftDeletes    : une évaluation supprimée est conservée (audit), exclue par défaut.
 *   - unique(submission_id, assessor_id) : un pair n'évalue un travail qu'une seule fois.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_workshop_assessments')) {
            return;
        }

        Schema::create('academy_workshop_assessments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('submission_id');
            $table->unsignedBigInteger('assessor_id')->nullable();
            $table->text('feedback')->nullable();
            $table->decimal('computed_score', 6, 2)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('submission_id')
                ->references('id')
                ->on('academy_workshop_submissions')
                ->onDelete('cascade');

            $table->foreign('assessor_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['submission_id', 'assessor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_workshop_assessments');
    }
};
