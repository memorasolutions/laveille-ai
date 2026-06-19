<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lesson_items')) {
            return;
        }

        Schema::create('lesson_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('lesson_id');
            $table->string('type'); // video|quiz|doc
            $table->string('title');
            $table->unsignedInteger('position')->default(0);
            $table->json('payload')->nullable();
            $table->unsignedInteger('estimated_minutes')->nullable();
            $table->boolean('is_required')->default(true);
            $table->string('external_ref')->nullable();
            $table->unsignedBigInteger('poster_media_id')->nullable();
            $table->timestamps();

            $table->foreign('lesson_id')
                ->references('id')
                ->on('lessons')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_items');
    }
};
