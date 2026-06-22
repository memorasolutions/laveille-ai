<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-b - AFFECTATION d'un ITEM de note (quiz ou devoir) à une CATÉGORIE + son
 * POIDS au sein de la catégorie. Représentation la plus PROPRE : une table pivot
 * légère et UNIFIÉE (item_type = quiz|assignment) plutôt que de muter le payload
 * JSON des items quiz ET d'ajouter une colonne sur les devoirs (deux mécanismes
 * de stockage distincts). Une seule table = un seul point de lecture (DRY pour
 * GradebookService), 100 % additif, anti-IDOR aisé (tout scopé course_id).
 *
 *  - item_type : 'quiz' (item_id = academy_lesson_items.id) | 'assignment'
 *    (item_id = academy_assignments.id).
 *  - grade_category_id : NULLABLE (set null si la catégorie est supprimée →
 *    l'item devient « non classé », JAMAIS supprimé).
 *  - weight : poids relatif de l'item DANS sa catégorie (moyenne pondérée).
 *
 * Un item SANS ligne ici = non classé → ignoré du calcul pondéré (rétrocompat).
 * Migration ADDITIVE (guard hasTable) ; down() = drop de la SEULE table nouvelle.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_grade_items')) {
            return;
        }

        Schema::create('academy_grade_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('item_type'); // 'quiz' | 'assignment'
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('grade_category_id')->nullable();
            $table->decimal('weight', 8, 2)->default(1);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->foreign('grade_category_id')
                ->references('id')
                ->on('academy_grade_categories')
                ->onDelete('set null');

            // Un item donné n'a qu'une seule affectation par cours.
            $table->unique(['course_id', 'item_type', 'item_id']);
            $table->index(['course_id', 'grade_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_grade_items');
    }
};
