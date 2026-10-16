<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fa_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('fa_boards')->cascadeOnDelete();
            $table->string('full_name');
            $table->string('display_label');
            $table->string('token_hash', 64)->unique();
            $table->string('status', 10)->default('pending');
            $table->unsignedInteger('joined_seq');
            $table->unsignedBigInteger('sort_key')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->index(['board_id', 'status']);
            $table->index(['board_id', 'sort_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_participants');
    }
};
