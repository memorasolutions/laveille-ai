<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
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
        Schema::create('channels', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->index();
            $table->string('name', 255);
            $table->json('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('inbound_secret', 100)->nullable()->unique();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('channel_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained('channels')->cascadeOnDelete();
            $table->string('external_id', 255)->nullable();
            $table->string('external_thread_id', 255)->nullable();
            $table->string('direction', 10);
            $table->string('status', 20)->default('received');
            $table->string('subject', 255)->nullable();
            $table->text('body');
            $table->string('sender', 255)->nullable();
            $table->string('recipient', 255)->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained('ai_conversations')->nullOnDelete();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->unique(['channel_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('channel_messages');
        Schema::dropIfExists('channels');
    }
};
