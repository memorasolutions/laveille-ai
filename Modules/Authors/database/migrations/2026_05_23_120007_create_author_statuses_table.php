<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->onDelete('cascade');
            $table->text('content');
            $table->enum('content_type', ['short', 'long'])->default('long');
            $table->string('image_url')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['author_profile_id', 'is_published', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_statuses');
    }
};
