<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(true)->index();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('tool_collection_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('tool_collections')->onDelete('cascade');
            $table->foreignId('tool_id')->constrained('directory_tools')->onDelete('cascade');
            $table->unsignedInteger('position')->default(0);
            $table->timestamp('added_at')->nullable();

            $table->unique(['collection_id', 'tool_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_collection_items');
        Schema::dropIfExists('tool_collections');
    }
};
