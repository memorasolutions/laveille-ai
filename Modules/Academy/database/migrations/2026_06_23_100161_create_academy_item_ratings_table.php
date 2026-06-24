<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F18 - NOTES / RATINGS (étoiles 1 à 5) sur un item de leçon (parité Moodle
 * « ratings »). Table ADDITIVE et idempotente (guard) : aucune autre table n'est
 * touchée, aucune dépendance. Un item sans note reste inchangé (rétrocompat).
 *
 *   - lesson_item_id : item noté (FK cascade).
 *   - user_id : auteur de la note (FK cascade).
 *   - value : note de 1 à 5 (bornée AUSSI côté serveur dans le contrôleur).
 *   - unique(lesson_item_id, user_id) : UNE note par utilisateur et par item ;
 *     re-noter MET À JOUR la même ligne (updateOrCreate), jamais de doublon.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_item_ratings')) {
            return;
        }

        Schema::create('academy_item_ratings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_item_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('value');
            $table->timestamps();

            $table->foreign('lesson_item_id')
                ->references('id')
                ->on('lesson_items')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['lesson_item_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_item_ratings');
    }
};
