<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * SRS (répétition espacée) - table des cartes de révision par apprenant.
 * Chaque carte porte son propre état SM-2 (ease_factor, interval_days,
 * repetitions, due_at) : pas de table de reviews séparée, l'algorithme SM-2
 * ne dépend que de l'état courant + la qualité de rappel. Migration ADDITIVE,
 * gardée par Schema::hasTable (portabilité, ré-exécution sûre).
 *
 * Source polymorphe (source_type + source_id) : une carte pointe vers un
 * LessonItem (concept/quiz) ; le champ polymorphe permet d'étendre plus tard
 * à d'autres sources (Question de banque) sans nouvelle migration.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_srs_cards')) {
            return;
        }

        Schema::create('academy_srs_cards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id');

            // Source polymorphe de la carte (ex. LessonItem, extensible à Question).
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');

            // Contenu figé de la carte (recto/verso) - indépendant de la source
            // (la source peut être éditée/supprimée sans casser la révision).
            $table->text('front');
            $table->text('back')->nullable();

            // État SM-2. Défauts = première programmation (nouvelle carte, due maintenant).
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->unsignedInteger('interval_days')->default(0);
            $table->unsignedInteger('repetitions')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();

            $table->timestamps();

            // Idempotence de la génération : une seule carte par (user, source).
            $table->unique(['user_id', 'source_type', 'source_id'], 'academy_srs_card_unique');

            // File des cartes dues d'un utilisateur (requête principale du réviseur).
            $table->index(['user_id', 'due_at'], 'academy_srs_user_due_idx');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();

            $table->foreign('lesson_id')
                ->references('id')
                ->on('lessons')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_srs_cards');
    }
};
