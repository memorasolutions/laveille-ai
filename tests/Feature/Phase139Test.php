<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Auth\Livewire\OnboardingWizard;

uses(RefreshDatabase::class);

test('new user needs onboarding', function () {
    $user = User::factory()->create();
    expect($user->needsOnboarding())->toBeTrue();
});

test('user with completed onboarding does not need onboarding', function () {
    $user = User::factory()->create(['onboarding_completed_at' => now()]);
    expect($user->needsOnboarding())->toBeFalse();
});

test('hasCompletedOnboarding returns true when timestamp set', function () {
    $user = User::factory()->create(['onboarding_completed_at' => now()]);
    expect($user->hasCompletedOnboarding())->toBeTrue();
});

test('onboarding step defaults to 0 for new user', function () {
    $user = User::factory()->create();
    expect((int) $user->onboarding_step)->toBe(0);
});

test('dashboard shows onboarding wizard for new user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user.dashboard'))
        ->assertOk()
        ->assertSee('Bienvenue');
});

test('dashboard hides onboarding wizard for completed user', function () {
    $user = User::factory()->create(['onboarding_completed_at' => now()]);

    $this->actingAs($user)
        ->get(route('user.dashboard'))
        ->assertOk()
        ->assertDontSee('onboarding-wizard');
});

test('livewire completeStep advances step', function () {
    $user = User::factory()->create(['onboarding_step' => 0]);

    Livewire::actingAs($user)
        ->test(OnboardingWizard::class)
        ->call('completeStep', 1)
        ->assertSet('step', 1);

    expect($user->fresh()->onboarding_step)->toBe(1);
});

test('livewire saveProfile saves bio and advances step', function () {
    $user = User::factory()->create(['onboarding_step' => 1]);

    Livewire::actingAs($user)
        ->test(OnboardingWizard::class)
        ->set('bio', 'Ma biographie de test')
        ->call('saveProfile')
        ->assertSet('step', 2);

    expect($user->fresh()->bio)->toBe('Ma biographie de test');
    expect($user->fresh()->onboarding_step)->toBe(2);
});

test('livewire skipToStep advances but cannot regress', function () {
    $user = User::factory()->create(['onboarding_step' => 3]);

    Livewire::actingAs($user)
        ->test(OnboardingWizard::class)
        ->call('skipToStep', 2)
        ->assertSet('step', 2);

    // DB should NOT regress
    expect($user->fresh()->onboarding_step)->toBe(3);
});

test('livewire complete sets onboarding completed at', function () {
    $user = User::factory()->create(['onboarding_step' => 4]);

    Livewire::actingAs($user)
        ->test(OnboardingWizard::class)
        ->call('complete')
        ->assertSet('dismissed', true);

    $user->refresh();
    expect($user->onboarding_step)->toBe(5);
    expect($user->onboarding_completed_at)->not->toBeNull();
});

test('migration includes onboarding columns', function () {
    expect(Schema::hasColumn('users', 'onboarding_step'))->toBeTrue();
    expect(Schema::hasColumn('users', 'onboarding_completed_at'))->toBeTrue();
});

test('onboarding wizard livewire component class exists', function () {
    expect(class_exists(OnboardingWizard::class))->toBeTrue();
});
