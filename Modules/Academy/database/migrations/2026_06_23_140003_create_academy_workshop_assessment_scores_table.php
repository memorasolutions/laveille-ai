<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F21 - ATELIER (Workshop) : une NOTE par critère pour une évaluation (type Moodle
 * « Workshop »). 1 ligne = (évaluation × critère) → score entier borné 0..max_score du
 * critère. Source des computed_score agrégés au service. Table ADDITIVE et idempotente
 * (guard Schema::hasTable).
 *
 *   - assessment_id : évaluation porteuse (FK cascade).
 *   - criterion_id  : critère de la grille noté (FK cascade).
 *   - score         : note du critère (entier, bornée 0..max_score au service).
 *   - unique(assessment_id, criterion_id) : une note par critère par évaluation (upsert).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_workshop_assessment_scores')) {
            return;
        }

        Schema::create('academy_workshop_assessment_scores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('criterion_id');
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();

            $table->foreign('assessment_id')
                ->references('id')
                ->on('academy_workshop_assessments')
                ->onDelete('cascade');

            $table->foreign('criterion_id')
                ->references('id')
                ->on('academy_workshop_criteria')
                ->onDelete('cascade');

            $table->unique(['assessment_id', 'criterion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_workshop_assessment_scores');
    }
};
