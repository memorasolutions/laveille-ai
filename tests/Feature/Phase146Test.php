<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\OnboardingStep;
use App\Models\User;
use Database\Seeders\OnboardingStepSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(OnboardingStepSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

it('seeds 4 onboarding steps', function () {
    expect(OnboardingStep::count())->toBe(4);
});

it('OnboardingStep has active scope', function () {
    expect(OnboardingStep::active()->count())->toBe(4);
});

it('OnboardingStep has ordered scope returning correct order', function () {
    $slugs = OnboardingStep::ordered()->pluck('slug')->toArray();
    expect($slugs)->toBe(['welcome', 'profile', 'preferences', 'done']);
});

it('GET /onboarding returns 200 for authenticated user', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)->get('/onboarding')->assertOk();
});

it('unauthenticated user redirected from /onboarding', function () {
    $this->get('/onboarding')->assertRedirect('/login');
});

it('POST /onboarding/complete sets onboarding_completed_at', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)
        ->post(route('onboarding.complete'))
        ->assertRedirect(route('user.dashboard'));
    expect($user->fresh()->onboarding_completed_at)->not->toBeNull();
});

it('POST /onboarding/complete updates name if provided', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)
        ->post(route('onboarding.complete'), ['name' => 'Jean Dupont'])
        ->assertRedirect(route('user.dashboard'));
    expect($user->fresh()->name)->toBe('Jean Dupont');
});

it('POST /onboarding/skip sets onboarding_completed_at without updating profile', function () {
    $user = User::factory()->create(['name' => 'Original Name']);
    $user->assignRole('user');
    $this->actingAs($user)
        ->post(route('onboarding.skip'))
        ->assertRedirect(route('user.dashboard'));
    expect($user->fresh()->onboarding_completed_at)->not->toBeNull();
    expect($user->fresh()->name)->toBe('Original Name');
});

it('admin can access onboarding steps index', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.onboarding-steps.index'))
        ->assertOk();
});

it('admin can access onboarding steps edit', function () {
    $step = OnboardingStep::first();
    $this->actingAs($this->admin)
        ->get(route('admin.onboarding-steps.edit', $step))
        ->assertOk();
});

it('admin can update onboarding step title', function () {
    $step = OnboardingStep::first();
    $this->actingAs($this->admin)
        ->put(route('admin.onboarding-steps.update', $step), [
            'title' => 'Nouveau titre',
            'order' => $step->order,
        ])
        ->assertRedirect(route('admin.onboarding-steps.index'));
    expect($step->fresh()->title)->toBe('Nouveau titre');
});

it('non-admin cannot access admin onboarding steps', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)
        ->get(route('admin.onboarding-steps.index'))
        ->assertForbidden();
});

it('onboarding wizard page shows step titles', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)
        ->get('/onboarding')
        ->assertOk()
        ->assertSee('Bienvenue')
        ->assertSee('Votre profil');
});
