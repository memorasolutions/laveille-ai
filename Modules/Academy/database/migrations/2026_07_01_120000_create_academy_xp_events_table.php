<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Gamification moderne (XP / niveaux / classement de cohorte) — capacité LMS 2026.
 * Une ligne = un événement d'XP crédité (jamais retiré, l'historique fait foi).
 * IDEMPOTENCE / ANTI-TRICHE : la contrainte UNIQUE (user_id, source_type, source_id,
 * reason) garantit qu'une même action pédagogique (ex. compléter la même leçon,
 * réussir la même tentative de quiz) ne peut JAMAIS créditer l'XP plus d'une fois,
 * même en cas de rejeu/relance concurrente. `source_id` n'est PAS nullable (0 =
 * sentinelle « aucun modèle source », ex. bonus de série) afin que la contrainte
 * unique reste réellement exécutoire (NULL est distinct de NULL dans un index
 * unique MySQL/SQLite — voir migration academy_user_badges pour ce même piège).
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
        if (Schema::hasTable('academy_xp_events')) {
            return;
        }

        Schema::create('academy_xp_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            // Cours en contexte (null = XP hors contexte de cours, cas rare/futur).
            $table->unsignedBigInteger('course_id')->nullable();
            // Type de source polymorphe LÉGER (pas de morphs Eloquent : on ne stocke
            // jamais de FK réelle vers un autre module, seulement une étiquette + id).
            $table->string('source_type', 40);
            $table->unsignedBigInteger('source_id')->default(0);
            // Raison courte lisible (ex. lesson_completed, quiz_passed, course_completed,
            // streak_day_2026-07-01). Documentée dans GamificationService.
            $table->string('reason', 60);
            $table->integer('points');
            $table->timestamps();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();

            // Cœur de l'anti-triche : une même (user, source_type, source_id, reason)
            // ne peut apparaître qu'une seule fois.
            $table->unique(
                ['user_id', 'source_type', 'source_id', 'reason'],
                'academy_xp_events_unique'
            );

            // Lectures fréquentes : total XP d'un utilisateur (global ou par cours).
            $table->index(['user_id', 'course_id']);
            $table->index(['course_id', 'points']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_xp_events');
    }
};
