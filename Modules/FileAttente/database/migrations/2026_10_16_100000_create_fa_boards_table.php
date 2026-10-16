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
        Schema::create('fa_boards', function (Blueprint $table) {
            $table->id();
            $table->string('manage_token_hash', 64)->unique();
            $table->string('display_token_hash', 64)->unique();
            $table->string('title')->nullable();
            $table->unsignedInteger('version')->default(0);
            $table->string('mask_mode', 10)->default('initial');
            $table->string('scan_mode', 10)->default('borne');
            $table->boolean('allow_self_leave')->default(true);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('purge_after');
            $table->timestamps();
            $table->index('purge_after');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fa_boards');
    }
};
