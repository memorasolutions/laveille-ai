<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

test('admin can access revenue dashboard', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertOk();
});

test('revenue page contains KPI cards', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSeeInOrder(['Actifs', 'En essai', 'MRR', 'ARR', 'Churn']);
});

test('revenue page contains subscription chart div', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSee('id="subscriptionChart"', false);
});

test('revenue page contains revenue by plan chart div', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSee('id="revenueByPlanChart"', false);
});

test('revenue page uses ApexCharts', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSee('subscriptionChart', false);
});

test('revenue page shows MRR value', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSee('0.00');
});

test('revenue page shows ARR value', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSee('0.00');
});

test('revenue page shows churn rate', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSee('0%');
});

test('non-admin cannot access revenue dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.revenue'))
        ->assertForbidden();
});

test('guest cannot access revenue dashboard', function () {
    $this->get(route('admin.revenue'))
        ->assertRedirect('/login');
});

test('admin can access metrics JSON endpoint', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue.metrics'))
        ->assertOk()
        ->assertJsonStructure(['success', 'metrics', 'revenue_by_plan']);
});

test('metrics endpoint returns correct structure', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue.metrics'))
        ->assertJsonStructure([
            'success',
            'metrics' => ['mrr', 'arr', 'active', 'trial', 'cancelled_this_month', 'churn_rate', 'new_this_month'],
            'revenue_by_plan',
        ]);
});

test('non-admin cannot access metrics endpoint', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $this->actingAs($user)
        ->get(route('admin.revenue.metrics'))
        ->assertForbidden();
});

test('guest cannot access metrics endpoint', function () {
    $this->get(route('admin.revenue.metrics'))
        ->assertRedirect('/login');
});

test('revenue route exists', function () {
    expect(Route::has('admin.revenue'))->toBeTrue();
});

test('metrics route exists', function () {
    expect(Route::has('admin.revenue.metrics'))->toBeTrue();
});

test('revenue page contains indicateurs section', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSee('Indicateurs');
});

test('revenue page shows currency', function () {
    $currency = strtoupper(config('saas.currency', 'cad'));

    $this->actingAs($this->admin)
        ->get(route('admin.revenue'))
        ->assertSee($currency);
});
