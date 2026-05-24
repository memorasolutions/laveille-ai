<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->onDelete('cascade');
            $table->json('llama_guard_score')->nullable();
            $table->json('gpt_oss_score')->nullable();
            $table->json('local_rules_flags')->nullable();
            $table->text('claude_haiku_review')->nullable();
            $table->enum('final_status', ['approved', 'flagged', 'rejected'])->default('approved');
            $table->timestamp('alert_sent_at')->nullable();
            $table->string('alert_recipient')->nullable();
            $table->timestamp('reviewed_by_admin_at')->nullable();
            $table->enum('admin_action', ['approved', 'depublished', 'author_banned'])->nullable();
            $table->timestamps();

            $table->index('final_status');
            $table->index('article_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_moderation_logs');
    }
};
