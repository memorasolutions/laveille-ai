<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_image_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->onDelete('cascade');
            $table->string('original_path', 500);
            $table->enum('format', ['avif', 'webp', 'jpg', 'png']);
            $table->integer('size_width');
            $table->integer('size_height');
            $table->unsignedInteger('file_size_bytes');
            $table->string('variant_path', 500);
            $table->boolean('is_open_graph')->default(false);
            $table->boolean('is_twitter_card')->default(false);
            $table->text('alt_text')->nullable();
            $table->timestamps();

            $table->index('author_profile_id');
            $table->index('format');
            $table->index('is_open_graph');
            $table->index('is_twitter_card');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_image_variants');
    }
};
