<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('newsletter_prompt_presets')) {
            return;
        }

        Schema::create('newsletter_prompt_presets', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->json('blocks');
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_prompt_presets');
    }
};
