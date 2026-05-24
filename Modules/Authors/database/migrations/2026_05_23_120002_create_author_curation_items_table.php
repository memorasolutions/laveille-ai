<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_curation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->onDelete('cascade');
            $table->string('url', 1024);
            $table->string('title', 500);
            $table->text('excerpt')->nullable();
            $table->string('thumbnail')->nullable();
            $table->text('note')->nullable();
            $table->json('tags')->nullable();
            $table->enum('source_type', ['manual', 'bookmarklet', 'rss'])->default('manual');
            $table->foreignId('rss_source_id')->nullable()->constrained('author_curation_items')->onDelete('set null');
            $table->foreignId('used_in_article_id')->nullable()->constrained('articles')->onDelete('set null');
            $table->timestamp('saved_at')->useCurrent();
            $table->timestamps();

            $table->index(['author_profile_id', 'saved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_curation_items');
    }
};
