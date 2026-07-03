<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Pivot organisation <-> utilisateur provisionné par SCIM. C'EST la table
 * qui garantit l'isolation multi-tenant des endpoints /scim/v2/Users :
 * un jeton d'organisation A ne peut lister/lire/modifier que les
 * utilisateurs présents dans CETTE table pour son sso_configuration_id
 * (voir ScimUserController::provisionedUsersQuery). Un même utilisateur
 * PEUT être provisionné par plusieurs organisations (M:N) sans dupliquer
 * le compte Laravel sous-jacent.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_provisioned_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sso_configuration_id')->constrained('sso_configurations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sso_configuration_id', 'user_id'], 'sso_provisioned_users_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_provisioned_users');
    }
};
