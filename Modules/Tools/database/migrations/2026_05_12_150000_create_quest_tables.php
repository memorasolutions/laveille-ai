<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quest_progress', function (Blueprint $table) {
            $table->id();
            $table->string('user_email')->index();
            $table->string('current_chapter', 64)->default('ch1-eveil-octopus');
            $table->json('completed_chapters')->nullable();
            $table->json('choices')->nullable();
            $table->json('badges')->nullable();
            $table->unsignedSmallInteger('streak_days')->default(0);
            $table->date('last_active_date')->nullable();
            $table->timestamps();
            $table->unique('user_email');
        });

        Schema::create('quest_magic_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('token', 64)->unique()->index();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quest_magic_tokens');
        Schema::dropIfExists('quest_progress');
    }
};
