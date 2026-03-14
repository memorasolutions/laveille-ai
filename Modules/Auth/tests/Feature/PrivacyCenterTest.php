<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guest is redirected from privacy center', function () {
    $this->get(route('user.privacy'))->assertRedirect();
});

test('authenticated user can view privacy center', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user.privacy'))
        ->assertOk()
        ->assertSee(__('Centre de confidentialité'))
        ->assertSee(__('Vos données'))
        ->assertSee(__('Exporter mes données'))
        ->assertSee(__('Supprimer mon compte'));
});

test('privacy center shows all data categories', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('user.privacy'));

    $response->assertSee(__('Profil'))
        ->assertSee(__('Articles'))
        ->assertSee(__('Commentaires'))
        ->assertSee(__('Sessions'))
        ->assertSee(__('Abonnement'));
});

test('privacy center shows GDPR rights section', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('user.privacy'))
        ->assertSee(__('Vos droits'))
        ->assertSee(__('Droit d\'accès'));
});
