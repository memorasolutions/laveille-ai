<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('author_webmentions')) {
            return;
        }

        Schema::create('author_webmentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_post_id')->nullable()
                ->constrained('author_posts')->cascadeOnDelete();
            $table->string('target_url', 500);
            $table->string('source_url', 500);
            $table->string('source_author_name', 255)->nullable();
            $table->string('source_author_url', 500)->nullable();
            $table->text('source_excerpt')->nullable();
            $table->enum('type', ['mention', 'reply', 'like', 'repost', 'bookmark'])->default('mention');
            $table->timestamp('received_at');
            $table->timestamp('verified_at')->nullable();
            $table->tinyInteger('spam_score')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['author_post_id', 'verified_at'], 'aw_post_verified_idx');
            $table->unique(['author_post_id', 'source_url'], 'aw_post_source_uq');
            $table->index(['type'], 'aw_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_webmentions');
    }
};
