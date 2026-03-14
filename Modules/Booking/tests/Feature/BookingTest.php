<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\Appointment;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService;
use Modules\Booking\Models\BookingSetting;
use Modules\Booking\Models\DateOverride;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
});

function createService(array $overrides = []): BookingService
{
    return BookingService::create(array_merge([
        'name' => 'Test Service',
        'slug' => 'test-service-'.uniqid(),
        'duration_minutes' => 60,
        'price' => 100,
        'is_active' => true,
        'color' => '#007bff',
    ], $overrides));
}

function createAppointmentWithDeps(array $overrides = []): Appointment
{
    $service = createService();
    $customer = BookingCustomer::create([
        'first_name' => 'Jean',
        'last_name' => 'Tremblay',
        'email' => 'jean'.uniqid().'@example.com',
        'phone' => '514-555-0100',
    ]);

    return Appointment::create(array_merge([
        'service_id' => $service->id,
        'customer_id' => $customer->id,
        'start_at' => now()->addDay(),
        'end_at' => now()->addDay()->addMinutes(60),
        'status' => 'pending',
        'cancel_token' => 'token-'.uniqid(),
    ], $overrides));
}

function adminUser(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('manage_booking');

    return $user;
}

// --- Modèles ---

it('BookingService a les scopes active et ordered', function () {
    createService(['is_active' => true, 'sort_order' => 2]);
    createService(['is_active' => false, 'sort_order' => 1]);

    expect(BookingService::active()->count())->toBe(1);
    expect(BookingService::ordered()->count())->toBe(2);
});

it('Appointment a les relations service et customer', function () {
    $appointment = createAppointmentWithDeps();

    expect($appointment->service)->toBeInstanceOf(BookingService::class);
    expect($appointment->customer)->toBeInstanceOf(BookingCustomer::class);
});

it('BookingCustomer a l\'accesseur full_name', function () {
    $customer = BookingCustomer::create([
        'first_name' => 'Marie',
        'last_name' => 'Dupont',
        'email' => 'marie@example.com',
    ]);

    expect($customer->full_name)->toBe('Marie Dupont');
});

it('BookingSetting get et set fonctionnent', function () {
    BookingSetting::set('test_key', 'test_value');

    expect(BookingSetting::get('test_key'))->toBe('test_value');
    expect(BookingSetting::get('inexistant', 'default'))->toBe('default');
});

it('DateOverride a les scopes blocked et available', function () {
    $user = User::factory()->create();
    DateOverride::create(['date' => now()->addDays(1)->toDateString(), 'override_type' => 'blocked', 'all_day' => true, 'created_by_id' => $user->id]);
    DateOverride::create(['date' => now()->addDays(2)->toDateString(), 'override_type' => 'available', 'all_day' => false, 'created_by_id' => $user->id]);

    expect(DateOverride::blocked()->count())->toBe(1);
    expect(DateOverride::available()->count())->toBe(1);
});

// --- Routes publiques ---

it('la page wizard publique est accessible', function () {
    $this->get('/rendez-vous')->assertOk();
});

it('la page manage avec cancel_token fonctionne', function () {
    $appointment = createAppointmentWithDeps(['status' => 'confirmed']);

    $this->get('/rendez-vous/manage/'.$appointment->cancel_token)->assertOk();
});

// --- Routes admin auth ---

it('les routes admin nécessitent l\'authentification', function () {
    $this->get('/admin/booking/services')->assertRedirect('/login');
});

it('un admin peut voir la liste des services', function () {
    $this->actingAs(adminUser())->get('/admin/booking/services')->assertOk();
});

it('un admin peut créer un service', function () {
    $response = $this->actingAs(adminUser())->post('/admin/booking/services', [
        'name' => 'Consultation',
        'duration_minutes' => 90,
        'price' => 120,
        'is_active' => 1,
        'color' => '#FFA500',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('booking_services', ['name' => 'Consultation']);
});

it('un admin peut modifier un service', function () {
    $service = createService();

    $response = $this->actingAs(adminUser())->put("/admin/booking/services/{$service->id}", [
        'name' => 'Service Modifié',
        'duration_minutes' => 120,
        'price' => 200,
        'is_active' => 1,
        'color' => '#00FF00',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('booking_services', ['id' => $service->id, 'name' => 'Service Modifié']);
});

it('un admin peut supprimer un service', function () {
    $service = createService();

    $response = $this->actingAs(adminUser())->delete("/admin/booking/services/{$service->id}");

    $response->assertRedirect();
    $this->assertDatabaseMissing('booking_services', ['id' => $service->id]);
});

it('un admin peut voir les rendez-vous', function () {
    $this->actingAs(adminUser())->get('/admin/booking/appointments')->assertOk();
});

it('un admin peut voir le détail d\'un rendez-vous', function () {
    $appointment = createAppointmentWithDeps();

    $this->actingAs(adminUser())->get("/admin/booking/appointments/{$appointment->id}")->assertOk();
});

it('un admin peut changer le statut d\'un rendez-vous', function () {
    $appointment = createAppointmentWithDeps();

    $response = $this->actingAs(adminUser())->put("/admin/booking/appointments/{$appointment->id}", [
        'status' => 'confirmed',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('booking_appointments', ['id' => $appointment->id, 'status' => 'confirmed']);
});
