<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-b - CARNET DE NOTES PONDÉRÉ (gradebook Moodle). CATÉGORIES de notes d'un
 * cours (ex. « Quiz » 40 %, « Devoirs » 60 %). Une catégorie appartient à UN
 * cours (course_id) et porte un POIDS en pourcentage. Migration ADDITIVE (guard
 * hasTable). Un cours SANS catégorie = carnet INCHANGÉ (agrégation simple
 * actuelle). down() = drop de la SEULE table nouvelle (aucune perte de données
 * existantes).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_grade_categories')) {
            return;
        }

        Schema::create('academy_grade_categories', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            // Poids de la catégorie en % (les poids sont normalisés au calcul si
            // leur somme diffère de 100).
            $table->decimal('weight', 8, 2)->default(0);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->index(['course_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_grade_categories');
    }
};
