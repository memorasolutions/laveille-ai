<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

function founderOnlyUser(bool $super): User
{
    config(['app.superadmin_email' => 'fondateur@example.com']);
    $user = User::factory()->create(['email' => $super ? 'fondateur@example.com' : 'client@example.com']);
    if ($super) {
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
    }

    return $user;
}

test('founder_only actif : invité et non super-admin reçoivent 404 (public et admin)', function () {
    config(['shop.founder_only' => true]);

    $this->get('/boutique')->assertNotFound();
    $this->get('/admin/shop/products')->assertNotFound();
    $this->post('/api/shop/shipping-quote')->assertNotFound();

    $this->actingAs(founderOnlyUser(false));
    $this->get('/boutique')->assertNotFound();
    $this->get('/admin/shop/products')->assertNotFound();
});

test('founder_only actif : le super-admin passe', function () {
    config(['shop.founder_only' => true]);
    $this->actingAs(founderOnlyUser(true));

    expect($this->get('/boutique')->getStatusCode())->not->toBe(404);
    expect($this->get('/admin/shop/products')->getStatusCode())->not->toBe(404);
});

test('founder_only actif : les webhooks ne sont pas affectés', function () {
    config(['shop.founder_only' => true]);

    // Sans signature valide : refus applicatif, mais jamais le 404 du garde.
    expect($this->postJson('/webhooks/stripe-shop', [])->getStatusCode())->not->toBe(404);
    expect($this->postJson('/webhooks/gelato', [])->getStatusCode())->not->toBe(404);
});

test('founder_only inactif : comportement inchangé', function () {
    config(['shop.founder_only' => false]);

    expect($this->get('/boutique')->getStatusCode())->not->toBe(404);
    // Admin sans connexion : redirection vers login, pas 404.
    $this->get('/admin/shop/products')->assertRedirect();
});
