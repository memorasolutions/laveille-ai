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
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->string('title')->nullable();
            $table->string('status')->default('ai_active');
            $table->string('model')->nullable();
            $table->text('system_prompt')->nullable();
            $table->json('context')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->decimal('cost_estimate', 10, 6)->default(0);
            $table->timestamps();
            $table->timestamp('closed_at')->nullable();

            $table->foreign('agent_id')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_conversations');
    }
};
