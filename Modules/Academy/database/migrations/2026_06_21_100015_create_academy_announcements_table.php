<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Annonces aux inscrits d'un cours (Phase D / D3 - pilotage). Une annonce
 * appartient à UN cours (ownership clair) ; publiée (published_at non null) =
 * visible des inscrits actifs, sinon brouillon. Migration ADDITIVE (guard hasTable).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_announcements')) {
            return;
        }

        Schema::create('academy_announcements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->string('title');
            $table->text('body');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->foreign('author_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['course_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_announcements');
    }
};
