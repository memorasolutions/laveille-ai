<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_screenshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('directory_tool_id')->constrained('directory_tools')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->string('caption', 255)->nullable();
            $table->boolean('is_approved')->default(false);
            $table->unsignedInteger('votes_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_screenshots');
    }
};
