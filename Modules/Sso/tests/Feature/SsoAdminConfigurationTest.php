<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Administration des configurations SSO (CRUD + émission de
 * jetons SCIM), gâtée can('sso.manage').
 *
 * Prouve que :
 *  - un utilisateur SANS la permission sso.manage reçoit 403 ;
 *  - un utilisateur AVEC la permission peut créer une configuration et
 *    émettre un jeton SCIM (retourné en clair UNE seule fois) ;
 *  - le drapeau sso.enabled=false (défaut) fait répondre 404 même à un
 *    utilisateur autorisé (défense en profondeur).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sso\Models\SsoConfiguration;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);
uses(\Modules\Sso\Tests\Concerns\SkipsWhenSsoDisabled::class);

beforeEach(function (): void {
    test()->skipIfSsoModuleDisabled();
    config(['sso.enabled' => true]);
});

function ssoAdminUserWithPermission(): User
{
    $user = User::factory()->create();
    $permission = Permission::firstOrCreate(['name' => 'sso.manage', 'guard_name' => 'web']);
    $user->givePermissionTo($permission);

    return $user;
}

it('refuse (403) un utilisateur sans la permission sso.manage', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/admin/sso/configurations')
        ->assertForbidden();
});

it('répond 404 sur les routes admin quand sso.enabled est désactivé, même pour un utilisateur autorisé', function (): void {
    config(['sso.enabled' => false]);
    $user = ssoAdminUserWithPermission();

    $this->actingAs($user)
        ->getJson('/admin/sso/configurations')
        ->assertNotFound();
});

it('permet à un utilisateur autorisé de créer une configuration SSO', function (): void {
    $user = ssoAdminUserWithPermission();

    $response = $this->actingAs($user)->postJson('/admin/sso/configurations', [
        'organization_slug' => 'nouvelle-org',
        'name' => 'Nouvelle Organisation',
        'idp_entity_id' => 'https://idp.nouvelle-org.example.com/entity',
        'idp_sso_url' => 'https://idp.nouvelle-org.example.com/sso',
        'idp_x509_cert' => \Modules\Sso\Tests\Concerns\SamlTestFixtures::certificateBody(),
    ]);

    $response->assertStatus(201)->assertJsonPath('data.organization_slug', 'nouvelle-org');
    expect(SsoConfiguration::where('organization_slug', 'nouvelle-org')->exists())->toBeTrue();
});

it('émet un jeton SCIM en clair UNE SEULE FOIS pour un utilisateur autorisé', function (): void {
    $user = ssoAdminUserWithPermission();
    $configuration = SsoConfiguration::factory()->create(['organization_slug' => 'token-org']);

    $response = $this->actingAs($user)->postJson("/admin/sso/configurations/{$configuration->id}/scim-tokens", [
        'name' => 'Jeton principal',
    ]);

    $response->assertStatus(201);
    expect($response->json('token'))->toBeString()->not->toBeEmpty();

    // Le hash stocké en base ne permet PAS de retrouver le jeton en clair.
    $stored = $configuration->scimTokens()->first();
    expect($stored->token_hash)->not->toBe($response->json('token'));
});
