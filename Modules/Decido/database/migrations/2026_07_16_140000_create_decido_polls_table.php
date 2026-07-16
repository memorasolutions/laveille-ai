<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decido_polls', function (Blueprint $table) {
            $table->id();
            $table->string('public_id', 12)->unique();
            $table->string('custom_slug')->nullable()->unique();
            $table->string('admin_token_hash', 255);
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('type', 20);
            $table->string('vote_mode', 30);
            $table->string('timezone', 60)->default('America/Toronto');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->time('range_start_time')->nullable();
            $table->time('range_end_time')->nullable();
            $table->unsignedInteger('step_minutes')->nullable();
            $table->unsignedBigInteger('final_option_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('public_id');
            $table->index('custom_slug');
            $table->index(['creator_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decido_polls');
    }
};
