<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acronym_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->json('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('acronyms', function (Blueprint $table) {
            $table->id();
            $table->json('acronym');
            $table->json('full_name');
            $table->json('slug');
            $table->json('description')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->string('logo_url', 500)->nullable();
            $table->string('domain', 50)->default('education');
            $table->foreignId('acronym_category_id')
                ->nullable()
                ->constrained('acronym_categories')
                ->nullOnDelete();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['domain', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acronyms');
        Schema::dropIfExists('acronym_categories');
    }
};
