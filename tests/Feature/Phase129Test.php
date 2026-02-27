<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Blog\Models\Article;
use Modules\SaaS\Models\Plan;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole($role);
});

// --- DB Transactions ---

it('stores user with role atomically', function () {
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $response = $this->actingAs($this->admin)->post(route('admin.users.store'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
        'roles' => [$role->id],
    ]);

    $response->assertRedirect(route('admin.users.index'));
    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    $user = User::where('email', 'john@example.com')->first();
    expect($user->hasRole('admin'))->toBeTrue();
});

it('stores role with permissions atomically', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => 'content_manager',
        'permissions' => [],
    ]);

    $response->assertRedirect(route('admin.roles.index'));
    $this->assertDatabaseHas('roles', ['name' => 'content_manager']);
});

// --- FormRequest Validation ---

it('rejects setting key with spaces', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.settings.store'), [
        'key' => 'has space',
        'value' => 'test',
    ]);

    $response->assertSessionHasErrors('key');
});

it('rejects setting with invalid type', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.settings.store'), [
        'key' => 'test_key',
        'value' => 'test',
        'type' => 'invalid',
    ]);

    $response->assertSessionHasErrors('type');
});

it('rejects duplicate role name', function () {
    Role::firstOrCreate(['name' => 'editor', 'guard_name' => 'web']);

    $response = $this->actingAs($this->admin)->post(route('admin.roles.store'), [
        'name' => 'editor',
    ]);

    $response->assertSessionHasErrors('name');
});

it('rejects duplicate setting key', function () {
    Setting::create(['key' => 'existing_key', 'value' => 'val', 'group' => 'general']);

    $response = $this->actingAs($this->admin)->post(route('admin.settings.store'), [
        'key' => 'existing_key',
        'value' => 'new',
    ]);

    $response->assertSessionHasErrors('key');
});

// --- Policies ---

it('registers ArticlePolicy for Article model', function () {
    $policy = Gate::getPolicyFor(Article::class);
    expect($policy)->toBeInstanceOf(\Modules\Blog\Policies\ArticlePolicy::class);
});

it('registers SettingPolicy for Setting model', function () {
    $policy = Gate::getPolicyFor(Setting::class);
    expect($policy)->toBeInstanceOf(\Modules\Settings\Policies\SettingPolicy::class);
});

it('registers PlanPolicy for Plan model', function () {
    $policy = Gate::getPolicyFor(Plan::class);
    expect($policy)->toBeInstanceOf(\Modules\SaaS\Policies\PlanPolicy::class);
});

it('non-admin cannot access settings create', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.settings.create'));

    expect($response->status())->toBeIn([302, 403]);
});

it('admin can access settings create', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.settings.create'));

    $response->assertOk();
});
