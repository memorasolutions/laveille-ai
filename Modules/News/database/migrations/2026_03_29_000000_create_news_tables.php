<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url')->unique();
            $table->string('category')->nullable();
            $table->string('language')->default('fr');
            $table->boolean('active')->default(true);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('news_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('guid')->unique();
            $table->string('url')->index();
            $table->text('description');
            $table->text('summary')->nullable();
            $table->string('image_url')->nullable();
            $table->string('author')->nullable();
            $table->timestamp('pub_date');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_articles');
        Schema::dropIfExists('news_sources');
    }
};
