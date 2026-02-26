<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\ActivityLogsTable;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);
});

test('activity log page loads for admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get('/admin/activity-logs')
        ->assertOk()
        ->assertSee('Journal');
});

test('activity log page denied for non-admin', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get('/admin/activity-logs')
        ->assertForbidden();
});

test('export csv route works', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin)->get('/admin/activity-logs/export');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('text/csv');
});

test('purge deletes old entries', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $fresh = Activity::create([
        'log_name' => 'default',
        'description' => 'Fresh activity',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $old = Activity::create([
        'log_name' => 'default',
        'description' => 'Old activity',
        'created_at' => now()->subDays(200),
        'updated_at' => now()->subDays(200),
    ]);

    $this->actingAs($admin)->delete('/admin/activity-logs/purge');

    expect(Activity::find($old->id))->toBeNull();
    expect(Activity::find($fresh->id))->not->toBeNull();
});

test('purge returns success message', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->delete('/admin/activity-logs/purge')
        ->assertRedirect()
        ->assertSessionHas('success');
});

test('activity log search works via livewire', function () {
    Livewire::test(ActivityLogsTable::class)
        ->set('search', 'test_xyz')
        ->assertOk();
});

test('activity log filter by causer works', function () {
    $user = User::factory()->create();

    Livewire::test(ActivityLogsTable::class)
        ->set('filterCauser', (string) $user->id)
        ->assertOk();
});

test('activity log filter by event works', function () {
    Livewire::test(ActivityLogsTable::class)
        ->set('filterEvent', 'created')
        ->assertOk();
});

test('activity log date filter works', function () {
    Livewire::test(ActivityLogsTable::class)
        ->set('dateFrom', now()->subDays(7)->format('Y-m-d'))
        ->set('dateTo', now()->format('Y-m-d'))
        ->assertOk();
});

test('activity log reset filters works', function () {
    Livewire::test(ActivityLogsTable::class)
        ->set('search', 'foo')
        ->set('filterEvent', 'created')
        ->set('dateFrom', '2026-01-01')
        ->call('resetFilters')
        ->assertSet('search', '')
        ->assertSet('filterEvent', '')
        ->assertSet('dateFrom', '');
});

test('activity detail modal opens', function () {
    $activity = Activity::create([
        'log_name' => 'default',
        'description' => 'Test action',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Livewire::test(ActivityLogsTable::class)
        ->call('showDetail', $activity->id)
        ->assertSet('detailActivityId', $activity->id);
});

test('activity detail modal closes', function () {
    Livewire::test(ActivityLogsTable::class)
        ->set('detailActivityId', 1)
        ->call('closeDetail')
        ->assertSet('detailActivityId', null);
});

test('activity log filter by log name works', function () {
    Livewire::test(ActivityLogsTable::class)
        ->set('filterLogName', 'default')
        ->assertOk();
});

test('guest cannot access activity logs', function () {
    $this->get('/admin/activity-logs')
        ->assertRedirect('/login');
});

test('guest cannot export activity logs', function () {
    $this->get('/admin/activity-logs/export')
        ->assertRedirect('/login');
});

test('guest cannot purge activity logs', function () {
    $this->delete('/admin/activity-logs/purge')
        ->assertRedirect('/login');
});
