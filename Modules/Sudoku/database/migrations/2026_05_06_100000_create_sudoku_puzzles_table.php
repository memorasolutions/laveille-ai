<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sudoku_puzzles', function (Blueprint $table) {
            $table->id();
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert', 'diabolical']);
            $table->date('date');
            $table->json('grid_init');
            $table->json('solution');
            $table->tinyInteger('clues_count');
            $table->integer('generation_time_ms')->nullable();
            $table->timestamps();

            $table->unique(['date', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sudoku_puzzles');
    }
};
