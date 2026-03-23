<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directory_suggestions', function (Blueprint $table) {
            $table->unsignedInteger('votes_count')->default(0);
        });

        Schema::create('suggestion_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('suggestion_id')->constrained('directory_suggestions')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'suggestion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestion_votes');
        Schema::table('directory_suggestions', function (Blueprint $table) {
            $table->dropColumn('votes_count');
        });
    }
};
