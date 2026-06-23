<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK - PARTICIPATION à un sondage (item de leçon « feedback »). Table ADDITIVE
 * et idempotente (guards) : trace QUI a répondu (étudiant authentifié), JAMAIS ce
 * qu'il a répondu. C'est le filet anti re-spam ANONYME robuste (la réponse anonyme
 * reste user_id NULL dans academy_feedback_responses ; ici on ne stocke que le FAIT
 * d'avoir participé, donc l'anonymat des RÉPONSES est intégralement préservé).
 *
 *   - UNIQUE(lesson_item_id, user_id) : une participation par (item, étudiant). Un
 *     étudiant reconnecté (session régénérée) ne peut donc PAS re-soumettre un 2e
 *     feedback anonyme : la contrainte DB le borne, contrairement au seul drapeau
 *     de session (contournable par déconnexion/reconnexion).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_feedback_participants')) {
            return;
        }

        Schema::create('academy_feedback_participants', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            // Toujours l'étudiant AUTHENTIFIÉ (la route est sous auth) : on trace le
            // FAIT de participer, pas le contenu des réponses (anonymat préservé).
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Une seule participation par (item, étudiant) : borne le re-spam même
            // après reconnexion (la session ne suffit pas).
            $table->unique(['lesson_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_feedback_participants');
    }
};
