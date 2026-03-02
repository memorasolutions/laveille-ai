<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
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
        Schema::create('workflow_step_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('workflow_enrollments')->cascadeOnDelete();
            $table->foreignId('step_id')->constrained('workflow_steps')->cascadeOnDelete();
            $table->string('status', 20)->default('waiting');
            $table->json('metadata')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_step_logs');
    }
};
