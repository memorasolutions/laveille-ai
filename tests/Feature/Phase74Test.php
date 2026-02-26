<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Newsletter\Models\Subscriber;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

test('dashboard accessible admin', function () {
    $this->get(route('admin.dashboard'))
        ->assertStatus(200)
        ->assertViewIs('backoffice::dashboard.index');
});

test('dashboard non-admin retourne 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('dashboard non-auth redirige login', function () {
    auth()->logout();
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('usersByMonth a 12 mois', function () {
    $response = $this->get(route('admin.dashboard'));
    $this->assertCount(12, $response->viewData('usersByMonth'));
});

test('articlesByMonth a 12 mois', function () {
    $response = $this->get(route('admin.dashboard'));
    $this->assertCount(12, $response->viewData('articlesByMonth'));
});

test('chaque item usersByMonth a label et count', function () {
    $response = $this->get(route('admin.dashboard'));
    foreach ($response->viewData('usersByMonth') as $item) {
        expect($item)->toHaveKeys(['label', 'count']);
    }
});

test('usersCount reflète les utilisateurs en DB', function () {
    User::factory()->count(2)->create();
    $this->get(route('admin.dashboard'))
        ->assertViewHas('usersCount', User::count());
});

test('newUsersThisWeek compte cette semaine', function () {
    User::factory()->create(['created_at' => now()->subDays(10)]);
    $recent = User::factory()->create();
    $this->get(route('admin.dashboard'))
        ->assertViewHas('newUsersThisWeek', 2);
});

test('subscribersGrowth compte 30 derniers jours', function () {
    Subscriber::create(['email' => 'growth@test.com', 'token' => 'tok-growth']);
    $this->get(route('admin.dashboard'))
        ->assertViewHas('subscribersGrowth', 1);
});

test('recentActivities est présente dans la vue', function () {
    $this->get(route('admin.dashboard'))
        ->assertViewHas('recentActivities');
});
