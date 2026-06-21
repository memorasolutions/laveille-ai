<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Pivot membres ↔ cohorte (Phase C). Un membre est un inscrit du cours de la
 * cohorte. Migration ADDITIVE.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_cohort_user')) {
            return;
        }

        Schema::create('academy_cohort_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('cohort_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('cohort_id')
                ->references('id')
                ->on('academy_cohorts')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->unique(['cohort_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_cohort_user');
    }
};
