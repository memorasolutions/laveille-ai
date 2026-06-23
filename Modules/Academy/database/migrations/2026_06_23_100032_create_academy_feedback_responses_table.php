<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * FEEDBACK - réponses à un sondage / questionnaire de rétroaction (item de leçon
 * « feedback », multi-questions, NON noté, type Moodle « Feedback »). Table ADDITIVE
 * et idempotente (guards) : aucune autre table n'est touchée, aucune dépendance.
 *
 *   - user_id NULLABLE : null = réponse ANONYME (aucun lien vers l'identité).
 *   - UNIQUE(lesson_item_id, user_id) : une réponse par étudiant POUR LES RÉPONSES
 *     NOMMÉES (upsert / modifiable). Les NULL étant distincts dans un index unique
 *     (SQLite + MySQL), plusieurs réponses anonymes coexistent sans contrainte ;
 *     le re-spam anonyme est borné par la session (cf. FeedbackService).
 *   - answers : JSON { index_question => valeur } (rating int, choice index, texte).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_feedback_responses')) {
            return;
        }

        Schema::create('academy_feedback_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            // NULLABLE : null = réponse anonyme (aucune identité enregistrée).
            $table->unsignedBigInteger('user_id')->nullable();
            // Réponses par question : { "0": 4, "1": 2, "2": "texte libre" }.
            $table->json('answers');
            $table->timestamps();

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Une réponse NOMMÉE par (item, étudiant) : le re-envoi MET À JOUR la même
            // ligne (upsert). Les réponses anonymes (user_id null) ne sont pas contraintes.
            $table->unique(['lesson_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_feedback_responses');
    }
};
