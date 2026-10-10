<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Modules\Idp\Models\OAuthUser;

uses(RefreshDatabase::class);
uses(\Modules\Idp\Tests\Concerns\SkipsWhenIdpDisabled::class);
uses(\Modules\Idp\Tests\Concerns\EnablesIdp::class);

beforeEach(function (): void {
    test()->skipIfIdpModuleDisabled();
});

afterEach(function (): void {
    \Modules\Idp\Tests\Concerns\EnablesIdp::disableIdpEnv();
});

function idpLoadKeys(): void
{
    // Clés Passport éphémères (aucune clé du dépôt ni de storage/ n'est utilisée).
    $dir = sys_get_temp_dir().'/idp-keys-'.bin2hex(random_bytes(4));
    mkdir($dir);
    $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($res, $private);
    file_put_contents($dir.'/oauth-private.key', $private);
    file_put_contents($dir.'/oauth-public.key', openssl_pkey_get_details($res)['key']);
    chmod($dir.'/oauth-private.key', 0600);
    chmod($dir.'/oauth-public.key', 0600);
    Passport::loadKeysFrom($dir);
}

/**
 * Jeton Passport portant les scopes donnés, posé sur le garde idp. Équivalent de Passport::actingAs()
 * SANS shouldUse() : le garde par défaut reste web comme en prod (auth:idp le bascule lui-même),
 * sinon le middleware EnsureAccountActive, placé avant auth:idp, verrait un utilisateur qu'il ne voit pas en prod.
 */
function idpActingAs(User $user, array $scopes): void
{
    $oauthUser = OAuthUser::findOrFail($user->id);
    // Vraies lignes oauth_clients / oauth_access_tokens : le jeton est révocable comme en prod.
    $clientId = (string) \Illuminate\Support\Str::uuid();
    $tokenId = bin2hex(random_bytes(20));
    $now = now();
    \Illuminate\Support\Facades\DB::table('oauth_clients')->insert([
        'id' => $clientId, 'name' => 'Moodle (test)', 'redirect_uris' => '[]', 'grant_types' => '[]',
        'revoked' => false, 'created_at' => $now, 'updated_at' => $now,
    ]);
    \Illuminate\Support\Facades\DB::table('oauth_access_tokens')->insert([
        'id' => $tokenId, 'user_id' => $user->id, 'client_id' => $clientId, 'scopes' => json_encode($scopes),
        'revoked' => false, 'created_at' => $now, 'updated_at' => $now, 'expires_at' => $now->copy()->addHour(),
    ]);
    $oauthUser->withAccessToken(new \Laravel\Passport\AccessToken([
        'oauth_access_token_id' => $tokenId,
        'oauth_client_id' => $clientId,
        'oauth_user_id' => $oauthUser->getAuthIdentifier(),
        'oauth_scopes' => $scopes,
    ]));
    auth('idp')->setUser($oauthUser);
}

it('ON : le provider Passport est chargé', function (): void {
    expect(config('idp.enabled'))->toBeTrue();
    expect(app()->getProviders(\Laravel\Passport\PassportServiceProvider::class))->not->toBeEmpty();
});

it('ON : un OAuthUser authentifié par le guard idp obtient ses claims', function (): void {
    idpLoadKeys();
    $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
    idpActingAs($user, ['openid']);

    $this->getJson('/api/oauth/userinfo')->assertOk()->assertExactJson([
        'sub' => (string) $user->id,
        'email' => $user->email,
        'email_verified' => true,
        'name' => $user->name,
    ]);
});

it('ON : un compte désactivé est refusé (403)', function (): void {
    idpLoadKeys();
    $user = User::factory()->create(['is_active' => false]);
    idpActingAs($user, ['openid']);

    $this->getJson('/api/oauth/userinfo')->assertForbidden();
});

it('ON : sans jeton, 401', function (): void {
    idpLoadKeys();
    $this->getJson('/api/oauth/userinfo')->assertUnauthorized();
});

it('ON : un jeton portant le scope openid obtient les 4 claims', function (): void {
    idpLoadKeys();
    $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
    idpActingAs($user, ['openid']);

    $this->getJson('/api/oauth/userinfo')->assertOk()->assertExactJson([
        'sub' => (string) $user->id,
        'email' => $user->email,
        'email_verified' => true,
        'name' => $user->name,
    ]);
});

it('ON : un jeton SANS le scope openid est refusé (403)', function (): void {
    idpLoadKeys();
    $user = User::factory()->create(['is_active' => true, 'email_verified_at' => now()]);
    idpActingAs($user, ['email', 'profile']);

    $this->getJson('/api/oauth/userinfo')->assertForbidden();
});

it('ON : les scopes OIDC sont déclarés et les tables oauth_* sont migrées', function (): void {
    expect(Passport::scopeIds())->toEqualCanonicalizing(['openid', 'email', 'profile']);
    expect(\Illuminate\Support\Facades\Schema::hasTable('oauth_clients'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('oauth_access_tokens'))->toBeTrue();
});

it('ON : le client maison saute le consentement seulement si first-party ET nom de confiance', function (): void {
    // Le modèle client maison est bien celui que Passport utilise (sinon l'écran de consentement,
    // qui n'a aucune vue, casserait le flux /oauth/authorize du Moodle).
    expect(Passport::clientModel())->toBe(\Modules\Idp\Models\OAuthClient::class);
    expect(config('idp.trusted_client_names'))->toContain('Moodle formations');

    $user = User::factory()->create();

    // First-party + nom de confiance (le Moodle) : consentement sauté.
    $moodle = new \Modules\Idp\Models\OAuthClient;
    $moodle->name = 'Moodle formations';
    expect($moodle->skipsAuthorization($user, []))->toBeTrue();

    // First-party MAIS nom non listé : consentement NON sauté (durcissement revue 2026-10-10).
    $autre = new \Modules\Idp\Models\OAuthClient;
    $autre->name = 'Autre client interne';
    expect($autre->skipsAuthorization($user, []))->toBeFalse();

    // Avec propriétaire (client tiers), même avec un nom de confiance : consentement NON sauté.
    $tiers = new \Modules\Idp\Models\OAuthClient;
    $tiers->name = 'Moodle formations';
    $tiers->user_id = $user->id;
    $tiers->owner_id = $user->id;
    expect($tiers->skipsAuthorization($user, []))->toBeFalse();
});

it('ON : la vue de consentement est liée (sinon /oauth/authorize renvoie 500)', function (): void {
    // Régression du flip du 2026-10-10 : sans ce binding, Passport 13 lève
    // « AuthorizationViewResponse is not instantiable » sur /oauth/authorize.
    $response = app(\Laravel\Passport\Contracts\AuthorizationViewResponse::class);
    expect($response)->toBeInstanceOf(\Laravel\Passport\Contracts\AuthorizationViewResponse::class);
});
