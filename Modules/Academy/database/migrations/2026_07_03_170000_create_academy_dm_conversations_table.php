<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Messagerie directe (DM) formateur ↔ apprenant — parité Moodle (LMS 2026).
 * Une conversation = un fil PRIVÉ entre exactement deux utilisateurs qui
 * partagent (au moment de la CRÉATION) une relation pédagogique commune sur
 * UN cours (formateur du cours ET apprenant inscrit à ce cours). `course_id`
 * est conservé pour traçabilité/contexte (affiché dans l'UI) mais n'est PAS
 * la seule preuve d'autorisation à l'exécution : chaque envoi de message
 * revérifie la relation en direct (voir DirectMessageConversation::canMessage()),
 * de sorte qu'une désinscription/retrait révoque l'accès immédiatement.
 *
 * user_one_id / user_two_id sont stockés triés (user_one_id < user_two_id) afin
 * qu'une contrainte UNIQUE empêche la création de deux fils dupliqués pour la
 * même paire d'utilisateurs sur le même cours.
 *
 * Migration ADDITIVE (guard hasTable), down() = drop strict.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_dm_conversations')) {
            return;
        }

        Schema::create('academy_dm_conversations', function (Blueprint $table): void {
            $table->id();
            // Cours en contexte au moment de la création (traçabilité UI seulement).
            $table->unsignedBigInteger('course_id');
            // Paire triée (user_one_id < user_two_id) : anti-doublon fiable.
            $table->unsignedBigInteger('user_one_id');
            $table->unsignedBigInteger('user_two_id');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();

            $table->foreign('user_one_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('user_two_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            // Un seul fil par paire d'utilisateurs et par cours.
            $table->unique(['course_id', 'user_one_id', 'user_two_id'], 'academy_dm_conversations_unique_pair');

            // Liste des conversations d'un utilisateur, triée par activité récente.
            $table->index(['user_one_id', 'last_message_at']);
            $table->index(['user_two_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_dm_conversations');
    }
};
