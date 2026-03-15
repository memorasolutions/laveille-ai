<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'accepted', 'declined', 'completed'])->default('pending');
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('conversation_internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });

        Schema::create('canned_replies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('shortcut', 50)->nullable()->unique();
            $table->string('category', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('category');
            $table->index('is_active');
        });

        Schema::create('chat_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->string('disk', 50)->default('local');
            $table->string('path');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('message_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('ai_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at');

            $table->unique(['message_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_reads');
        Schema::dropIfExists('chat_attachments');
        Schema::dropIfExists('canned_replies');
        Schema::dropIfExists('conversation_internal_notes');
        Schema::dropIfExists('conversation_assignments');
    }
};
