<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Booking\Models\BookingCustomer;
use Modules\Booking\Models\BookingService as ServiceModel;
use Modules\Booking\Models\WaitlistEntry;
use Modules\Booking\Services\WaitlistService;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('join crée une entrée waitlist avec status waiting', function () {
    $service = ServiceModel::factory()->create();
    $customer = BookingCustomer::factory()->create();

    $entry = app(WaitlistService::class)->join($service->id, $customer->id, '2026-07-15', '10:00');

    expect($entry)->toBeInstanceOf(WaitlistEntry::class);
    expect($entry->service_id)->toBe($service->id);
    expect($entry->customer_id)->toBe($customer->id);
    expect($entry->preferred_date->format('Y-m-d'))->toBe('2026-07-15');
    expect($entry->preferred_time_start)->toBe('10:00');
    expect($entry->status)->toBe('waiting');
});

it('notifyAvailability notifie les entrées en attente pour le bon service+date', function () {
    $service = ServiceModel::factory()->create();
    WaitlistEntry::factory()->count(3)->create([
        'service_id' => $service->id,
        'preferred_date' => '2026-07-15',
        'status' => 'waiting',
    ]);

    $count = app(WaitlistService::class)->notifyAvailability('2026-07-15', $service->id);

    expect($count)->toBe(3);
    expect(WaitlistEntry::where('status', 'notified')->count())->toBe(3);
});

it('notifyAvailability ne notifie pas les entrées d\'un autre service', function () {
    $serviceA = ServiceModel::factory()->create();
    $serviceB = ServiceModel::factory()->create();

    WaitlistEntry::factory()->create([
        'service_id' => $serviceA->id,
        'preferred_date' => '2026-07-15',
        'status' => 'waiting',
    ]);
    WaitlistEntry::factory()->create([
        'service_id' => $serviceB->id,
        'preferred_date' => '2026-07-15',
        'status' => 'waiting',
    ]);

    $count = app(WaitlistService::class)->notifyAvailability('2026-07-15', $serviceA->id);

    expect($count)->toBe(1);
    expect(WaitlistEntry::where('service_id', $serviceB->id)->where('status', 'waiting')->count())->toBe(1);
});

it('expireStale expire les entries dont expires_at est passé', function () {
    WaitlistEntry::factory()->create([
        'status' => 'notified',
        'expires_at' => now()->subHour(),
    ]);
    WaitlistEntry::factory()->create([
        'status' => 'notified',
        'expires_at' => now()->addHour(),
    ]);
    WaitlistEntry::factory()->create([
        'status' => 'waiting',
    ]);

    $count = app(WaitlistService::class)->expireStale();

    expect($count)->toBe(1);
    expect(WaitlistEntry::where('status', 'expired')->count())->toBe(1);
    expect(WaitlistEntry::where('status', 'notified')->count())->toBe(1);
    expect(WaitlistEntry::where('status', 'waiting')->count())->toBe(1);
});

it('scope waiting retourne uniquement les entrées en attente', function () {
    WaitlistEntry::factory()->create(['status' => 'waiting']);
    WaitlistEntry::factory()->create(['status' => 'waiting']);
    WaitlistEntry::factory()->create(['status' => 'notified']);
    WaitlistEntry::factory()->create(['status' => 'expired']);

    expect(WaitlistEntry::waiting()->count())->toBe(2);
});
