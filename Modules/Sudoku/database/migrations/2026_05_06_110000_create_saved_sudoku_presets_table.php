<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_sudoku_presets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('pseudo')->nullable();
            $table->foreignId('puzzle_id')->constrained('sudoku_puzzles')->cascadeOnDelete();
            $table->json('grid_state');
            $table->integer('time_elapsed')->default(0);
            $table->integer('hints_used')->default(0);
            $table->integer('errors_count')->default(0);
            $table->timestamp('last_saved_at');
            $table->timestamps();

            $table->index(['user_id', 'puzzle_id']);
            $table->index(['pseudo', 'puzzle_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_sudoku_presets');
    }
};
