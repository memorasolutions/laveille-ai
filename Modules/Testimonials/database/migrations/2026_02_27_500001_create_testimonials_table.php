<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('author_name', 255);
            $table->string('author_title', 255)->nullable();
            $table->string('author_avatar', 2048)->nullable();
            $table->text('content');
            $table->tinyInteger('rating')->default(5);
            $table->integer('order')->default(0);
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index(['is_approved', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
