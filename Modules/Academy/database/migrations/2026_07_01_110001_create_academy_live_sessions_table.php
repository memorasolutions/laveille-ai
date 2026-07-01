<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Séances en direct / visioconférence natives (capacité LMS 2026).
 * Une séance est rattachée à un cours (course_id) et, optionnellement, à une
 * cohorte (cohort_id nullable). MVP « par lien » : le formateur colle l'URL de
 * sa réunion (join_url) ; le fournisseur (provider) par défaut est Google Meet.
 *
 * Migration ADDITIVE, gardée par Schema::hasTable (portabilité, ré-exécution
 * sûre). down() = inverse exact (drop de la table).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_live_sessions')) {
            return;
        }

        Schema::create('academy_live_sessions', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('cohort_id')->nullable();

            $table->string('title');
            $table->text('description')->nullable();

            // Fournisseur de visioconférence (défaut Google Meet). MVP par lien.
            $table->enum('provider', ['meet', 'zoom', 'teams', 'other'])->default('meet');
            $table->string('join_url', 2048);

            // Heures stockées en UTC (Carbon), affichées Québec d'abord côté vue.
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Requête principale : séances (à venir/passées) d'un cours par date.
            $table->index(['course_id', 'starts_at'], 'academy_live_course_starts_idx');

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->cascadeOnDelete();

            $table->foreign('cohort_id')
                ->references('id')
                ->on('academy_cohorts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_live_sessions');
    }
};
