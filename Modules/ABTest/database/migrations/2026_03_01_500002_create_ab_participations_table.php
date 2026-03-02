<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ab_participations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('ab_experiments')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->string('variant');
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['experiment_id', 'user_id']);
            $table->index(['experiment_id', 'session_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ab_participations');
    }
};
