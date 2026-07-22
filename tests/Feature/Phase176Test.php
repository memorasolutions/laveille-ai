<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Blog\Models\Article;
use Modules\Pages\Models\StaticPage;
use Modules\SaaS\Models\Plan;
use Modules\Settings\Models\Setting;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->admin = User::factory()->create(['name' => 'Admin Test']);
    $this->admin->assignRole('super_admin');
});

test('search page accessible by admin', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.search'))
        ->assertOk();
});

test('search page requires authentication', function () {
    $this->get(route('admin.search'))
        ->assertRedirect();
});

test('search returns users results', function () {
    if (! \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
        $this->markTestSkipped('Module SaaS désactivé dans ce déploiement (dépend indirectement de Modules\\SaaS\\Models\\Plan).');
    }

    User::factory()->create(['name' => 'UniqueSearchName']);

    $this->actingAs($this->admin)
        ->get(route('admin.search', ['q' => 'UniqueSearch']))
        ->assertOk()
        ->assertSee('UniqueSearchName');
});

test('search returns articles results', function () {
    Article::factory()->create(['title' => ['fr' => 'Article Unique Test'], 'status' => 'published']);

    $this->actingAs($this->admin)
        ->get(route('admin.search', ['q' => 'Unique Test']))
        ->assertOk()
        ->assertSee('Article Unique Test');
});

test('search returns settings results', function () {
    if (! \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
        $this->markTestSkipped('Module SaaS désactivé dans ce déploiement (dépend indirectement de Modules\\SaaS\\Models\\Plan).');
    }

    Setting::create(['key' => 'unique_search_key', 'value' => 'test_value']);

    $this->actingAs($this->admin)
        ->get(route('admin.search', ['q' => 'unique_search']))
        ->assertOk()
        ->assertSee('unique_search_key');
});

test('search returns plans results', function () {
    if (! \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
        $this->markTestSkipped('Module SaaS désactivé dans ce déploiement (dépend indirectement de Modules\\SaaS\\Models\\Plan).');
    }

    Plan::factory()->create(['name' => 'UniqueSearchPlan']);

    $this->actingAs($this->admin)
        ->get(route('admin.search', ['q' => 'UniqueSearchPlan']))
        ->assertOk()
        ->assertSee('UniqueSearchPlan');
});

test('search filters by type users only', function () {
    if (! \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
        $this->markTestSkipped('Module SaaS désactivé dans ce déploiement (dépend indirectement de Modules\\SaaS\\Models\\Plan).');
    }

    User::factory()->create(['name' => 'FilterTestUser']);
    Plan::factory()->create(['name' => 'FilterTestPlan']);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.search', ['q' => 'FilterTest', 'type' => 'users']));

    $response->assertOk()
        ->assertSee('FilterTestUser')
        ->assertDontSee('FilterTestPlan');
});

test('search shows total count badge', function () {
    if (! \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
        $this->markTestSkipped('Module SaaS désactivé dans ce déploiement (dépend indirectement de Modules\\SaaS\\Models\\Plan).');
    }

    $this->actingAs($this->admin)
        ->get(route('admin.search', ['q' => 'admin']))
        ->assertOk()
        ->assertSee('résultats', false);
});

test('search page uses WowDash pattern', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.search'))
        ->assertOk()
        ->assertSee('card', false)
        ->assertSee('form-control', false);
});

test('search shows type filter dropdown', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.search'))
        ->assertOk()
        ->assertSee('name="type"', false);
});

test('empty search shows search form only', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.search'))
        ->assertOk()
        ->assertDontSee('Résultats pour', false);
});

test('search returns pages results', function () {
    if (! \Nwidart\Modules\Facades\Module::find('SaaS')?->isEnabled()) {
        $this->markTestSkipped('Module SaaS désactivé dans ce déploiement (dépend indirectement de Modules\\SaaS\\Models\\Plan).');
    }

    StaticPage::factory()->create(['title' => ['fr' => 'Page Unique Recherche']]);

    $this->actingAs($this->admin)
        ->get(route('admin.search', ['q' => 'Page Unique']))
        ->assertOk()
        ->assertSee('Page Unique Recherche');
});
