<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('author_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable()->constrained('author_comments')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 80)->nullable();
            $table->string('author_email', 191)->nullable();
            $table->text('body');
            $table->json('reactions')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedTinyInteger('spam_score')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('flagged_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['author_profile_id', 'approved_at'], 'ac_profile_approved_idx');
            $table->index('parent_id', 'ac_parent_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_comments');
    }
};
