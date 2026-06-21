<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Cohortes / groupes d'apprenants (Phase C - gestion à l'échelle).
 * Une cohorte appartient à UN cours (ownership clair). Migration ADDITIVE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_cohorts')) {
            return;
        }

        Schema::create('academy_cohorts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('course_id');
            $table->string('name');
            $table->string('slug')->nullable();
            $table->timestamps();

            $table->foreign('course_id')
                ->references('id')
                ->on('courses')
                ->onDelete('cascade');

            $table->index('course_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_cohorts');
    }
};
