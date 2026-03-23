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
        Schema::create('directory_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('directory_tags', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->timestamps();
        });

        Schema::create('directory_tools', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('description')->nullable();
            $table->json('short_description')->nullable();
            $table->string('url')->nullable();
            $table->string('logo')->nullable();
            $table->string('pricing')->default('free'); // free, freemium, paid, open_source, enterprise
            $table->string('status')->default('published'); // published, pending, rejected
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('directory_category_tool', function (Blueprint $table) {
            $table->foreignId('directory_category_id')->constrained('directory_categories')->cascadeOnDelete();
            $table->foreignId('directory_tool_id')->constrained('directory_tools')->cascadeOnDelete();
            $table->primary(['directory_category_id', 'directory_tool_id']);
        });

        Schema::create('directory_tag_tool', function (Blueprint $table) {
            $table->foreignId('directory_tag_id')->constrained('directory_tags')->cascadeOnDelete();
            $table->foreignId('directory_tool_id')->constrained('directory_tools')->cascadeOnDelete();
            $table->primary(['directory_tag_id', 'directory_tool_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_tag_tool');
        Schema::dropIfExists('directory_category_tool');
        Schema::dropIfExists('directory_tools');
        Schema::dropIfExists('directory_tags');
        Schema::dropIfExists('directory_categories');
    }
};
