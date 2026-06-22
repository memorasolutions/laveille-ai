<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * V2-a - GRILLES D'ÉVALUATION (rubrics). Un NIVEAU appartient à UN critère
 * (criterion_id) : libellé + points (>=0). À la correction, le formateur choisit
 * UN niveau par critère ; la note = somme des points choisis (mise à l'échelle
 * sur max_points du devoir). Migration ADDITIVE (guard hasTable).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_rubric_levels')) {
            return;
        }

        Schema::create('academy_rubric_levels', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('criterion_id');
            $table->string('description');
            $table->unsignedInteger('points')->default(0);
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('criterion_id')
                ->references('id')
                ->on('academy_rubric_criteria')
                ->onDelete('cascade');

            $table->index(['criterion_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_rubric_levels');
    }
};
