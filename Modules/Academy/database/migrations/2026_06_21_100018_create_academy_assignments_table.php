<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Devoirs (assignments) d'un cours (Phase E / E2 - évaluation). Un devoir
 * appartient à UN cours (ownership clair) et peut être rattaché à une leçon
 * (optionnel) ou au cours entier. is_published = false → brouillon (jamais
 * visible d'un étudiant). Migration ADDITIVE (guard hasTable).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_assignments')) {
            return;
        }

        Schema::create('academy_assignments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('lesson_id')->nullable();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->unsignedInteger('max_points')->default(100);
            $table->timestamp('due_at')->nullable();
            $table->boolean('is_published')->default(false);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->foreign('lesson_id')
                ->references('id')
                ->on('lessons')
                ->nullOnDelete();

            $table->index(['course_id', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_assignments');
    }
};
