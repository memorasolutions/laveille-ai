<?php

declare(strict_types=1);

use App\Models\User;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('admin layout renders breadcrumbs section', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.logs'))
        ->assertOk()
        ->assertSee('page-breadcrumb');
});

test('profile edit has breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.profile'))
        ->assertOk()
        ->assertSee('Profil');
});

test('push notifications has breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.push-notifications.index'))
        ->assertOk()
        ->assertSee('Notifications push');
});

test('onboarding steps has breadcrumbs', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.onboarding-steps.index'))
        ->assertOk()
        ->assertSee('Onboarding');
});

test('breadcrumbs contain Administration link', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.logs'))
        ->assertOk()
        ->assertSee('Administration');
});
