<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\Coupon;
use Modules\Booking\Models\GiftCard;
use Modules\Booking\Models\Package;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

function bookingAdmin(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('manage_booking');

    return $user;
}

// --- Modèles Coupon ---

it('Coupon scopes active et valid fonctionnent', function () {
    Coupon::create([
        'code' => 'ACTIVE123',
        'type' => 'percent',
        'value' => 10,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(30),
        'max_uses' => 100,
        'used_count' => 0,
    ]);

    Coupon::create([
        'code' => 'INACTIVE456',
        'type' => 'fixed',
        'value' => 20,
        'is_active' => false,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDays(30),
    ]);

    Coupon::create([
        'code' => 'EXPIRED789',
        'type' => 'percent',
        'value' => 15,
        'is_active' => true,
        'starts_at' => now()->subDays(30),
        'expires_at' => now()->subDay(),
    ]);

    expect(Coupon::active()->count())->toBe(1);
    expect(Coupon::valid()->count())->toBe(1);
});

it('Coupon calculateDiscount fonctionne pour percent et fixed', function () {
    $percentCoupon = Coupon::create([
        'code' => 'PERCENT20',
        'type' => 'percent',
        'value' => 20,
        'is_active' => true,
    ]);

    $fixedCoupon = Coupon::create([
        'code' => 'FIXED15',
        'type' => 'fixed',
        'value' => 15,
        'is_active' => true,
    ]);

    expect($percentCoupon->calculateDiscount(100))->toBe(20.0);
    expect($fixedCoupon->calculateDiscount(100))->toBe(15.0);
});

it('Coupon isValidForService vérifie les services applicables', function () {
    $couponWithServices = Coupon::create([
        'code' => 'SERVICES12',
        'type' => 'percent',
        'value' => 10,
        'is_active' => true,
        'applicable_service_ids' => [1, 2],
    ]);

    $couponNoRestriction = Coupon::create([
        'code' => 'ALLSERVICES',
        'type' => 'fixed',
        'value' => 25,
        'is_active' => true,
        'applicable_service_ids' => null,
    ]);

    expect($couponWithServices->isValidForService(1))->toBeTrue();
    expect($couponWithServices->isValidForService(3))->toBeFalse();
    expect($couponNoRestriction->isValidForService(999))->toBeTrue();
});

// --- Modèles Package ---

