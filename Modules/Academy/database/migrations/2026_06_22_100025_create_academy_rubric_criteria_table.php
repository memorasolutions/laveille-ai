<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-a - GRILLES D'ÉVALUATION (rubrics). Un CRITÈRE appartient à UN devoir
 * (assignment_id) ; chaque critère porte N niveaux (table séparée). Migration
 * ADDITIVE (guard hasTable). Un devoir SANS critère = comportement inchangé
 * (note manuelle libre). down() = drop de la SEULE table nouvelle.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_rubric_criteria')) {
            return;
        }

        Schema::create('academy_rubric_criteria', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('assignment_id');
            $table->string('description');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('assignment_id')
                ->references('id')
                ->on('academy_assignments')
                ->onDelete('cascade');

            $table->index(['assignment_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_rubric_criteria');
    }
};
