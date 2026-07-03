<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Une ligne par organisation cliente ayant activé le SSO SAML 2.0. Le SP
 * (ce site) peut servir plusieurs IdP clients simultanément (multi-tenant).
 * FK vers Modules\Tenancy\Models\Tenant EN MOU (nullable, sans contrainte
 * FK dure) : le module Sso ne dépend PAS que Tenancy soit activé — voir
 * Modules\Sso\Models\SsoConfiguration::tenant() (relation "soft").
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_configurations', function (Blueprint $table) {
            $table->id();

            // Identifiant d'organisation — texte libre unique (slug), portable
            // même si Modules\Tenancy est désactivé. Optionnellement rattaché
            // à un tenant réel si le module Tenancy est actif (tenant_id nullable,
            // AUCUNE contrainte FK dure pour rester indépendant du module).
            $table->string('organization_slug')->unique();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->string('name');
            $table->boolean('is_active')->default(true);

            // --- Service Provider (SP) — ce site ---
            $table->string('sp_entity_id')->nullable();

            // --- Identity Provider (IdP) — fourni par le client ---
            $table->string('idp_entity_id');
            $table->string('idp_sso_url');
            $table->text('idp_x509_cert');

            // Mapping attribut SAML -> champ Laravel (ex. {"email":"mail","name":"displayName"}).
            $table->json('attribute_mapping')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_configurations');
    }
};
