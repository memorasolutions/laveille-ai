<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\CampaignsTable;
use Modules\Backoffice\Livewire\CategoriesTable;
use Modules\Backoffice\Livewire\PlansTable;
use Modules\Backoffice\Livewire\ShortcodesTable;
use Modules\Backoffice\Livewire\SubscribersTable;
use Modules\Blog\Models\Category;
use Modules\Editor\Models\Shortcode;
use Modules\Newsletter\Models\Campaign;
use Modules\Newsletter\Models\Subscriber;
use Modules\SaaS\Models\Plan;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    $this->actingAs($this->admin);
});

test('categories bulk activate works', function () {
    $categories = Category::factory()->count(3)->create(['is_active' => false]);

    Livewire::test(CategoriesTable::class)
        ->set('selected', $categories->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'activate')
        ->call('executeBulkAction');

    expect(Category::where('is_active', true)->count())->toBe(3);
});

test('categories bulk deactivate works', function () {
    $categories = Category::factory()->count(3)->create(['is_active' => true]);

    Livewire::test(CategoriesTable::class)
        ->set('selected', $categories->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'deactivate')
        ->call('executeBulkAction');

    expect(Category::where('is_active', false)->count())->toBe(3);
});

test('categories bulk delete works', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(CategoriesTable::class)
        ->set('selected', $categories->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    expect(Category::count())->toBe(0);
});

test('plans bulk activate works', function () {
    $plans = Plan::factory()->count(3)->create(['is_active' => false]);

    Livewire::test(PlansTable::class)
        ->set('selected', $plans->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'activate')
        ->call('executeBulkAction');

    expect(Plan::where('is_active', true)->count())->toBe(3);
});

test('plans bulk deactivate works', function () {
    $plans = Plan::factory()->count(3)->create(['is_active' => true]);

    Livewire::test(PlansTable::class)
        ->set('selected', $plans->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'deactivate')
        ->call('executeBulkAction');

    expect(Plan::where('is_active', false)->count())->toBe(3);
});

test('plans bulk delete works', function () {
    $plans = Plan::factory()->count(3)->create();

    Livewire::test(PlansTable::class)
        ->set('selected', $plans->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    expect(Plan::count())->toBe(0);
});

test('subscribers bulk delete works', function () {
    $subscribers = Subscriber::factory()->count(3)->create();

    Livewire::test(SubscribersTable::class)
        ->set('selected', $subscribers->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    expect(Subscriber::count())->toBe(0);
});

test('shortcodes bulk delete works', function () {
    $shortcodes = Shortcode::factory()->count(3)->create();

    Livewire::test(ShortcodesTable::class)
        ->set('selected', $shortcodes->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    expect(Shortcode::count())->toBe(0);
});

test('campaigns bulk delete works', function () {
    $campaigns = Campaign::factory()->count(3)->create();

    Livewire::test(CampaignsTable::class)
        ->set('selected', $campaigns->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    expect(Campaign::count())->toBe(0);
});

test('bulk action without selection does not execute', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(CategoriesTable::class)
        ->set('selected', [])
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction');

    expect(Category::count())->toBe(3);
});

test('categories selectAll populates selected', function () {
    Category::factory()->count(3)->create();

    $component = Livewire::test(CategoriesTable::class)
        ->set('selectAll', true);

    expect($component->get('selected'))->toHaveCount(3);
});

test('plans selectAll populates selected', function () {
    Plan::factory()->count(3)->create();

    $component = Livewire::test(PlansTable::class)
        ->set('selectAll', true);

    expect($component->get('selected'))->toHaveCount(3);
});

test('subscribers selectAll populates selected', function () {
    Subscriber::factory()->count(3)->create();

    $component = Livewire::test(SubscribersTable::class)
        ->set('selectAll', true);

    expect($component->get('selected'))->toHaveCount(3);
});

test('bulk action resets after execution', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(CategoriesTable::class)
        ->set('selected', $categories->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', 'delete')
        ->call('executeBulkAction')
        ->assertSet('selected', [])
        ->assertSet('selectAll', false)
        ->assertSet('bulkAction', '');
});

test('empty bulk action does not execute', function () {
    $categories = Category::factory()->count(3)->create();

    Livewire::test(CategoriesTable::class)
        ->set('selected', $categories->pluck('id')->map(fn ($id) => (int) $id)->toArray())
        ->set('bulkAction', '')
        ->call('executeBulkAction');

    expect(Category::count())->toBe(3);
});
