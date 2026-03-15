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
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->string('consent_token', 64)->unique();
            $table->string('ip_hash', 64);
            $table->string('user_agent', 512)->nullable();
            $table->json('choices');
            $table->string('jurisdiction', 20);
            $table->string('policy_version', 20);
            $table->string('region_detected', 10)->nullable();
            $table->boolean('gpc_enabled')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['jurisdiction', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_consents');
    }
};
