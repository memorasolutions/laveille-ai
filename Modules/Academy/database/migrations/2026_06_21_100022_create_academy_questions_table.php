<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Questions réutilisables de la banque (QB1, socle DONNÉES). Chaque question
 * appartient à une catégorie (cascade) et à un formateur propriétaire (owner_id).
 * Le payload JSON porte la structure spécifique au type (choix, bonnes réponses,
 * réponses acceptées, paires) ; QuestionBankService la traduit au format attendu
 * par QuizService::score(). Migration ADDITIVE (guard hasTable) ; INERTE jusqu'à QB2.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_questions')) {
            return;
        }

        Schema::create('academy_questions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('owner_id')->nullable();
            // Type fermé validé côté code (portable SQLite/MySQL) :
            //   mcq | truefalse | short | matching
            $table->string('type');
            $table->text('prompt');
            // Structure spécifique au type (choices/correct/accepted/pairs…).
            $table->json('payload');
            $table->text('explanation')->nullable();
            // facile | moyen | difficile (null = non classé).
            $table->string('difficulty')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('academy_question_categories')
                ->onDelete('cascade');

            $table->foreign('owner_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['category_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_questions');
    }
};
