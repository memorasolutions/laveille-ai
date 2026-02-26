<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('excerpt', 500)->nullable();
            $table->string('status')->default('draft');
            $table->json('meta')->nullable();
            $table->integer('revision_number')->default(1);
            $table->string('summary')->nullable();
            $table->timestamps();

            $table->index(['article_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_revisions');
    }
};
