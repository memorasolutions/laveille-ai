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
        Schema::create('email_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger_type', 50)->index();
            $table->json('trigger_config')->nullable();
            $table->string('status', 20)->default('draft')->index();
            if (Schema::hasTable('tenants')) {
                $table->foreignId('tenant_id')->nullable()->constrained('tenants')->nullOnDelete();
            } else {
                $table->unsignedBigInteger('tenant_id')->nullable();
            }
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_workflows');
    }
};
