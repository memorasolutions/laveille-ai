<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sudoku_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('puzzle_id')->constrained('sudoku_puzzles')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('pseudo', 30);
            $table->string('ip_hash', 64);
            $table->string('country', 2)->nullable();
            $table->integer('time_seconds');
            $table->tinyInteger('hints_used')->default(0);
            $table->tinyInteger('errors_count')->default(0);
            $table->integer('score');
            $table->timestamp('completed_at');
            $table->boolean('is_published_in_leaderboard')->default(true);
            $table->timestamps();

            $table->index('puzzle_id');
            $table->index('user_id');
            $table->index('completed_at');
            $table->index('score');
            $table->index('country');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sudoku_scores');
    }
};
