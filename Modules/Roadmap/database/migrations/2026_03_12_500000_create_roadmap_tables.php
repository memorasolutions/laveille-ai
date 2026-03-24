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
        Schema::create('roadmap_boards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true);
            $table->string('color', 7)->default('#6366f1');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('roadmap_ideas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('roadmap_boards')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('description');
            $table->string('status', 20)->default('under_review')->index();
            $table->string('category', 100)->nullable()->index();
            $table->unsignedInteger('vote_count')->default(0);
            $table->unsignedInteger('comment_count')->default(0);
            $table->boolean('pinned')->default(false);
            $table->foreignId('merged_into_id')->nullable()->constrained('roadmap_ideas')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['board_id', 'slug']);
        });

        Schema::create('roadmap_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('roadmap_ideas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['idea_id', 'user_id']);
        });

        Schema::create('roadmap_idea_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('idea_id')->constrained('roadmap_ideas')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('is_official')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roadmap_idea_comments');
        Schema::dropIfExists('roadmap_votes');
        Schema::dropIfExists('roadmap_ideas');
        Schema::dropIfExists('roadmap_boards');
    }
};
