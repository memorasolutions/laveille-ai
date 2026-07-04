<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Catégories de cours (taxonomie simple, parité Moodle « catégories de cours »).
 * Un cours appartient à 0 ou 1 catégorie (voir migration suivante qui ajoute
 * courses.category_id). Gestion CRUD réservée à academy.manage (voir
 * CourseCategoryManager) ; les formateurs choisissent une catégorie existante
 * pour leur cours mais n'en créent/suppriment aucune (taxonomie partagée
 * site-wide, évite les doublons/conflits entre formateurs).
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
        if (Schema::hasTable('academy_course_categories')) {
            return;
        }

        Schema::create('academy_course_categories', function (Blueprint $table): void {
            $table->id();

            $table->string('name', 120);
            $table->string('slug', 140)->unique();

            // Couleur hexadécimale optionnelle (ex. #064E5A), validée serveur au format
            // #RGB/#RRGGBB dans CourseCategoryManager. Icône : emoji ou courte étiquette.
            $table->string('color', 7)->nullable();
            $table->string('icon', 60)->nullable();

            // Ordre d'affichage manuel (liste admin + filtre public), le nom départage
            // les égalités.
            $table->unsignedInteger('position')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_course_categories');
    }
};
