<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('author_push_subscriptions')) {
            return;
        }

        Schema::create('author_push_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_profile_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint', 500)->unique();
            $table->string('public_key', 255)->nullable();
            $table->string('auth_token', 255)->nullable();
            $table->string('content_encoding', 50)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('author_profile_id', 'aps_profile_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_push_subscriptions');
    }
};
