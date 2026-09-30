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
        if (! Schema::hasTable('analytics_ga4_daily')) {
            Schema::create('analytics_ga4_daily', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('url', 512);
                $table->unsignedInteger('sessions')->default(0);
                $table->unsignedInteger('active_users')->default(0);
                $table->unsignedInteger('screen_page_views')->default(0);
                $table->unsignedInteger('engaged_sessions')->default(0);
                $table->unsignedInteger('avg_engagement_time_seconds')->default(0);
                $table->unsignedInteger('conversions')->default(0);
                $table->unsignedBigInteger('content_id')->nullable();
                $table->string('content_type', 64)->nullable();
                $table->timestamps();

                // Noms d'index explicites et courts (garde-fou 64 caractères MySQL).
                $table->unique(['date', 'url'], 'ga4_daily_date_url_unique');
                $table->index(['url', 'date'], 'ga4_daily_url_date_idx');
                $table->index('content_id', 'ga4_daily_content_id_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_ga4_daily');
    }
};
