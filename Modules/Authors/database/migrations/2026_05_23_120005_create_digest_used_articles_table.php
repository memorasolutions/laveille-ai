<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digest_used_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('week_number');
            $table->unsignedSmallInteger('year');
            $table->timestamp('used_at')->useCurrent();
            $table->decimal('popularity_score', 10, 2)->nullable();

            $table->unique(['article_id', 'week_number', 'year']);
            $table->index(['year', 'week_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digest_used_articles');
    }
};
