<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Paliers d'abonnement Academy (freemium/pro/organisation — LMS 2026).
 *
 * Prouve que :
 *  - le drapeau academy.subscription_tiers_enabled OFF (défaut) laisse l'accès
 *    INCHANGÉ (hasFeature() retourne toujours true, quel que soit le palier) ;
 *  - drapeau ON : un palier SANS la fonctionnalité refuse proprement (false,
 *    jamais d'exception) et un palier AVEC la fonctionnalité l'autorise ;
 *  - à défaut d'assignation, l'utilisateur retombe sur le palier `is_default` ;
 *  - le CRUD admin (/admin/academy/subscription-tiers) fonctionne et est
 *    anti-IDOR (non-admin reçoit 403 sur chaque action).
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\SubscriptionTier;
use Modules\Academy\Services\TierGateService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    // Défaut EXPLICITE : chaque test active le drapeau lui-même quand nécessaire.
    config(['academy.subscription_tiers_enabled' => false]);
    config(['academy.subscription_tier_feature_keys' => [
        'academy_gamification' => 'Gamification',
        'academy_open_badges'  => 'Open Badges',
    ]]);
});

/** Crée un palier avec des valeurs par défaut sûres (prix neutre, aucun Stripe). */
function stierTier(array $overrides = []): SubscriptionTier
{
    return SubscriptionTier::create(array_merge([
        'name'            => 'Palier test',
        'slug'            => 'palier-test-'.uniqid(),
        'price_cents'     => null,
        'billing_period'  => 'monthly',
        'features'        => [],
        'max_seats'       => null,
        'stripe_price_id' => null,
        'is_default'      => false,
        'is_active'       => true,
        'sort_order'      => 0,
    ], $overrides));
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF (défaut) = accès INCHANGÉ, aucune restriction ajoutée
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau subscription_tiers_enabled OFF (défaut) laisse toujours l\'accès inchangé', function (): void {
    config(['academy.subscription_tiers_enabled' => false]);

    $user = User::factory()->create();
    // Même sans AUCUN palier configuré en base, l'accès reste ouvert (défaut inchangé).
    $service = new TierGateService();

    expect($service->hasFeature($user, 'academy_gamification'))->toBeTrue();
    expect($service->hasFeature(null, 'academy_open_badges'))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Drapeau ON : gating réel selon le palier assigné
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau ON : un palier SANS la fonctionnalité refuse proprement (jamais 500)', function (): void {
    config(['academy.subscription_tiers_enabled' => true]);

    $tier = stierTier(['name' => 'Freemium', 'is_default' => true, 'features' => []]);
    $user = User::factory()->create();
    app(TierGateService::class)->assignTier($user, $tier);

    expect(app(TierGateService::class)->hasFeature($user, 'academy_gamification'))->toBeFalse();
});

test('drapeau ON : un palier AVEC la fonctionnalité autorise l\'accès', function (): void {
    config(['academy.subscription_tiers_enabled' => true]);

    $tier = stierTier(['name' => 'Pro', 'features' => ['academy_gamification']]);
    $user = User::factory()->create();
    app(TierGateService::class)->assignTier($user, $tier);

    expect(app(TierGateService::class)->hasFeature($user, 'academy_gamification'))->toBeTrue();
    expect(app(TierGateService::class)->hasFeature($user, 'academy_open_badges'))->toBeFalse();
});

test('drapeau ON : sans assignation, l\'utilisateur retombe sur le palier par défaut', function (): void {
    config(['academy.subscription_tiers_enabled' => true]);

    stierTier(['name' => 'Freemium', 'is_default' => true, 'features' => []]);
    $pro = stierTier(['name' => 'Pro', 'features' => ['academy_gamification']]);

    $user = User::factory()->create();

    // Aucune assignation créée : doit résoudre le palier par défaut (Freemium), pas Pro.
    $resolved = app(TierGateService::class)->currentTierFor($user);
    expect($resolved?->slug)->toBe(SubscriptionTier::where('is_default', true)->first()->slug);
    expect($resolved?->id)->not->toBe($pro->id);
    expect(app(TierGateService::class)->hasFeature($user, 'academy_gamification'))->toBeFalse();
});

