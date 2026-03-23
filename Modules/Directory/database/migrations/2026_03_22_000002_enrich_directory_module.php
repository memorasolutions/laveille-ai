<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_tools', function (Blueprint $table) {
            $table->json('how_to_use')->nullable();
            $table->json('core_features')->nullable();
            $table->json('use_cases')->nullable();
            $table->json('faq')->nullable();
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->string('screenshot', 500)->nullable();
            $table->string('website_type', 50)->default('website');
            $table->unsignedSmallInteger('launch_year')->nullable();
            $table->json('target_audience')->nullable();
        });

        Schema::create('directory_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('directory_tool_id')->constrained('directory_tools')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title');
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'directory_tool_id']);
        });

        Schema::create('directory_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('directory_tool_id')->constrained('directory_tools')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('directory_discussions')->cascadeOnDelete();
            $table->text('body');
            $table->unsignedInteger('upvotes')->default(0);
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });

        Schema::create('directory_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('directory_tool_id')->constrained('directory_tools')->cascadeOnDelete();
            $table->string('url', 500);
            $table->string('title');
            $table->string('type', 50)->default('article');
            $table->string('language', 5)->default('fr');
            $table->string('thumbnail', 500)->nullable();
            $table->boolean('is_approved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_resources');
        Schema::dropIfExists('directory_discussions');
        Schema::dropIfExists('directory_reviews');

        Schema::table('directory_tools', function (Blueprint $table) {
            $table->dropColumn([
                'how_to_use', 'core_features', 'use_cases', 'faq',
                'pros', 'cons', 'screenshot', 'website_type',
                'launch_year', 'target_audience',
            ]);
        });
    }
};
