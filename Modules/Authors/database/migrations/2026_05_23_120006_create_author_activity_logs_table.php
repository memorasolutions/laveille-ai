<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->onDelete('cascade');
            $table->enum('event_type', [
                'login',
                'draft_created',
                'article_published',
                'status_published',
                'image_uploaded',
                'reactivation_email_sent',
                'tier_changed',
                'custom_domain_configured'
            ]);
            $table->json('event_meta')->nullable();
            $table->timestamp('recorded_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->index(['author_profile_id', 'recorded_at']);
            $table->index('event_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_activity_logs');
    }
};
