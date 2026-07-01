<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Test de positionnement adaptatif (CAT) - trace de chaque passage. Migration
 * ADDITIVE (guard Schema::hasTable, portabilité, ré-exécution sûre). Une ligne
 * par tentative (jamais de conflit/écrasement entre tentatives d'un même
 * apprenant) : idempotence naturelle par insertion, pas de mise à jour croisée.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_placement_attempts')) {
            return;
        }

        Schema::create('academy_placement_attempts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('course_id');

            // Trace des questions posées (question_id, difficulté utilisée, correct)
            // - utile au débogage, à l'audit pédagogique et à l'anti-répétition.
            $table->json('questions_asked');

            // Niveau estimé en fin de passage : 'faible'|'moyen'|'fort'. Nullable :
            // une tentative interrompue avant la fin n'a pas encore de niveau.
            $table->string('estimated_level')->nullable();

            $table->unsignedBigInteger('recommended_lesson_id')->nullable();

            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();

            $table->foreign('recommended_lesson_id')
                ->references('id')
                ->on('lessons')
                ->nullOnDelete();

            $table->index(['user_id', 'course_id'], 'academy_placement_user_course_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_placement_attempts');
    }
};
