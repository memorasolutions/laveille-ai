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
        Schema::create('proactive_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('event_type', 50)->index();
            $table->json('conditions')->nullable();
            $table->text('message');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('delay_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proactive_triggers');
    }
};
