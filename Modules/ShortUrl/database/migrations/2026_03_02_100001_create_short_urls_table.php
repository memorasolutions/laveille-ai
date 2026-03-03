<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('short_urls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained('short_url_domains')->nullOnDelete();
            $table->string('slug', 50)->unique();
            $table->text('original_url');
            $table->string('title', 255)->nullable();
            $table->string('password', 255)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_clicks')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('redirect_type')->default(302);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->json('tags')->nullable();
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'is_active']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('short_urls');
    }
};
