<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decido_poll_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poll_id')->constrained('decido_polls')->cascadeOnDelete();
            $table->string('label', 255);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['poll_id', 'sort_order']);
        });

        Schema::table('decido_polls', function (Blueprint $table) {
            $table->foreign('final_option_id')
                ->references('id')
                ->on('decido_poll_options')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('decido_polls', function (Blueprint $table) {
            $table->dropForeign(['final_option_id']);
        });

        Schema::dropIfExists('decido_poll_options');
    }
};
