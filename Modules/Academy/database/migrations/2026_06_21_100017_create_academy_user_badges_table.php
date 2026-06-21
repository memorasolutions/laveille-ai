<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Pivot des badges GAGNÉS (Phase E / E1). Une ligne = un badge décerné à un
 * utilisateur (optionnellement lié à un cours). La contrainte UNIQUE
 * (badge_id, user_id, course_id) garantit l'IDEMPOTENCE : ré-évaluer ne crée
 * jamais de doublon. Migration ADDITIVE (guard hasTable).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_user_badges')) {
            return;
        }

        Schema::create('academy_user_badges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('badge_id');
            $table->unsignedBigInteger('user_id');
            // Cours associé au badge si pertinent (course_completed) ; null pour les
            // badges globaux (first_certificate, lessons_completed cumulés…).
            $table->unsignedBigInteger('course_id')->nullable();
            $table->timestamp('awarded_at')->nullable();
            $table->timestamps();

            $table->foreign('badge_id')
                ->references('id')
                ->on('academy_badges')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->nullOnDelete();

            // Idempotence stricte. course_id nullable : SQLite et MySQL traitent
            // NULL comme distinct dans un index unique, donc les badges globaux
            // (course_id null) restent uniques par (badge_id, user_id) via le
            // garde-fou firstOrCreate du service.
            $table->unique(['badge_id', 'user_id', 'course_id'], 'academy_user_badges_unique');
            $table->index(['user_id', 'awarded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_user_badges');
    }
};
