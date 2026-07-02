<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Consumer LTI 1.3 (outils pédagogiques externes) — enregistrement des outils
 * branchés à l'Académie. Academy est TOUJOURS le CONSUMER (la plateforme qui
 * lance l'outil externe), jamais le TOOL PROVIDER : cette table ne décrit que
 * les outils tiers autorisés à être lancés depuis un item de leçon, avec les
 * paramètres OIDC/LTI nécessaires à l'échange (issuer, client_id, deployment_id,
 * URLs de login/jetons/JWKS). Migration ADDITIVE (guard hasTable), down() = drop strict.
 */

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academy_lti_tool_registrations')) {
            return;
        }

        Schema::create('academy_lti_tool_registrations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Émetteur (issuer) attribué par l'outil externe à SA plateforme d'origine.
            $table->string('issuer')->index();
            $table->string('client_id');
            $table->string('deployment_id');
            $table->string('auth_login_url');
            $table->string('auth_token_url');
            $table->string('jwks_url');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Un même triplet (issuer, client_id, deployment_id) identifie une seule
            // intégration : anti-doublon, cohérent avec le standard LTI 1.3.
            $table->unique(['issuer', 'client_id', 'deployment_id'], 'academy_lti_tool_registrations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academy_lti_tool_registrations');
    }
};
