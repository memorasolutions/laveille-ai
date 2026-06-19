<?php

/**
 * Tests Feature: QR code avec expiration (lien dynamique)
 *
 * @author  MEMORA solutions <info@memora.ca>
 */

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ShortUrl\Models\ShortUrl;
use Modules\ShortUrl\Models\ShortUrlDomain;
use Tests\TestCase;

class QrDynamicLinkTest extends TestCase
{
    use RefreshDatabase;

    protected ShortUrlDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->domain = ShortUrlDomain::create([
            'domain'     => 'lurl.ca',
            'is_default' => true,
            'is_active'  => true,
        ]);
    }

    /** Endpoint crée un lien court avec expires_at */
    public function test_qr_dynamic_link_creates_short_url_with_expires_at(): void
    {
        $originalUrl = 'https://example.com';
        $expiresAt   = Carbon::now()->addHour()->format('Y-m-d\TH:i');

        $response = $this->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => $originalUrl,
            'expires_at'   => $expiresAt,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['short_url', 'slug', 'expires_at']);

        $this->assertDatabaseHas('short_urls', [
            'original_url' => $originalUrl,
        ]);

        $record = ShortUrl::where('original_url', $originalUrl)->first();
        $this->assertNotNull($record->expires_at);
    }

    /** Lien court valide → 302 vers original_url avant expiration */
    public function test_qr_dynamic_link_redirect_before_expiry(): void
    {
        ShortUrl::create([
            'slug'         => 'testqr1',
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::now()->addHour(),
            'is_active'    => true,
            'redirect_type' => 302,
            'domain_id'    => $this->domain->id,
        ]);

        $this->get('/s/testqr1')
             ->assertRedirect('https://example.com');
    }

    /** Lien expiré → redirection vers /lien-expire */
    public function test_qr_dynamic_link_redirect_after_expiry(): void
    {
        ShortUrl::create([
            'slug'         => 'testqr2',
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::now()->subMinute(),
            'is_active'    => true,
            'redirect_type' => 302,
            'domain_id'    => $this->domain->id,
        ]);

        $response = $this->get('/s/testqr2');
        $response->assertRedirect();
        $this->assertStringContainsString('/lien-expire', $response->headers->get('Location'));
    }

    /** Utilisateur connecté + slug personnalisé → respecté en DB */
    public function test_qr_dynamic_link_authenticated_user_with_custom_slug(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::now()->addHour()->format('Y-m-d\TH:i'),
            'slug'         => 'mon-lien-test',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('short_urls', [
            'slug'    => 'mon-lien-test',
            'user_id' => $user->id,
        ]);
    }

    /** Slug déjà pris → 422 avec message explicite */
    public function test_qr_dynamic_link_duplicate_slug_rejected(): void
    {
        $user = User::factory()->create();

        ShortUrl::create([
            'slug'         => 'pris',
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::now()->addHour(),
            'user_id'      => $user->id,
            'domain_id'    => $this->domain->id,
            'is_active'    => true,
            'redirect_type' => 302,
        ]);

        $response = $this->actingAs($user)->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => 'https://another.com',
            'expires_at'   => Carbon::now()->addHour()->format('Y-m-d\TH:i'),
            'slug'         => 'pris',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['slug']);
    }

    /** Anonyme → slug auto, is_anonymous=true */
    public function test_qr_dynamic_link_anonymous_gets_auto_slug(): void
    {
        $response = $this->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::now()->addHour()->format('Y-m-d\TH:i'),
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('short_urls', [
            'original_url' => 'https://example.com',
            'user_id'      => null,
            'is_anonymous' => true,
        ]);
    }

    /** URL invalide → 422 */
    public function test_qr_dynamic_link_invalid_url_rejected(): void
    {
        $response = $this->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => 'pas-une-url',
            'expires_at'   => Carbon::now()->addHour()->format('Y-m-d\TH:i'),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['original_url']);
    }

    /** Date passée → 422 */
    public function test_qr_dynamic_link_past_date_rejected(): void
    {
        $response = $this->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::yesterday()->format('Y-m-d\TH:i'),
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['expires_at']);
    }

    /** auto_extend=false sur lien QR : expires_at reste stable après un scan */
    public function test_qr_link_does_not_extend_expires_at_on_scan(): void
    {
        // Utiliser startOfSecond() pour éviter les écarts de microsecondes DB vs Carbon
        $originalExpiry = Carbon::now()->addHour()->startOfSecond();

        $shortUrl = ShortUrl::create([
            'slug'          => 'testqr-noext',
            'original_url'  => 'https://example.com',
            'expires_at'    => $originalExpiry,
            'is_active'     => true,
            'redirect_type' => 302,
            'domain_id'     => $this->domain->id,
            'auto_extend'   => false,
        ]);

        // Scanner le lien (GET /s/slug déclenche trackClick)
        $this->get('/s/testqr-noext');

        $shortUrl->refresh();
        // La date d'expiration doit rester dans l'heure (pas +12 mois)
        $this->assertTrue(
            $shortUrl->expires_at->lessThan(Carbon::now()->addMonths(6)),
            'expires_at a été prolongé alors que auto_extend=false'
        );
        $this->assertTrue(
            $shortUrl->expires_at->greaterThan(Carbon::now()),
            'expires_at ne doit pas être dans le passé'
        );
    }

    /** auto_extend=true (raccourcisseur normal) : expires_at prolongé de +12 mois au scan */
    public function test_normal_short_url_extends_expires_at_on_scan(): void
    {
        $shortUrl = ShortUrl::create([
            'slug'          => 'testqr-ext',
            'original_url'  => 'https://example.com',
            'expires_at'    => Carbon::now()->addDays(10),
            'is_active'     => true,
            'redirect_type' => 302,
            'domain_id'     => $this->domain->id,
            'auto_extend'   => true,
        ]);

        $this->get('/s/testqr-ext');

        $shortUrl->refresh();
        // expires_at doit être ~12 mois dans le futur (pas 10 jours)
        $this->assertTrue(
            $shortUrl->expires_at->greaterThan(Carbon::now()->addMonths(11)),
            'expires_at n\'a pas été prolongé alors que auto_extend=true'
        );
    }

    /** Endpoint QR pose auto_extend=false sur le lien créé */
    public function test_qr_endpoint_sets_auto_extend_false(): void
    {
        $response = $this->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::now()->addHour()->format('Y-m-d\TH:i'),
        ]);

        $response->assertStatus(200);

        $record = ShortUrl::where('original_url', 'https://example.com')->latest()->first();
        $this->assertNotNull($record);
        $this->assertFalse((bool) $record->auto_extend, 'auto_extend doit être false sur un lien QR');
    }

    /** Connecté avec max_clicks → respecté en DB */
    public function test_qr_dynamic_link_authenticated_max_clicks_respected(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::now()->addHour()->format('Y-m-d\TH:i'),
            'max_clicks'   => 50,
        ]);

        $response->assertStatus(200)->assertJsonFragment(['max_clicks' => 50]);

        $this->assertDatabaseHas('short_urls', [
            'user_id'    => $user->id,
            'max_clicks' => 50,
        ]);
    }

    /** Connecté avec mot de passe → stocké (hashé) en DB */
    public function test_qr_dynamic_link_authenticated_password_stored(): void
    {
        $user = \App\Models\User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('tools.qr.dynamic-link'), [
            'original_url' => 'https://example.com',
            'expires_at'   => Carbon::now()->addHour()->format('Y-m-d\TH:i'),
            'password'     => 'secret123',
        ]);

        $response->assertStatus(200)->assertJsonFragment(['has_password' => true]);

        $record = ShortUrl::where('user_id', $user->id)->latest()->first();
        $this->assertNotNull($record->password, 'Le mot de passe doit être stocké (hashé)');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $record->password));
    }

    /** Endpoint GET domaines → liste JSON */
    public function test_qr_domains_endpoint_returns_domains(): void
    {
        $response = $this->getJson(route('tools.qr.domains'));

        $response->assertStatus(200)->assertJsonStructure([['id', 'domain', 'is_default']]);
    }

    // =========================================================================
    // Tests — Délai de grâce 90 jours (QR_GRACE_DAYS)
    // =========================================================================

    /**
     * Lien QR expiré depuis <90j → toujours en base après cleanup,
     * et un scan retourne la page « expiré ».
     */
    public function test_qr_link_in_grace_period_stays_in_db_and_returns_expired_page(): void
    {
        $shortUrl = ShortUrl::create([
            'slug'          => 'grace-qr-1',
            'original_url'  => 'https://example.com',
            'domain_id'     => $this->domain->id,
            'auto_extend'   => false,
            'expires_at'    => Carbon::now()->subDay(), // expiré hier, dans la grâce
            'is_active'     => true,
            'redirect_type' => 302,
        ]);

        $this->artisan('shorturl:cleanup-expired')->assertSuccessful();

        // Le lien doit toujours exister (non supprimé, non soft-deleted)
        $this->assertDatabaseHas('short_urls', ['id' => $shortUrl->id, 'deleted_at' => null]);

        // Un scan doit rediriger vers /lien-expire (le lien est expiré mais en base)
        $response = $this->get('/s/grace-qr-1');
        $this->assertStringContainsString('/lien-expire', $response->headers->get('Location'));
    }

    /**
     * Lien QR expiré depuis >90j → soft-deleted par le cleanup.
     */
    public function test_qr_link_after_grace_period_is_deleted_by_cleanup(): void
    {
        $shortUrl = ShortUrl::create([
            'slug'          => 'grace-qr-2',
            'original_url'  => 'https://example.com',
            'domain_id'     => $this->domain->id,
            'auto_extend'   => false,
            'expires_at'    => Carbon::now()->subDays(91), // au-delà de la grâce
            'is_active'     => true,
            'redirect_type' => 302,
        ]);

        $this->artisan('shorturl:cleanup-expired')->assertSuccessful();

        // Le lien doit être soft-deleted
        $this->assertSoftDeleted('short_urls', ['id' => $shortUrl->id]);
    }

    /**
     * Lien normal (auto_extend=true) expiré → soft-deleted immédiatement, sans délai de grâce.
     */
    public function test_normal_link_expired_is_deleted_immediately_by_cleanup(): void
    {
        $shortUrl = ShortUrl::create([
            'slug'          => 'normal-expired-1',
            'original_url'  => 'https://example.com',
            'domain_id'     => $this->domain->id,
            'auto_extend'   => true,
            'expires_at'    => Carbon::now()->subDay(), // expiré hier
            'is_active'     => true,
            'redirect_type' => 302,
        ]);

        $this->artisan('shorturl:cleanup-expired')->assertSuccessful();

        // Suppression immédiate : pas de grâce pour les liens normaux
        $this->assertSoftDeleted('short_urls', ['id' => $shortUrl->id]);
    }

    /**
     * La notif « expire bientôt » n'est PAS envoyée aux liens QR fixes (auto_extend=false).
     * expiry_notified_at doit rester NULL après le cleanup.
     */
    public function test_cleanup_does_not_notify_qr_fixed_links(): void
    {
        $user = \App\Models\User::factory()->create();

        $shortUrl = ShortUrl::create([
            'slug'                 => 'notif-qr-1',
            'original_url'         => 'https://example.com',
            'domain_id'            => $this->domain->id,
            'user_id'              => $user->id,
            'auto_extend'          => false,
            'expires_at'           => Carbon::now()->addDays(15), // dans 15j = normalement éligible à notif
            'expiry_notified_at'   => null,
            'is_active'            => true,
            'redirect_type'        => 302,
        ]);

        $this->artisan('shorturl:cleanup-expired')->assertSuccessful();

        // expiry_notified_at doit rester NULL : les QR fixes sont exclus de la notif
        $shortUrl->refresh();
        $this->assertNull($shortUrl->expiry_notified_at, 'Un lien QR fixe ne doit jamais recevoir la notif « expire bientôt »');
    }

    /**
     * Le cleanup ne plante pas sur un lien QR anonyme (user_id=NULL)
     * expiré au-delà de la grâce.
     */
    public function test_cleanup_does_not_crash_on_anonymous_link(): void
    {
        $shortUrl = ShortUrl::create([
            'slug'          => 'anon-qr-1',
            'original_url'  => 'https://example.com',
            'domain_id'     => $this->domain->id,
            'user_id'       => null, // lien anonyme, pas de propriétaire
            'auto_extend'   => false,
            'expires_at'    => Carbon::now()->subDays(91), // au-delà de la grâce
            'is_active'     => true,
            'redirect_type' => 302,
        ]);

        // La commande doit réussir sans exception malgré user_id=null
        $this->artisan('shorturl:cleanup-expired')->assertSuccessful();

        // Et le lien doit être soft-deleted
        $this->assertSoftDeleted('short_urls', ['id' => $shortUrl->id]);
    }
}
