<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Provisioning SCIM 2.0 (RFC 7643/7644).
 *
 * Prouve que :
 *  - un jeton invalide/absent -> 401 sur toutes les routes /scim/v2/* ;
 *  - CRUD complet Users (POST create, GET show, GET list, PUT update,
 *    PATCH incrémental active=false, DELETE = désactivation SANS suppression
 *    physique) fonctionne ;
 *  - PAGINATION (startIndex/count/totalResults) et FILTRAGE basique
 *    (filter=userName eq "...") fonctionnent ;
 *  - ISOLATION MULTI-TENANT stricte : le jeton de l'organisation A ne peut
 *    JAMAIS lister/lire/modifier les utilisateurs provisionnés par
 *    l'organisation B (anti-IDOR) ;
 *  - le drapeau sso.enabled=false (défaut) fait répondre 404.
 *
 * Autonome : helpers préfixés `scim`, aucune redéclaration d'une fonction
 * d'un autre fichier de test. Garde-fou : si le module Sso est désactivé
 * dans modules_statuses.json, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Sso\Models\ScimToken;
use Modules\Sso\Models\SsoConfiguration;
use Modules\Sso\Services\ScimTokenService;

uses(RefreshDatabase::class);
uses(\Modules\Sso\Tests\Concerns\SkipsWhenSsoDisabled::class);

beforeEach(function (): void {
    test()->skipIfSsoModuleDisabled();
    config(['sso.enabled' => true]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers scim (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** @return array{configuration: SsoConfiguration, token: string} */
function scimOrganizationWithToken(string $slug): array
{
    $configuration = SsoConfiguration::factory()->create(['organization_slug' => $slug]);
    $issued = app(ScimTokenService::class)->issue($configuration, "Jeton {$slug}");

    return ['configuration' => $configuration, 'token' => $issued['token']];
}

