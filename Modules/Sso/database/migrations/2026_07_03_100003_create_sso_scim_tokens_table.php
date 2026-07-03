<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Jeton Bearer SCIM par organisation — même principe que
 * Laravel\Sanctum\PersonalAccessToken (le jeton en clair n'est JAMAIS stocké,
 * seul son hash SHA-256 l'est ; comparaison en temps constant via hash_equals
 * — voir Modules\Sso\Services\ScimTokenService).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_scim_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sso_configuration_id')->constrained('sso_configurations')->cascadeOnDelete();
            $table->string('name');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_scim_tokens');
    }
};
