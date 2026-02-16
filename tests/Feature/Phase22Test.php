<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'super_admin']);
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'user']);
});

// --- UserController CRUD Tests ---

test('user controller exists in api module', function () {
    expect(class_exists(Modules\Api\Http\Controllers\UserController::class))->toBeTrue();
});

test('user api index requires authentication', function () {
    $response = $this->getJson('/api/v1/users');
    $response->assertStatus(401);
});

test('user api index returns paginated users for admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    User::factory()->count(3)->create();

    $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/users');

    $response->assertStatus(200)
        ->assertJsonStructure(['data', 'links', 'meta']);
});

test('user api show returns single user for admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $user = User::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'name', 'email']]);
});

test('user api store creates user for super_admin', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/users', [
        'name' => 'Nouveau Utilisateur',
        'email' => 'nouveau@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('users', ['email' => 'nouveau@test.com']);
});

test('user api store validates required fields', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/users', []);

    $response->assertStatus(422)
        ->assertJson(['success' => false]);
});

test('user api update modifies user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $user = User::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/users/{$user->id}", [
        'name' => 'Nom Modifié',
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($user->fresh()->name)->toBe('Nom Modifié');
});

test('user api destroy deletes user', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    $user = User::factory()->create();

    $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/users/{$user->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

test('user api destroy prevents self-deletion', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $response = $this->actingAs($admin, 'sanctum')->deleteJson("/api/v1/users/{$admin->id}");

    $response->assertStatus(403);
});

test('non-admin cannot access users index', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user, 'sanctum')->getJson('/api/v1/users');

    $response->assertStatus(403);
});

// --- Scramble Config Tests ---

test('scramble config exists', function () {
    expect(file_exists(config_path('scramble.php')))->toBeTrue();
});

test('scramble api version is configured', function () {
    expect(config('scramble.info.version'))->toBe('1.0.0');
});

test('scramble docs route is registered', function () {
    $routes = collect(app('router')->getRoutes()->getRoutes())
        ->pluck('uri')
        ->toArray();
    expect($routes)->toContain('docs/api');
});

// --- Module API Routes Cleanup ---

test('module api routes are cleaned (no stubs)', function () {
    $modules = ['Auth', 'Core', 'Backoffice', 'FrontTheme', 'Health', 'Logging', 'Media', 'Notifications', 'RolesPermissions', 'SEO', 'Settings', 'Storage', 'Webhooks'];

    foreach ($modules as $module) {
        $content = file_get_contents(base_path("Modules/{$module}/routes/api.php"));
        expect($content)->not->toContain('apiResource(')
            ->and($content)->toContain('centralized');
    }
});

// --- API Resource Routes ---

test('api v1 has crud routes for users', function () {
    $content = file_get_contents(base_path('routes/api/v1.php'));
    expect($content)->toContain('apiResource')
        ->toContain('UserController');
});
