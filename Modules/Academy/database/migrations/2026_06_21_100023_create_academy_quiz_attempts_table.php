<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V1-b — HISTORIQUE DES TENTATIVES de quiz (socle additif, rétrocompat).
 *
 * 1 ligne PAR soumission de quiz (contrairement à `completions` qui est upsertée
 * et idempotente sur (user_id, lesson_item_id)). Prépare V1-c (méthode de notation :
 * meilleure / moyenne / 1re / dernière tentative) et l'analyse des échecs répétés.
 *
 * `course_id` est DÉNORMALISÉ (résolu à l'écriture via item→lesson→chapter) pour
 * scoper l'analytics sans jointures multiples. La table démarre VIDE : aucune
 * Completion existante n'est touchée.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_quiz_attempts')) {
            return;
        }

        Schema::create('academy_quiz_attempts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lesson_item_id');
            $table->unsignedBigInteger('course_id'); // dénormalisé (analytics par cours)
            $table->unsignedInteger('score');        // nb de bonnes réponses (compatible V1-c)
            $table->unsignedInteger('max_score');    // nb de questions du round
            $table->unsignedTinyInteger('percent')->default(0); // 0-100
            $table->boolean('passed')->default(false);
            $table->json('answers');                          // snapshot des réponses soumises
            $table->json('questions_snapshot')->nullable();   // snapshot du round joué (révision)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->index(['user_id', 'lesson_item_id']);
            $table->index('lesson_item_id');
            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_quiz_attempts');
    }
};
