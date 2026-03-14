<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Modules\Editor\Models\Shortcode;
use Modules\Editor\Services\ShortcodeService;
use Spatie\Permission\Models\Role;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

function adminUser154(): User
{
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);

    return User::factory()->create()->assignRole('super_admin');
}

test('shortcode model can be created', function () {
    $shortcode = Shortcode::factory()->create();
    $this->assertDatabaseHas('shortcodes', ['id' => $shortcode->id]);
});

test('shortcode has fillable attributes', function () {
    $shortcode = Shortcode::factory()->create([
        'tag' => 'test',
        'name' => 'Test Shortcode',
        'html_template' => '<div>Test</div>',
    ]);
    $this->assertEquals('test', $shortcode->tag);
    $this->assertEquals('Test Shortcode', $shortcode->name);
    $this->assertEquals('<div>Test</div>', $shortcode->html_template);
});

test('shortcode casts parameters to array', function () {
    $shortcode = Shortcode::factory()->create(['parameters' => ['url', 'color']]);
    $this->assertIsArray($shortcode->parameters);
    $this->assertEquals(['url', 'color'], $shortcode->parameters);
});

test('shortcode scope active filters inactive', function () {
    Shortcode::factory()->count(2)->create(['is_active' => true]);
    Shortcode::factory()->create(['is_active' => false]);
    $this->assertEquals(2, Shortcode::active()->count());
});

test('shortcode factory creates valid model', function () {
    $shortcode = Shortcode::factory()->create();
    $this->assertNotNull($shortcode);
    $this->assertDatabaseHas('shortcodes', ['id' => $shortcode->id]);
});

test('shortcode factory inactive state', function () {
    $shortcode = Shortcode::factory()->inactive()->create();
    $this->assertFalse($shortcode->is_active);
});

test('shortcode factory self closing state', function () {
    $shortcode = Shortcode::factory()->selfClosing()->create();
    $this->assertFalse($shortcode->has_content);
});

test('shortcode service renders simple shortcode', function () {
    Shortcode::factory()->create([
        'tag' => 'button',
        'html_template' => '<a href="{{ $url }}" class="btn-{{ $color }}">{{ $content }}</a>',
        'parameters' => ['url', 'color'],
        'has_content' => true,
        'is_active' => true,
    ]);
    $service = new ShortcodeService;
    $result = $service->render('[button url="/test" color="primary"]Click[/button]');
    $this->assertStringContainsString('href="/test"', $result);
    $this->assertStringContainsString('btn-primary', $result);
    $this->assertStringContainsString('Click', $result);
});

test('shortcode service renders self-closing shortcode', function () {
    Shortcode::factory()->create([
        'tag' => 'youtube',
        'html_template' => '<iframe src="https://www.youtube.com/embed/{{ $id }}"></iframe>',
        'parameters' => ['id'],
        'has_content' => false,
        'is_active' => true,
    ]);
    $service = new ShortcodeService;
    $result = $service->render('[youtube id="abc123"]');
    $this->assertStringContainsString('youtube.com/embed/abc123', $result);
});

test('shortcode service ignores unknown shortcodes', function () {
    $service = new ShortcodeService;
    $original = '[unknown]test[/unknown]';
    $result = $service->render($original);
    $this->assertEquals($original, $result);
});

test('shortcode service ignores inactive shortcodes', function () {
    Shortcode::factory()->create([
        'tag' => 'inactive_tag',
        'is_active' => false,
    ]);
    $service = new ShortcodeService;
    $original = '[inactive_tag]content[/inactive_tag]';
    $result = $service->render($original);
    $this->assertEquals($original, $result);
});

test('shortcode service returns content without shortcodes unchanged', function () {
    $service = new ShortcodeService;
    $result = $service->render('Hello world');
    $this->assertEquals('Hello world', $result);
});

