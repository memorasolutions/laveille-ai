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
    // Nom d'index EXPLICITE et court : le nom auto-généré
    // (academy_workshop_assessment_scores_assessment_id_criterion_id_unique = 68 car.)
    // dépasse la limite MySQL/MariaDB de 64 caractères et fait échouer la migration.
    private const UNIQUE_INDEX = 'academy_was_assess_crit_uq';

    public function up(): void
    {
        if (! Schema::hasTable('academy_workshop_assessment_scores')) {
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

                $table->unique(['assessment_id', 'criterion_id'], self::UNIQUE_INDEX);
            });

            return;
        }

        // Table déjà présente (demi-création d'un déploiement antérieur où l'index trop long
        // avait fait échouer l'ALTER) : on pose l'index manquant, sans casser si déjà là.
        Schema::table('academy_workshop_assessment_scores', function (Blueprint $table): void {
            try {
                $table->unique(['assessment_id', 'criterion_id'], self::UNIQUE_INDEX);
            } catch (\Throwable $e) {
                // Index déjà présent : rien à faire.
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_workshop_assessment_scores');
    }
};