it('Package scopes active et ordered fonctionnent', function () {
    Package::create([
        'name' => 'Package inactif',
        'session_count' => 5,
        'price' => 300,
        'validity_days' => 180,
        'is_active' => false,
        'sort_order' => 2,
    ]);

    Package::create([
        'name' => 'Package actif 1',
        'session_count' => 10,
        'price' => 500,
        'validity_days' => 365,
        'is_active' => true,
        'sort_order' => 3,
    ]);

    Package::create([
        'name' => 'Package actif 2',
        'session_count' => 20,
        'price' => 800,
        'validity_days' => 365,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    expect(Package::active()->count())->toBe(2);
    expect(Package::ordered()->first()->name)->toBe('Package actif 2');
});

it('Package savingsPercent calcule l\'économie', function () {
    $packageWithDiscount = Package::create([
        'name' => 'Avec réduction',
        'session_count' => 10,
        'price' => 80,
        'regular_price' => 100,
        'validity_days' => 365,
        'is_active' => true,
    ]);

    $packageNoDiscount = Package::create([
        'name' => 'Sans réduction',
        'session_count' => 5,
        'price' => 200,
        'regular_price' => null,
        'validity_days' => 180,
        'is_active' => true,
    ]);

    expect($packageWithDiscount->savingsPercent())->toBe(20);
    expect($packageNoDiscount->savingsPercent())->toBeNull();
});

// --- Modèles GiftCard ---

it('GiftCard useAmount réduit le solde', function () {
    $giftCard = GiftCard::create([
        'code' => 'GIFT123',
        'purchaser_name' => 'Jean Dupont',
        'purchaser_email' => 'jean@example.com',
        'initial_amount' => 100,
        'remaining_amount' => 100,
        'status' => 'active',
    ]);

    $giftCard->useAmount(30);
    expect((float) $giftCard->remaining_amount)->toBe(70.0);
    expect($giftCard->status)->toBe('active');

    $giftCard->useAmount(70);
    expect((float) $giftCard->remaining_amount)->toBe(0.0);
    expect($giftCard->status)->toBe('exhausted');
});

it('GiftCard scopes active et byCode fonctionnent', function () {
    GiftCard::create([
        'code' => 'TEST123',
        'purchaser_name' => 'Client 1',
        'purchaser_email' => 'client1@example.com',
        'initial_amount' => 100,
        'remaining_amount' => 100,
        'status' => 'active',
    ]);

    GiftCard::create([
        'code' => 'EXHAUSTED',
        'purchaser_name' => 'Client 2',
        'purchaser_email' => 'client2@example.com',
        'initial_amount' => 50,
        'remaining_amount' => 0,
        'status' => 'exhausted',
    ]);

    expect(GiftCard::active()->count())->toBe(1);
    expect(GiftCard::byCode('TEST123')->count())->toBe(1);
});

// --- CRUD admin Coupons ---

it('un admin peut voir la liste des coupons', function () {
    $this->actingAs(bookingAdmin())->get('/admin/booking/coupons')->assertOk();
});

it('un admin peut créer un coupon', function () {
    $this->actingAs(bookingAdmin())->post('/admin/booking/coupons', [
        'code' => 'NEWCOUPON',
        'type' => 'percent',
        'value' => 10,
        'is_active' => 1,
    ])->assertRedirect();

    $this->assertDatabaseHas('booking_coupons', ['code' => 'NEWCOUPON', 'type' => 'percent']);
});

it('un admin peut modifier un coupon', function () {
    $coupon = Coupon::create([
        'code' => 'OLDCODE',
        'type' => 'percent',
        'value' => 10,
        'is_active' => true,
    ]);

    $this->actingAs(bookingAdmin())->put("/admin/booking/coupons/{$coupon->id}", [
        'code' => 'UPDATEDCODE',
        'type' => 'fixed',
        'value' => 25,
        'is_active' => 1,
    ])->assertRedirect();

    $this->assertDatabaseHas('booking_coupons', ['id' => $coupon->id, 'code' => 'UPDATEDCODE']);
});

it('un admin peut supprimer un coupon', function () {
    $coupon = Coupon::create([
        'code' => 'TODELETE',
        'type' => 'percent',
        'value' => 10,
        'is_active' => true,
    ]);

    $this->actingAs(bookingAdmin())->delete("/admin/booking/coupons/{$coupon->id}")->assertRedirect();
    $this->assertDatabaseMissing('booking_coupons', ['id' => $coupon->id]);
});

// --- CRUD admin Packages ---

it('un admin peut voir la liste des forfaits', function () {
    $this->actingAs(bookingAdmin())->get('/admin/booking/packages')->assertOk();
});

it('un admin peut créer un forfait', function () {
    $this->actingAs(bookingAdmin())->post('/admin/booking/packages', [
        'name' => 'Forfait Premium',
        'session_count' => 10,
        'price' => 500,
        'validity_days' => 365,
        'is_active' => 1,
        'sort_order' => 1,
    ])->assertRedirect();

    $this->assertDatabaseHas('booking_packages', ['name' => 'Forfait Premium']);
});

it('un admin peut modifier un forfait', function () {
    $package = Package::create([
        'name' => 'Forfait original',
        'session_count' => 5,
        'price' => 300,
        'validity_days' => 180,
        'is_active' => true,
    ]);

    $this->actingAs(bookingAdmin())->put("/admin/booking/packages/{$package->id}", [
        'name' => 'Forfait modifié',
        'session_count' => 8,
        'price' => 400,
        'validity_days' => 270,
        'is_active' => 1,
        'sort_order' => 0,
    ])->assertRedirect();

    $this->assertDatabaseHas('booking_packages', ['id' => $package->id, 'name' => 'Forfait modifié']);
});

it('un admin peut supprimer un forfait', function () {
    $package = Package::create([
        'name' => 'À supprimer',
        'session_count' => 5,
        'price' => 300,
        'validity_days' => 180,
        'is_active' => true,
    ]);

    $this->actingAs(bookingAdmin())->delete("/admin/booking/packages/{$package->id}")->assertRedirect();
    $this->assertDatabaseMissing('booking_packages', ['id' => $package->id]);
});

// --- CRUD admin Gift Cards ---

it('un admin peut voir la liste des cartes-cadeaux', function () {
    $this->actingAs(bookingAdmin())->get('/admin/booking/gift-cards')->assertOk();
});

it('un admin peut créer une carte-cadeau', function () {
    $this->actingAs(bookingAdmin())->post('/admin/booking/gift-cards', [
        'code' => 'NEWGIFT456',
        'purchaser_name' => 'Pierre Durand',
        'purchaser_email' => 'pierre@example.com',
        'initial_amount' => 100,
        'remaining_amount' => 100,
        'status' => 'active',
    ])->assertRedirect();

    $this->assertDatabaseHas('booking_gift_cards', ['code' => 'NEWGIFT456']);
});

it('un admin peut modifier une carte-cadeau', function () {
    $giftCard = GiftCard::create([
        'code' => 'GIFTOLD',
        'purchaser_name' => 'Ancien Client',
        'purchaser_email' => 'ancien@example.com',
        'initial_amount' => 50,
        'remaining_amount' => 50,
        'status' => 'active',
    ]);

    $this->actingAs(bookingAdmin())->put("/admin/booking/gift-cards/{$giftCard->id}", [
        'code' => 'GIFTUPDATED',
        'purchaser_name' => 'Nouveau Client',
        'purchaser_email' => 'nouveau@example.com',
        'initial_amount' => 75,
        'remaining_amount' => 75,
        'status' => 'active',
    ])->assertRedirect();

    $this->assertDatabaseHas('booking_gift_cards', ['id' => $giftCard->id, 'code' => 'GIFTUPDATED']);
});

it('un admin peut supprimer une carte-cadeau', function () {
    $giftCard = GiftCard::create([
        'code' => 'GIFTTODELETE',
        'purchaser_name' => 'Client X',
        'purchaser_email' => 'x@example.com',
        'initial_amount' => 50,
        'remaining_amount' => 50,
        'status' => 'active',
    ]);

    $this->actingAs(bookingAdmin())->delete("/admin/booking/gift-cards/{$giftCard->id}")->assertRedirect();
    $this->assertDatabaseMissing('booking_gift_cards', ['id' => $giftCard->id]);
});