test('shortcode service renders multiple shortcodes', function () {
    Shortcode::factory()->create([
        'tag' => 'button',
        'html_template' => '<a href="{{ $url }}">{{ $content }}</a>',
        'parameters' => ['url'],
        'has_content' => true,
        'is_active' => true,
    ]);
    Shortcode::factory()->create([
        'tag' => 'badge',
        'html_template' => '<span class="badge">{{ $content }}</span>',
        'parameters' => [],
        'has_content' => true,
        'is_active' => true,
    ]);
    $service = new ShortcodeService;
    $content = '[button url="/test"]Click[/button] and [badge]New[/badge]';
    $result = $service->render($content);
    $this->assertStringContainsString('href="/test"', $result);
    $this->assertStringContainsString('badge', $result);
});

test('shortcode service parseAttributes works', function () {
    $service = new ShortcodeService;
    $attributes = $service->parseAttributes(' url="/test" color="blue"');
    $this->assertEquals(['url' => '/test', 'color' => 'blue'], $attributes);
});

test('render_shortcodes helper function exists', function () {
    $this->assertTrue(function_exists('render_shortcodes'));
});

test('render_shortcodes helper renders content', function () {
    Shortcode::factory()->create([
        'tag' => 'button',
        'html_template' => '<a href="{{ $url }}" class="btn-{{ $color }}">{{ $content }}</a>',
        'parameters' => ['url', 'color'],
        'has_content' => true,
        'is_active' => true,
    ]);
    $result = render_shortcodes('[button url="/x" color="info"]Go[/button]');
    $this->assertStringContainsString('href="/x"', $result);
    $this->assertStringContainsString('btn-info', $result);
});

test('admin can view shortcodes index', function () {
    $this->actingAs(adminUser154())->get(route('admin.shortcodes.index'))->assertOk();
});

test('admin can view create shortcode form', function () {
    $this->actingAs(adminUser154())->get(route('admin.shortcodes.create'))->assertOk();
});

test('admin can store a shortcode', function () {
    $this->actingAs(adminUser154())->post(route('admin.shortcodes.store'), [
        'tag' => 'testsc',
        'name' => 'Test Shortcode',
        'html_template' => '<div>Test</div>',
        'has_content' => 1,
    ])->assertRedirect(route('admin.shortcodes.index'));
    $this->assertDatabaseHas('shortcodes', ['tag' => 'testsc']);
});

test('admin can update a shortcode', function () {
    $shortcode = Shortcode::factory()->create(['tag' => 'updatable', 'name' => 'Old Name']);
    $this->actingAs(adminUser154())->put(route('admin.shortcodes.update', $shortcode), [
        'tag' => 'updatable',
        'name' => 'New Name',
        'html_template' => $shortcode->html_template,
    ])->assertRedirect(route('admin.shortcodes.index'));
    $this->assertEquals('New Name', $shortcode->fresh()->name);
});

test('admin can delete a shortcode', function () {
    $shortcode = Shortcode::factory()->create();
    $this->actingAs(adminUser154())->delete(route('admin.shortcodes.destroy', $shortcode))
        ->assertRedirect(route('admin.shortcodes.index'));
    $this->assertDatabaseMissing('shortcodes', ['id' => $shortcode->id]);
});

test('store validates tag format', function () {
    $this->actingAs(adminUser154())->post(route('admin.shortcodes.store'), [
        'tag' => 'INVALID TAG!',
        'name' => 'Test',
        'html_template' => '<div>Test</div>',
    ])->assertSessionHasErrors('tag');
});

test('store validates unique tag', function () {
    Shortcode::factory()->create(['tag' => 'taken']);
    $this->actingAs(adminUser154())->post(route('admin.shortcodes.store'), [
        'tag' => 'taken',
        'name' => 'Test',
        'html_template' => '<div>Test</div>',
    ])->assertSessionHasErrors('tag');
});

test('guest cannot access shortcodes', function () {
    $this->get(route('admin.shortcodes.index'))->assertRedirect('/login');
});
