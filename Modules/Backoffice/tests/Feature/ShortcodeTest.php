<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Tests\Feature;

use Modules\Editor\Models\Shortcode;
use Modules\Editor\Services\ShortcodeService;
use Spatie\Permission\Models\Permission;

uses(\Tests\TestCase::class, \Illuminate\Foundation\Testing\RefreshDatabase::class);

function createShortcodeAdmin()
{
    $user = \App\Models\User::factory()->create();
    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::firstOrCreate(['name' => 'manage_shortcodes', 'guard_name' => 'web']));
    $user->assignRole($role);

    return $user;
}

it('guest cannot access shortcodes index', function () {
    $this->get(route('admin.shortcodes.index'))
        ->assertRedirect(route('login'));
});

it('admin can view shortcodes index', function () {
    $admin = createShortcodeAdmin();
    Shortcode::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.shortcodes.index'))
        ->assertOk()
        ->assertSee('Shortcodes');
});

it('admin can view create form', function () {
    $admin = createShortcodeAdmin();

    $this->actingAs($admin)
        ->get(route('admin.shortcodes.create'))
        ->assertOk()
        ->assertSee('Nouveau shortcode');
});

it('admin can store a valid shortcode', function () {
    $admin = createShortcodeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.shortcodes.store'), [
            'tag' => 'test_shortcode',
            'name' => 'Test Shortcode',
            'description' => 'Test description',
            'html_template' => '<div>{{ $content }}</div>',
            'parameters' => '["content"]',
            'has_content' => true,
        ])
        ->assertRedirect(route('admin.shortcodes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('shortcodes', ['tag' => 'test_shortcode']);
});

it('store validates required fields', function () {
    $admin = createShortcodeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.shortcodes.store'), [])
        ->assertSessionHasErrors(['tag', 'name', 'html_template']);
});

it('store validates tag format', function () {
    $admin = createShortcodeAdmin();

    $this->actingAs($admin)
        ->post(route('admin.shortcodes.store'), [
            'tag' => 'Invalid-Tag',
            'name' => 'Test',
            'html_template' => '<div></div>',
        ])
        ->assertSessionHasErrors('tag');
});

it('store validates unique tag', function () {
    $admin = createShortcodeAdmin();
    Shortcode::factory()->create(['tag' => 'existing_tag']);

    $this->actingAs($admin)
        ->post(route('admin.shortcodes.store'), [
            'tag' => 'existing_tag',
            'name' => 'Different',
            'html_template' => '<div></div>',
        ])
        ->assertSessionHasErrors('tag');
});

it('admin can view edit form', function () {
    $admin = createShortcodeAdmin();
    $shortcode = Shortcode::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.shortcodes.edit', $shortcode))
        ->assertOk()
        ->assertSee($shortcode->tag);
});

it('admin can update a shortcode', function () {
    $admin = createShortcodeAdmin();
    $shortcode = Shortcode::factory()->create(['name' => 'Old Name']);

    $this->actingAs($admin)
        ->put(route('admin.shortcodes.update', $shortcode), [
            'tag' => $shortcode->tag,
            'name' => 'Updated Name',
            'html_template' => '<div>new</div>',
            'parameters' => '["param1"]',
            'has_content' => false,
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.shortcodes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('shortcodes', ['id' => $shortcode->id, 'name' => 'Updated Name']);
});

it('admin can deactivate a shortcode via update', function () {
    $admin = createShortcodeAdmin();
    $shortcode = Shortcode::factory()->create(['is_active' => true]);

    $this->actingAs($admin)
        ->put(route('admin.shortcodes.update', $shortcode), [
            'tag' => $shortcode->tag,
            'name' => $shortcode->name,
            'html_template' => $shortcode->html_template,
            'is_active' => false,
        ])
        ->assertRedirect(route('admin.shortcodes.index'));

    expect($shortcode->fresh()->is_active)->toBeFalse();
});

it('admin can delete a shortcode', function () {
    $admin = createShortcodeAdmin();
    $shortcode = Shortcode::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.shortcodes.destroy', $shortcode))
        ->assertRedirect(route('admin.shortcodes.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('shortcodes', ['id' => $shortcode->id]);
});

it('shortcode service renders content correctly', function () {
    Shortcode::factory()->create([
        'tag' => 'test_render',
        'html_template' => '<div class="test">{{ $content }}</div>',
        'has_content' => true,
        'is_active' => true,
    ]);

    $service = app(ShortcodeService::class);
    $result = $service->render('Before [test_render]inner[/test_render] after.');

    expect($result)->toBe('Before <div class="test">inner</div> after.');
});

it('shortcode service ignores inactive shortcodes', function () {
    Shortcode::factory()->create([
        'tag' => 'inactive_tag',
        'html_template' => '<div>Rendered</div>',
        'has_content' => false,
        'is_active' => false,
    ]);

    $service = app(ShortcodeService::class);
    $content = 'Content [inactive_tag] should remain.';

    expect($service->render($content))->toBe($content);
});

it('shortcode service renders attributes', function () {
    Shortcode::factory()->create([
        'tag' => 'link',
        'html_template' => '<a href="{{ $url }}" class="{{ $class }}">{{ $content }}</a>',
        'parameters' => ['url', 'class'],
        'has_content' => true,
        'is_active' => true,
    ]);

    $service = app(ShortcodeService::class);
    $result = $service->render('[link url="https://example.com" class="btn"]Click[/link]');

    expect($result)->toBe('<a href="https://example.com" class="btn">Click</a>');
});
