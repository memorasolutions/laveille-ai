<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Garde anti-rejeu SAML : chaque InResponseTo (id de l'AuthnRequest d'origine)
 * validé avec succès est enregistré ici. Une 2e assertion présentant le MÊME
 * InResponseTo pour la MÊME organisation est rejetée (rejeu). Purge périodique
 * possible via inresponseto_ttl_days (config sso.saml.inresponseto_ttl_days).
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sso_saml_replay_guards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sso_configuration_id')->constrained('sso_configurations')->cascadeOnDelete();
            $table->string('in_response_to');
            $table->string('assertion_id')->nullable();
            $table->timestamp('consumed_at');
            $table->timestamps();

            $table->unique(['sso_configuration_id', 'in_response_to'], 'sso_replay_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_saml_replay_guards');
    }
};
