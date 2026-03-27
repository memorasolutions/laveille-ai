<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Colonnes streak sur users
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_active_at')->nullable()->after('remember_token');
            $table->unsignedSmallInteger('streak_days')->default(0)->after('last_active_at');
            $table->date('streak_last_date')->nullable()->after('streak_days');
        });

        // Table log de réputation (pour leaderboard mensuel)
        Schema::create('reputation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('points');
            $table->string('reason', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reputation_logs');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['last_active_at', 'streak_days', 'streak_last_date']);
        });
    }
};