function scimHeaders(string $token): array
{
    return [
        'Authorization' => "Bearer {$token}",
        'Accept' => 'application/scim+json',
        'Content-Type' => 'application/scim+json',
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Authentification
// ─────────────────────────────────────────────────────────────────────────────

it('répond 404 sur /scim/v2/Users quand sso.enabled est désactivé (défaut)', function (): void {
    config(['sso.enabled' => false]);

    $this->getJson('/scim/v2/Users')->assertNotFound();
});

it('répond 401 quand le jeton Bearer SCIM est absent', function (): void {
    $this->getJson('/scim/v2/Users', ['Accept' => 'application/scim+json'])
        ->assertStatus(401)
        ->assertJsonPath('schemas.0', 'urn:ietf:params:scim:api:messages:2.0:Error');
});

it('répond 401 quand le jeton Bearer SCIM est invalide', function (): void {
    $this->getJson('/scim/v2/Users', scimHeaders('un-jeton-qui-n-existe-pas'))
        ->assertStatus(401);
});

it('répond 401 quand le jeton Bearer SCIM est révoqué', function (): void {
    $org = scimOrganizationWithToken('revoked-org');
    $token = ScimToken::query()->where('sso_configuration_id', $org['configuration']->getKey())->firstOrFail();
    app(ScimTokenService::class)->revoke($token);

    $this->getJson('/scim/v2/Users', scimHeaders($org['token']))->assertStatus(401);
});

// ─────────────────────────────────────────────────────────────────────────────
// CRUD Users
// ─────────────────────────────────────────────────────────────────────────────

it('crée un utilisateur via POST /scim/v2/Users (cas positif)', function (): void {
    $org = scimOrganizationWithToken('create-org');

    $response = $this->postJson('/scim/v2/Users', [
        'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
        'userName' => 'nouvel.employe@create-org.example.com',
        'name' => ['formatted' => 'Nouvel Employé'],
        'emails' => [['value' => 'nouvel.employe@create-org.example.com', 'primary' => true]],
        'active' => true,
    ], scimHeaders($org['token']));

    $response->assertStatus(201)
        ->assertJsonPath('userName', 'nouvel.employe@create-org.example.com')
        ->assertJsonPath('active', true);

    expect(User::where('email', 'nouvel.employe@create-org.example.com')->exists())->toBeTrue();
});

it('refuse la création sans email/userName (validation)', function (): void {
    $org = scimOrganizationWithToken('invalid-org');

    $this->postJson('/scim/v2/Users', ['name' => ['formatted' => 'Sans Email']], scimHeaders($org['token']))
        ->assertStatus(400);
});

it('lit un utilisateur provisionné via GET /scim/v2/Users/{id}', function (): void {
    $org = scimOrganizationWithToken('read-org');

    $created = $this->postJson('/scim/v2/Users', [
        'userName' => 'lecture@read-org.example.com',
        'name' => ['formatted' => 'Lecture Test'],
    ], scimHeaders($org['token']))->json();

    $this->getJson('/scim/v2/Users/'.$created['id'], scimHeaders($org['token']))
        ->assertOk()
        ->assertJsonPath('userName', 'lecture@read-org.example.com');
});

it('met à jour intégralement un utilisateur via PUT (remplacement complet)', function (): void {
    $org = scimOrganizationWithToken('put-org');

    $created = $this->postJson('/scim/v2/Users', [
        'userName' => 'avant@put-org.example.com',
        'name' => ['formatted' => 'Avant Maj'],
    ], scimHeaders($org['token']))->json();

    $this->putJson('/scim/v2/Users/'.$created['id'], [
        'userName' => 'apres@put-org.example.com',
        'name' => ['formatted' => 'Après Maj'],
        'active' => true,
    ], scimHeaders($org['token']))
        ->assertOk()
        ->assertJsonPath('userName', 'apres@put-org.example.com');

    expect(User::find($created['id'])->name)->toBe('Après Maj');
});

it('désactive un utilisateur via PATCH active=false SANS le supprimer physiquement', function (): void {
    $org = scimOrganizationWithToken('patch-org');

    $created = $this->postJson('/scim/v2/Users', [
        'userName' => 'a-desactiver@patch-org.example.com',
        'name' => ['formatted' => 'À Désactiver'],
    ], scimHeaders($org['token']))->json();

    $this->patchJson('/scim/v2/Users/'.$created['id'], [
        'schemas' => ['urn:ietf:params:scim:api:messages:2.0:PatchOp'],
        'Operations' => [
            ['op' => 'replace', 'path' => 'active', 'value' => false],
        ],
    ], scimHeaders($org['token']))
        ->assertOk()
        ->assertJsonPath('active', false);

    $user = User::find($created['id']);
    expect($user)->not->toBeNull();
    expect($user->is_active)->toBeFalse();
});

it('désactive (jamais supprime) un utilisateur via DELETE', function (): void {
    $org = scimOrganizationWithToken('delete-org');

    $created = $this->postJson('/scim/v2/Users', [
        'userName' => 'a-supprimer@delete-org.example.com',
        'name' => ['formatted' => 'À Supprimer'],
    ], scimHeaders($org['token']))->json();

    $this->deleteJson('/scim/v2/Users/'.$created['id'], [], scimHeaders($org['token']))
        ->assertStatus(204);

    $user = User::find($created['id']);
    expect($user)->not->toBeNull(); // toujours en base — jamais de suppression physique
    expect($user->is_active)->toBeFalse();

    // Organisation ne voit plus l'utilisateur désactivé/détaché dans SA liste.
    $this->getJson('/scim/v2/Users/'.$created['id'], scimHeaders($org['token']))->assertStatus(404);
});

// ─────────────────────────────────────────────────────────────────────────────
// Pagination + filtrage
// ─────────────────────────────────────────────────────────────────────────────

it('pagine GET /scim/v2/Users (startIndex, count, totalResults)', function (): void {
    $org = scimOrganizationWithToken('page-org');

    foreach (range(1, 5) as $i) {
        $this->postJson('/scim/v2/Users', [
            'userName' => "user{$i}@page-org.example.com",
            'name' => ['formatted' => "User {$i}"],
        ], scimHeaders($org['token']));
    }

    $page1 = $this->getJson('/scim/v2/Users?startIndex=1&count=2', scimHeaders($org['token']))->json();
    expect($page1['totalResults'])->toBe(5);
    expect($page1['itemsPerPage'])->toBe(2);
    expect($page1['Resources'])->toHaveCount(2);

    $page2 = $this->getJson('/scim/v2/Users?startIndex=3&count=2', scimHeaders($org['token']))->json();
    expect($page2['Resources'])->toHaveCount(2);
    expect($page1['Resources'][0]['id'])->not->toBe($page2['Resources'][0]['id']);
});

it('filtre GET /scim/v2/Users via filter=userName eq "..."', function (): void {
    $org = scimOrganizationWithToken('filter-org');

    $this->postJson('/scim/v2/Users', ['userName' => 'cible@filter-org.example.com', 'name' => ['formatted' => 'Cible']], scimHeaders($org['token']));
    $this->postJson('/scim/v2/Users', ['userName' => 'autre@filter-org.example.com', 'name' => ['formatted' => 'Autre']], scimHeaders($org['token']));

    $filtered = $this->getJson('/scim/v2/Users?'.http_build_query(['filter' => 'userName eq "cible@filter-org.example.com"']), scimHeaders($org['token']))->json();

    expect($filtered['totalResults'])->toBe(1);
    expect($filtered['Resources'][0]['userName'])->toBe('cible@filter-org.example.com');
});

// ─────────────────────────────────────────────────────────────────────────────
// Isolation multi-tenant (anti-IDOR) — LE test critique de la spec.
// ─────────────────────────────────────────────────────────────────────────────

it('empêche une organisation de LISTER les utilisateurs provisionnés par une AUTRE organisation', function (): void {
    $orgA = scimOrganizationWithToken('org-a');
    $orgB = scimOrganizationWithToken('org-b');

    $this->postJson('/scim/v2/Users', ['userName' => 'employe-a@org-a.example.com', 'name' => ['formatted' => 'Employé A']], scimHeaders($orgA['token']));
    $this->postJson('/scim/v2/Users', ['userName' => 'employe-b@org-b.example.com', 'name' => ['formatted' => 'Employé B']], scimHeaders($orgB['token']));

    $listFromA = $this->getJson('/scim/v2/Users', scimHeaders($orgA['token']))->json();
    $listFromB = $this->getJson('/scim/v2/Users', scimHeaders($orgB['token']))->json();

    expect($listFromA['totalResults'])->toBe(1);
    expect($listFromA['Resources'][0]['userName'])->toBe('employe-a@org-a.example.com');

    expect($listFromB['totalResults'])->toBe(1);
    expect($listFromB['Resources'][0]['userName'])->toBe('employe-b@org-b.example.com');
});

it('empêche une organisation de LIRE (GET) un utilisateur provisionné par une AUTRE organisation (404, anti-IDOR)', function (): void {
    $orgA = scimOrganizationWithToken('iso-read-a');
    $orgB = scimOrganizationWithToken('iso-read-b');

    $createdByA = $this->postJson('/scim/v2/Users', [
        'userName' => 'confidentiel@iso-read-a.example.com',
        'name' => ['formatted' => 'Confidentiel A'],
    ], scimHeaders($orgA['token']))->json();

    // Org B tente de lire l'utilisateur de A avec SON PROPRE jeton valide.
    $this->getJson('/scim/v2/Users/'.$createdByA['id'], scimHeaders($orgB['token']))
        ->assertStatus(404);
});

it('empêche une organisation de MODIFIER (PATCH) un utilisateur provisionné par une AUTRE organisation (anti-IDOR)', function (): void {
    $orgA = scimOrganizationWithToken('iso-patch-a');
    $orgB = scimOrganizationWithToken('iso-patch-b');

    $createdByA = $this->postJson('/scim/v2/Users', [
        'userName' => 'protege@iso-patch-a.example.com',
        'name' => ['formatted' => 'Protégé A'],
    ], scimHeaders($orgA['token']))->json();

    // Org B tente de désactiver le compte de A.
    $this->patchJson('/scim/v2/Users/'.$createdByA['id'], [
        'Operations' => [['op' => 'replace', 'path' => 'active', 'value' => false]],
    ], scimHeaders($orgB['token']))->assertStatus(404);

    // Le compte de A reste INTACT (toujours actif).
    $user = User::find($createdByA['id']);
    expect($user->is_active)->toBeTrue();
});

it('empêche une organisation de SUPPRIMER (DELETE) un utilisateur provisionné par une AUTRE organisation (anti-IDOR)', function (): void {
    $orgA = scimOrganizationWithToken('iso-delete-a');
    $orgB = scimOrganizationWithToken('iso-delete-b');

    $createdByA = $this->postJson('/scim/v2/Users', [
        'userName' => 'intouchable@iso-delete-a.example.com',
        'name' => ['formatted' => 'Intouchable A'],
    ], scimHeaders($orgA['token']))->json();

    $this->deleteJson('/scim/v2/Users/'.$createdByA['id'], [], scimHeaders($orgB['token']))
        ->assertStatus(404);

    $user = User::find($createdByA['id']);
    expect($user->is_active)->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// Découverte SCIM
// ─────────────────────────────────────────────────────────────────────────────

it('expose GET /scim/v2/ServiceProviderConfig', function (): void {
    $org = scimOrganizationWithToken('discovery-org');

    $this->getJson('/scim/v2/ServiceProviderConfig', scimHeaders($org['token']))
        ->assertOk()
        ->assertJsonPath('patch.supported', true)
        ->assertJsonPath('bulk.supported', false);
});

it('expose GET /scim/v2/Schemas avec le schéma Core User', function (): void {
    $org = scimOrganizationWithToken('schemas-org');

    $this->getJson('/scim/v2/Schemas', scimHeaders($org['token']))
        ->assertOk()
        ->assertJsonPath('Resources.0.id', 'urn:ietf:params:scim:schemas:core:2.0:User');
});

it('répond 501 sur /scim/v2/Groups (hors scope V1, documenté)', function (): void {
    $org = scimOrganizationWithToken('groups-org');

    $this->getJson('/scim/v2/Groups', scimHeaders($org['token']))->assertStatus(501);
});
