<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * F22 - LIENS compétence <-> cible. Une compétence peut être associée à un COURS
 * entier (course_id) ET/OU à des ITEMS de leçon précis (lesson_item_id). On modélise
 * la cible par DEUX colonnes nullables plutôt qu'un polymorphe (plus simple, FK réelles
 * + nettoyage en cascade fiable) : un lien porte EXACTEMENT une cible (course_id XOR
 * lesson_item_id), garanti côté application (CourseCompetencies).
 *
 * cascadeOnDelete : si la compétence, le cours ou l'item disparaît, le lien part avec
 * (aucun orphelin). Migration ADDITIVE guardée (hasTable). RÉTROCOMPAT : aucun lien =
 * comportement actuel inchangé. down() = drop de la seule table nouvelle.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_competency_links')) {
            return;
        }

        Schema::create('academy_competency_links', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('competency_id');
            // Cible : un COURS entier OU un item de leçon (exactement une des deux).
            $table->unsignedBigInteger('course_id')->nullable();
            $table->unsignedBigInteger('lesson_item_id')->nullable();
            $table->timestamps();

            $table->foreign('competency_id')->references('id')->on('academy_competencies')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
            $table->foreign('lesson_item_id')->references('id')->on('lesson_items')->cascadeOnDelete();

            // Anti-doublon : un même couple (compétence, cible) ne peut exister qu'une fois.
            $table->unique(['competency_id', 'course_id', 'lesson_item_id'], 'academy_comp_link_unique');
            $table->index(['course_id']);
            $table->index(['lesson_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_competency_links');
    }
};
