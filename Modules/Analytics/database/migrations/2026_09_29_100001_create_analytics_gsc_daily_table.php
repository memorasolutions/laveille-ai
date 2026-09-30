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
        if (! Schema::hasTable('analytics_gsc_daily')) {
            Schema::create('analytics_gsc_daily', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('url', 512);
                $table->unsignedInteger('clicks')->default(0);
                $table->unsignedInteger('impressions')->default(0);
                $table->decimal('ctr', 7, 4)->default(0);
                $table->decimal('position', 7, 2)->default(0);
                $table->unsignedBigInteger('content_id')->nullable();
                $table->string('content_type', 64)->nullable();
                $table->timestamps();

                // Noms d'index explicites et courts (garde-fou 64 caractères MySQL).
                $table->unique(['date', 'url'], 'gsc_daily_date_url_unique');
                $table->index(['url', 'date'], 'gsc_daily_url_date_idx');
                $table->index('content_id', 'gsc_daily_content_id_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_gsc_daily');
    }
};
