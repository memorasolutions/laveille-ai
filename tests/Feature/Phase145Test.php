<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\CookieCategory;
use App\Models\User;
use Database\Seeders\CookieCategorySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(CookieCategorySeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

it('seeds 4 cookie categories', function () {
    expect(CookieCategory::count())->toBe(4);
});

it('has active scope', function () {
    expect(CookieCategory::active()->count())->toBe(4);
});

it('has ordered scope', function () {
    $categories = CookieCategory::ordered()->pluck('name')->toArray();
    expect($categories)->toBe(['essential', 'functional', 'analytics', 'marketing']);
});

it('isRequired returns true for essential category', function () {
    $essential = CookieCategory::where('name', 'essential')->first();
    expect($essential->isRequired())->toBeTrue();

    $analytics = CookieCategory::where('name', 'analytics')->first();
    expect($analytics->isRequired())->toBeFalse();
});

it('admin can access cookie categories index', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.cookie-categories.index'))
        ->assertOk();
});

it('admin can access cookie categories create', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.cookie-categories.create'))
        ->assertOk();
});

it('admin can store a new cookie category', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.cookie-categories.store'), [
        'name' => 'preferences',
        'label' => 'Cookies de préférences',
        'description' => 'Test description',
        'order' => 5,
        'is_active' => '1',
    ]);
    $response->assertRedirect(route('admin.cookie-categories.index'));
    expect(CookieCategory::where('name', 'preferences')->exists())->toBeTrue();
});

it('admin can update a cookie category', function () {
    $category = CookieCategory::where('name', 'analytics')->first();
    $response = $this->actingAs($this->admin)->put(route('admin.cookie-categories.update', $category), [
        'name' => 'analytics',
        'label' => 'Statistiques modifié',
        'order' => 3,
        'is_active' => '1',
    ]);
    $response->assertRedirect(route('admin.cookie-categories.index'));
    expect($category->fresh()->label)->toBe('Statistiques modifié');
});

it('admin cannot delete required category', function () {
    $required = CookieCategory::where('name', 'essential')->first();
    $this->actingAs($this->admin)
        ->delete(route('admin.cookie-categories.destroy', $required))
        ->assertRedirect();
    expect(CookieCategory::where('id', $required->id)->exists())->toBeTrue();
});

it('admin can delete optional category', function () {
    $optional = CookieCategory::where('name', 'marketing')->first();
    $this->actingAs($this->admin)
        ->delete(route('admin.cookie-categories.destroy', $optional))
        ->assertRedirect();
    expect(CookieCategory::where('id', $optional->id)->exists())->toBeFalse();
});

it('non-admin cannot access cookie categories admin', function () {
    $user = User::factory()->create();
    $user->assignRole('user');
    $this->actingAs($user)
        ->get(route('admin.cookie-categories.index'))
        ->assertForbidden();
});
