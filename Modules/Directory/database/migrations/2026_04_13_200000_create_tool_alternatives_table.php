<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tool_alternatives', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tool_id');
            $table->unsignedBigInteger('alternative_tool_id');
            $table->unsignedTinyInteger('relevance_score')->default(50);
            $table->enum('source', ['auto', 'manual'])->default('auto');
            $table->timestamps();

            $table->foreign('tool_id')
                ->references('id')
                ->on('directory_tools')
                ->onDelete('cascade');

            $table->foreign('alternative_tool_id')
                ->references('id')
                ->on('directory_tools')
                ->onDelete('cascade');

            $table->unique(['tool_id', 'alternative_tool_id']);
            $table->index('tool_id');
            $table->index('alternative_tool_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_alternatives');
    }
};
