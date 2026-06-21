<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Remises d'un devoir (Phase E / E2 - évaluation). Une remise appartient à UN
 * devoir et UN utilisateur (unique(assignment_id,user_id) : une remise éditable
 * par étudiant). score null = non corrigé ; graded_at/graded_by remplis à la
 * correction. Migration ADDITIVE (guard hasTable).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_submissions')) {
            return;
        }

        Schema::create('academy_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->unsignedBigInteger('user_id');
            $table->text('body')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->unsignedBigInteger('graded_by')->nullable();
            $table->timestamps();

            $table->foreign('assignment_id')
                ->references('id')
                ->on('academy_assignments')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('graded_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['assignment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_submissions');
    }
};
