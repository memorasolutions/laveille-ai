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
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('moderator_id')->nullable();
            $table->string('action', 32);
            $table->string('old_status', 32)->nullable();
            $table->string('new_status', 32);
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('tool_id', 'moderation_logs_tool_id_fk')
                ->references('id')
                ->on('directory_tools')
                ->cascadeOnDelete();

            $table->foreign('moderator_id', 'moderation_logs_moderator_id_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['tool_id', 'created_at'], 'moderation_logs_tool_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_logs');
    }
};
