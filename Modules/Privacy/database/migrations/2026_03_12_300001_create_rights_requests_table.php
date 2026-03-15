<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rights_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('name', 255);
            $table->string('email', 255)->index();
            $table->string('request_type', 50);
            $table->text('description');
            $table->string('file_path', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->string('jurisdiction', 20);
            $table->timestamp('deadline_at');
            $table->timestamp('responded_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'deadline_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rights_requests');
    }
};
