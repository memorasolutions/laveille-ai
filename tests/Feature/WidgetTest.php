<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder;
use Modules\Widget\Models\Widget;
use Modules\Widget\Services\WidgetService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

it('redirects guest to login', function () {
    $this->get(route('admin.widgets.index'))
        ->assertRedirect(route('login'));
});

it('forbids editor from accessing widgets', function () {
    $editor = User::factory()->create();
    $editor->assignRole('editor');

    $this->actingAs($editor)
        ->get(route('admin.widgets.index'))
        ->assertForbidden();
});

it('allows admin to view widget index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.widgets.index'))
        ->assertOk()
        ->assertSee('Widgets');
});

it('allows admin to view create form', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->get(route('admin.widgets.create'))
        ->assertOk()
        ->assertSee('Nouveau widget');
});

it('allows admin to store a widget', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.widgets.store'), [
            'zone' => 'sidebar',
            'type' => 'html',
            'title' => 'Test Widget',
            'content' => '<p>Hello</p>',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.widgets.index'));

    $this->assertDatabaseHas('widgets', [
        'title' => 'Test Widget',
        'zone' => 'sidebar',
        'type' => 'html',
    ]);
});

it('allows admin to edit a widget', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $widget = Widget::create([
        'zone' => 'footer',
        'type' => 'custom_text',
        'title' => 'Mon widget',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.widgets.edit', $widget))
        ->assertOk()
        ->assertSee('Mon widget');
});

it('allows admin to update a widget', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $widget = Widget::create([
        'zone' => 'footer',
        'type' => 'custom_text',
        'title' => 'Original',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->actingAs($admin)
        ->put(route('admin.widgets.update', $widget), [
            'zone' => 'sidebar',
            'type' => 'html',
            'title' => 'Updated',
            'content' => '<p>New</p>',
            'is_active' => '1',
        ])
        ->assertRedirect(route('admin.widgets.index'));

    $this->assertDatabaseHas('widgets', [
        'id' => $widget->id,
        'title' => 'Updated',
        'zone' => 'sidebar',
    ]);
});

it('allows admin to delete a widget', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $widget = Widget::create([
        'zone' => 'sidebar',
        'type' => 'html',
        'title' => 'To Delete',
        'is_active' => true,
        'sort_order' => 0,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.widgets.destroy', $widget))
        ->assertRedirect(route('admin.widgets.index'));

    $this->assertDatabaseMissing('widgets', ['id' => $widget->id]);
});

it('allows admin to reorder widgets', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $w1 = Widget::create(['zone' => 'sidebar', 'type' => 'html', 'title' => 'W1', 'sort_order' => 0]);
    $w2 = Widget::create(['zone' => 'sidebar', 'type' => 'html', 'title' => 'W2', 'sort_order' => 1]);

    $this->actingAs($admin)
        ->postJson(route('admin.widgets.reorder'), [
            'zone' => 'sidebar',
            'order' => [$w2->id, $w1->id],
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($w1->fresh()->sort_order)->toBe(1);
    expect($w2->fresh()->sort_order)->toBe(0);
});

it('validates required fields on store', function () {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');

    $this->actingAs($admin)
        ->post(route('admin.widgets.store'), [])
        ->assertSessionHasErrors(['zone', 'type', 'title']);
});

it('tests widget scopes', function () {
    Widget::create(['zone' => 'sidebar', 'type' => 'html', 'title' => 'A', 'is_active' => true, 'sort_order' => 0]);
    Widget::create(['zone' => 'sidebar', 'type' => 'html', 'title' => 'B', 'is_active' => false, 'sort_order' => 1]);
    Widget::create(['zone' => 'footer', 'type' => 'html', 'title' => 'C', 'is_active' => true, 'sort_order' => 0]);

    expect(Widget::active()->count())->toBe(2);
    expect(Widget::forZone('sidebar')->count())->toBe(2);
    expect(Widget::active()->forZone('sidebar')->count())->toBe(1);
});

it('caches widgets by zone via service', function () {
    Widget::create(['zone' => 'sidebar', 'type' => 'html', 'title' => 'Cached', 'is_active' => true, 'sort_order' => 0]);

    $first = WidgetService::getWidgetsForZone('sidebar');
    expect($first)->toHaveCount(1);

    expect(Cache::has('widgets_sidebar'))->toBeTrue();
});

it('clears cache when widget is saved', function () {
    $widget = Widget::create(['zone' => 'sidebar', 'type' => 'html', 'title' => 'T', 'is_active' => true, 'sort_order' => 0]);

    // Populate cache
    WidgetService::getWidgetsForZone('sidebar');
    expect(Cache::has('widgets_sidebar'))->toBeTrue();

    // Update widget triggers cache clear
    $widget->update(['title' => 'Updated']);
    expect(Cache::has('widgets_sidebar'))->toBeFalse();
});
