<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Prérequis de cours (Phase C / C4 - parcours guidé). Un cours peut exiger qu'un
 * ou plusieurs AUTRES cours soient complétés (100 %) avant inscription/accès.
 * Table de liaison « cours → cours prérequis ». Migration ADDITIVE et idempotente
 * (guard Schema::hasTable). FK en cascade : un cours supprimé retire ses liens.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_course_prerequisites')) {
            return;
        }

        Schema::create('academy_course_prerequisites', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->unsignedBigInteger('prerequisite_course_id');
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->foreign('prerequisite_course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            // Un même prérequis n'est jamais lié deux fois au même cours.
            $table->unique(['course_id', 'prerequisite_course_id'], 'course_prereq_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_course_prerequisites');
    }
};