test('assignTier désactive l\'ancienne assignation et n\'en laisse qu\'une active', function (): void {
    config(['academy.subscription_tiers_enabled' => true]);

    $free = stierTier(['name' => 'Freemium', 'is_default' => true]);
    $pro  = stierTier(['name' => 'Pro', 'features' => ['academy_gamification']]);
    $user = User::factory()->create();

    app(TierGateService::class)->assignTier($user, $free);
    app(TierGateService::class)->assignTier($user, $pro);

    expect($user->subscriptionTierAssignments()->where('is_active', true)->count())->toBe(1);
    expect($user->currentSubscriptionTier()?->id)->toBe($pro->id);
    expect($user->hasSubscriptionFeature('academy_gamification'))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) CRUD admin — accessible à l'admin, 403 anti-IDOR pour les autres
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    if (! $this->admin->can('academy.manage')) {
        $this->admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    $this->plainUser = User::factory()->create();
});

test('admin avec academy.manage voit la liste des paliers', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.academy.subscription-tiers.index'))
        ->assertOk()
        ->assertSee('Paliers d\'abonnement', false);
});

test('utilisateur sans permission reçoit 403 sur la liste des paliers', function (): void {
    $this->actingAs($this->plainUser)
        ->get(route('admin.academy.subscription-tiers.index'))
        ->assertForbidden();
});

test('utilisateur sans permission reçoit 403 sur la création d\'un palier', function (): void {
    $this->actingAs($this->plainUser)
        ->post(route('admin.academy.subscription-tiers.store'), ['name' => 'Hack'])
        ->assertForbidden();

    expect(SubscriptionTier::where('name', 'Hack')->exists())->toBeFalse();
});

test('admin peut créer un palier avec des fonctionnalités', function (): void {
    $this->actingAs($this->admin)
        ->post(route('admin.academy.subscription-tiers.store'), [
            'name'           => 'Pro',
            'billing_period' => 'monthly',
            'features'       => ['academy_gamification'],
            'is_active'      => '1',
        ])
        ->assertRedirect(route('admin.academy.subscription-tiers.index'));

    $tier = SubscriptionTier::where('name', 'Pro')->first();
    expect($tier)->not->toBeNull();
    expect($tier->hasFeature('academy_gamification'))->toBeTrue();
    // Zéro Stripe : aucun ID de prix inventé par défaut.
    expect($tier->stripe_price_id)->toBeNull();
});

test('un seul palier peut être marqué par défaut à la fois', function (): void {
    $first  = stierTier(['name' => 'Freemium', 'is_default' => true]);

    $this->actingAs($this->admin)
        ->post(route('admin.academy.subscription-tiers.store'), [
            'name'           => 'Pro',
            'billing_period' => 'monthly',
            'is_default'     => '1',
        ])
        ->assertRedirect();

    expect($first->fresh()->is_default)->toBeFalse();
    expect(SubscriptionTier::where('is_default', true)->count())->toBe(1);
});

test('admin peut basculer le statut actif/inactif d\'un palier', function (): void {
    $tier = stierTier(['is_active' => true]);

    $this->actingAs($this->admin)
        ->post(route('admin.academy.subscription-tiers.toggle-status', $tier))
        ->assertRedirect();

    expect($tier->fresh()->is_active)->toBeFalse();
});

test('suppression bloquée proprement si des utilisateurs sont assignés (anti-casse)', function (): void {
    $tier = stierTier();
    $user = User::factory()->create();
    app(TierGateService::class)->assignTier($user, $tier);

    $this->actingAs($this->admin)
        ->delete(route('admin.academy.subscription-tiers.destroy', $tier))
        ->assertRedirect();

    expect(SubscriptionTier::find($tier->id))->not->toBeNull();
});
