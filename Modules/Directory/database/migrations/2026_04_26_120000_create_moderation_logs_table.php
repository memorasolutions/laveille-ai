<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('directory_tools')->cascadeOnDelete()->index();
            $table->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->string('action', 32);
            $table->string('old_status', 32)->nullable();
            $table->string('new_status', 32);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['tool_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};
