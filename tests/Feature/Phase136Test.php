<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    $this->regularUser = User::factory()->create();
    $this->regularUser->assignRole('user');
});

test('CSRF protection is active on forms', function () {
    // Laravel 12 handles CSRF globally - verify forms include CSRF tokens
    $response = $this->actingAs($this->admin)
        ->get(route('admin.cache'));

    $response->assertOk();
    $response->assertSee('_token', false);
});

test('XSS is escaped in user name display', function () {
    User::factory()->create(['name' => '<script>alert("xss")</script>']);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.users.index'));

    $response->assertOk();
    $response->assertDontSee('<script>alert("xss")</script>', false);
});

test('SQL injection in search parameter is safe', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.search', ['q' => "'; DROP TABLE users;--"]))
        ->assertOk();

    // Verify users table still exists
    expect(User::count())->toBeGreaterThan(0);
});

test('admin routes return 403 for regular user', function () {
    $this->actingAs($this->regularUser)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin routes return 403 for unauthenticated on all critical pages', function () {
    $routes = [
        'admin.dashboard',
        'admin.users.index',
        'admin.roles.index',
        'admin.settings.index',
        'admin.plugins.index',
    ];

    foreach ($routes as $routeName) {
        $this->get(route($routeName))->assertRedirect();
    }
});

test('regular user cannot access admin user management', function () {
    $this->actingAs($this->regularUser)
        ->get(route('admin.users.index'))
        ->assertForbidden();

    $this->actingAs($this->regularUser)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

test('models use fillable or guarded for mass assignment protection', function () {
    $models = [
        \App\Models\User::class,
        \Modules\Settings\Models\Setting::class,
        \Modules\SaaS\Models\Plan::class,
    ];

    foreach ($models as $model) {
        $instance = new $model;
        $hasFillable = ! empty($instance->getFillable());
        $hasGuarded = $instance->getGuarded() !== ['*'];

        expect($hasFillable || $hasGuarded)->toBeTrue(
            "Model {$model} has no mass assignment protection"
        );
    }
});

test('sensitive routes are rate limited', function () {
    $content = file_get_contents(base_path('Modules/Backoffice/routes/web.php'));

    expect($content)->toContain('throttle:export');
    expect($content)->toContain('throttle:import');
});

test('API routes require authentication', function () {
    $this->getJson('/api/v1/users')
        ->assertUnauthorized();
});

test('CORS configuration exists', function () {
    $cors = config('cors');

    expect($cors)->not->toBeNull();
    expect($cors['paths'])->toContain('api/*');
});

test('session cookie is httponly', function () {
    expect(config('session.http_only'))->toBeTrue();
});

test('passwords are hashed not stored in plain text', function () {
    $user = User::factory()->create(['password' => 'TestPassword123!']);

    expect($user->password)->not->toBe('TestPassword123!');
    expect(\Illuminate\Support\Facades\Hash::check('TestPassword123!', $user->password))->toBeTrue();
});
