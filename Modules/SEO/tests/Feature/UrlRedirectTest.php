<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Modules\SEO\Models\UrlRedirect;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Model tests ──

test('UrlRedirect model can be created', function () {
    $redirect = UrlRedirect::create([
        'from_url' => '/old-page',
        'to_url' => '/new-page',
        'status_code' => 301,
        'is_active' => true,
    ]);

    expect($redirect)->toBeInstanceOf(UrlRedirect::class)
        ->and($redirect->from_url)->toBe('/old-page')
        ->and($redirect->to_url)->toBe('/new-page')
        ->and($redirect->status_code)->toBe(301)
        ->and($redirect->is_active)->toBeTrue()
        ->and($redirect->hits)->toBe(0);
});

test('findRedirect returns exact match', function () {
    UrlRedirect::create(['from_url' => '/exact', 'to_url' => '/dest', 'status_code' => 301, 'is_active' => true]);

    $found = UrlRedirect::findRedirect('/exact');

    expect($found)->not->toBeNull()
        ->and($found->from_url)->toBe('/exact');
});

test('findRedirect returns wildcard match', function () {
    UrlRedirect::create(['from_url' => '/old/*', 'to_url' => '/new', 'status_code' => 301, 'is_active' => true]);

    $found = UrlRedirect::findRedirect('/old/some-page');

    expect($found)->not->toBeNull()
        ->and($found->from_url)->toBe('/old/*');
});

test('findRedirect returns null when no match', function () {
    UrlRedirect::create(['from_url' => '/something', 'to_url' => '/else', 'status_code' => 301, 'is_active' => true]);

    expect(UrlRedirect::findRedirect('/nothing'))->toBeNull();
});

test('findRedirect ignores inactive redirects', function () {
    UrlRedirect::create(['from_url' => '/inactive', 'to_url' => '/dest', 'status_code' => 301, 'is_active' => false]);

    expect(UrlRedirect::findRedirect('/inactive'))->toBeNull();
});

test('recordHit increments hits counter and sets last_hit_at', function () {
    $redirect = UrlRedirect::create(['from_url' => '/hit-me', 'to_url' => '/dest', 'status_code' => 301, 'is_active' => true]);

    expect($redirect->hits)->toBe(0)
        ->and($redirect->last_hit_at)->toBeNull();

    $redirect->recordHit();
    $redirect->refresh();

    expect($redirect->hits)->toBe(1)
        ->and($redirect->last_hit_at)->not->toBeNull();
});

// ── Middleware tests ──

test('middleware redirects with 301 status', function () {
    UrlRedirect::create(['from_url' => '/old-301', 'to_url' => '/new-301', 'status_code' => 301, 'is_active' => true]);

    $response = $this->get('/old-301');

    $response->assertRedirect('/new-301');
    $response->assertStatus(301);
});

test('middleware redirects with 302 status', function () {
    UrlRedirect::create(['from_url' => '/old-302', 'to_url' => '/new-302', 'status_code' => 302, 'is_active' => true]);

    $response = $this->get('/old-302');

    $response->assertRedirect('/new-302');
    $response->assertStatus(302);
});

test('middleware passes through when no redirect found', function () {
    // A known route that exists (/ redirects to /login with 302)
    $response = $this->get('/');

    expect($response->status())->toBeIn([200, 301, 302]);
});

// ── Admin tests ──

test('admin can list redirects', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    UrlRedirect::create(['from_url' => '/a', 'to_url' => '/b', 'status_code' => 301, 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('admin.redirects.index'))
        ->assertOk()
        ->assertSee('/a');
});

test('admin can create a redirect', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $this->actingAs($user)
        ->post(route('admin.redirects.store'), [
            'from_url' => '/create-test',
            'to_url' => '/create-dest',
            'status_code' => 301,
        ])
        ->assertRedirect();

    expect(UrlRedirect::where('from_url', '/create-test')->exists())->toBeTrue();
});

test('admin can delete a redirect', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $redirect = UrlRedirect::create(['from_url' => '/delete-me', 'to_url' => '/dest', 'status_code' => 301, 'is_active' => true]);

    $this->actingAs($user)
        ->delete(route('admin.redirects.destroy', $redirect))
        ->assertRedirect();

    expect(UrlRedirect::find($redirect->id))->toBeNull();
});
