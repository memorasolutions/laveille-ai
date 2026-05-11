<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('concentre_builder_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('week_start');
            $table->date('week_end');
            $table->json('selected_news_ids')->nullable();
            $table->text('manual_urls')->nullable();
            $table->longText('generated_prompt');
            $table->timestamps();

            $table->index(['user_id', 'week_start']);
            $table->index('week_start');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('concentre_builder_runs');
    }
};
