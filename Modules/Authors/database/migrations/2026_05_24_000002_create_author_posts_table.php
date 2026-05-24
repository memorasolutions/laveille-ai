<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 191);
            $table->string('title', 255);
            $table->text('excerpt')->nullable();
            $table->longText('body_markdown');
            $table->longText('body_html')->nullable();
            $table->string('cover_image', 500)->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->enum('visibility', ['public', 'subscribers', 'premium'])->default('public');
            $table->json('tags')->nullable();
            $table->unsignedInteger('reading_time_minutes')->default(0);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['author_profile_id', 'slug'], 'ap_profile_slug_uq');
            $table->index(['author_profile_id', 'status', 'published_at'], 'ap_profile_status_pubat_idx');
            $table->index(['status', 'visibility'], 'ap_status_visibility_idx');
        });

        Schema::create('author_post_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->longText('body_markdown_snapshot');
            $table->string('change_summary', 191)->nullable();
            $table->timestamps();

            $table->index(['author_post_id', 'created_at'], 'apr_post_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_post_revisions');
        Schema::dropIfExists('author_posts');
    }
};
