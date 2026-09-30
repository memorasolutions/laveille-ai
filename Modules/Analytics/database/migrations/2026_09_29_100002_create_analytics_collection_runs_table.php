<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('analytics_collection_runs')) {
            Schema::create('analytics_collection_runs', function (Blueprint $table) {
                $table->id();
                $table->string('source', 16); // 'ga4' | 'gsc'
                $table->date('collected_for');
                $table->timestamp('ran_at')->nullable();
                $table->string('status', 16); // 'success' | 'partial' | 'error' | 'skipped'
                $table->unsignedInteger('rows_upserted')->default(0);
                $table->text('message')->nullable();
                $table->timestamps();

                // Noms d'index explicites et courts (garde-fou 64 caractères MySQL).
                $table->unique(['source', 'collected_for'], 'collection_runs_source_date_unique');
                $table->index(['source', 'collected_for'], 'collection_runs_source_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_collection_runs');
    }
};
