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
        Schema::create('short_url_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('short_url_id')->constrained('short_urls')->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('referrer')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->timestamp('clicked_at')->useCurrent();

            $table->index(['short_url_id', 'clicked_at']);
            $table->index('country_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_url_clicks');
    }
};
