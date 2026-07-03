<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Métadonnées SP + initiation du login SAML.
 *
 * Prouve que :
 *  - GET /sso/saml/metadata expose un XML de métadonnées SP valide ;
 *  - GET /sso/saml/login?org=slug redirige vers l'URL SSO de l'IdP
 *    configuré (redirection externe, pas une 200) ;
 *  - une organisation inconnue/inactive renvoie 404 sans fuite d'info.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sso\Models\SsoConfiguration;

uses(RefreshDatabase::class);
uses(\Modules\Sso\Tests\Concerns\SkipsWhenSsoDisabled::class);

beforeEach(function (): void {
    test()->skipIfSsoModuleDisabled();
    config(['sso.enabled' => true]);
});

it('expose des métadonnées SP XML valides sur GET /sso/saml/metadata', function (): void {
    $response = $this->get(route('sso.saml.metadata'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/xml');
    expect($response->getContent())->toContain('<md:EntityDescriptor');
    expect($response->getContent())->toContain('<md:SPSSODescriptor');
});

it('expose des métadonnées SP spécifiques à une organisation via ?org=slug', function (): void {
    $configuration = SsoConfiguration::factory()->create(['organization_slug' => 'metadata-org']);

    $response = $this->get(route('sso.saml.metadata', ['org' => 'metadata-org']));

    $response->assertOk();
    expect($response->getContent())->toContain($configuration->sp_entity_id ?: config('sso.saml.sp_entity_id'));
});

it('répond 404 sur métadonnées d\'une organisation inconnue', function (): void {
    $this->get(route('sso.saml.metadata', ['org' => 'organisation-inexistante']))->assertNotFound();
});

it('redirige vers l\'URL SSO de l\'IdP configuré sur GET /sso/saml/login', function (): void {
    $configuration = SsoConfiguration::factory()->create([
        'organization_slug' => 'login-org',
        'idp_sso_url' => 'https://idp.login-org.example.com/sso-endpoint',
    ]);

    $response = $this->get(route('sso.saml.login', ['org' => 'login-org']));

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('idp.login-org.example.com');
    expect($response->headers->get('Location'))->toContain('SAMLRequest=');
});

it('répond 400 sur GET /sso/saml/login sans paramètre org', function (): void {
    $this->get(route('sso.saml.login'))->assertStatus(400);
});

it('répond 404 sur GET /sso/saml/login pour une organisation inactive', function (): void {
    SsoConfiguration::factory()->create(['organization_slug' => 'inactive-org', 'is_active' => false]);

    $this->get(route('sso.saml.login', ['org' => 'inactive-org']))->assertNotFound();
});
