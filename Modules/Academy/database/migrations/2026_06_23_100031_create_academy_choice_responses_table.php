<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * CHOICE - réponses au sondage (vote) d'un item de leçon « choice ». Table ADDITIVE
 * et idempotente (guards) : un vote par étudiant et par item (contrainte UNIQUE),
 * modifiable (upsert). Aucune autre table n'est touchée ; aucune dépendance.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_choice_responses')) {
            return;
        }

        Schema::create('academy_choice_responses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->unsignedBigInteger('user_id');
            // Indices (entiers) des options choisies, dans l'ordre des options du payload.
            $table->json('choices');
            $table->timestamps();

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Un seul vote par (item, étudiant) - le re-vote MET À JOUR la même ligne.
            $table->unique(['lesson_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_choice_responses');
    }
};
