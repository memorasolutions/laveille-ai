<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Shop\Support\ShopAccess;
use Spatie\Permission\Models\Role;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Calque exact de founderOnlyUser() de FounderOnlyTest : même email super-admin, même rôle.
function shopAccessUser(bool $super): User
{
    config(['app.superadmin_email' => 'fondateur@example.com']);
    $user = User::factory()->create(['email' => $super ? 'fondateur@example.com' : 'client@example.com']);
    if ($super) {
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
    }

    return $user;
}

test('maintenance : l\'interface boutique est cachée pour tout le monde, même le super-admin', function () {
    config(['shop.maintenance' => true, 'shop.founder_only' => false]);

    expect(ShopAccess::uiVisible())->toBeFalse();

    $this->actingAs(shopAccessUser(true));
    expect(ShopAccess::uiVisible())->toBeFalse();
});

test('boutique publique : l\'interface est visible pour un invité', function () {
    config(['shop.maintenance' => false, 'shop.founder_only' => false]);

    expect(ShopAccess::uiVisible())->toBeTrue();
});

test('réservée au fondateur : l\'interface est cachée à l\'invité', function () {
    config(['shop.maintenance' => false, 'shop.founder_only' => true]);

    expect(ShopAccess::uiVisible())->toBeFalse();
});

test('réservée au fondateur : l\'interface est cachée à un utilisateur ordinaire', function () {
    config(['shop.maintenance' => false, 'shop.founder_only' => true]);
    $this->actingAs(shopAccessUser(false));

    expect(ShopAccess::uiVisible())->toBeFalse();
});

test('réservée au fondateur : seul le super-admin voit l\'interface', function () {
    config(['shop.maintenance' => false, 'shop.founder_only' => true]);
    $this->actingAs(shopAccessUser(true));

    expect(ShopAccess::uiVisible())->toBeTrue();
});
