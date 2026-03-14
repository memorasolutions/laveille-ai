<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_knowledge_urls', function (Blueprint $table) {
            $table->id();
            $table->string('url');
            $table->string('label');
            $table->string('hidden_source_name')->nullable(); // nom à ne jamais mentionner
            $table->boolean('robots_allowed')->default(false);
            $table->string('scrape_status')->default('pending'); // pending, scraping, completed, failed, robots_blocked
            $table->string('scrape_frequency')->default('weekly'); // manual, daily, weekly, monthly
            $table->timestamp('last_scraped_at')->nullable();
            $table->unsignedInteger('pages_scraped')->default(0);
            $table->unsignedInteger('max_pages')->default(50);
            $table->text('scrape_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamps();

            $table->index('is_active');
            $table->index('scrape_status');
            $table->index('tenant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_knowledge_urls');
    }
};
