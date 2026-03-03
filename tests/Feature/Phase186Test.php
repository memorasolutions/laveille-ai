<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Search\Services\SearchService;
use Modules\Settings\Models\Setting;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

// ============================================================
// CONFIGURATION
// ============================================================

test('scout driver est configuré en database', function () {
    expect(config('scout.driver'))->toBe('database');
});

test('config search contient les 6 modèles requis', function () {
    $models = config('search.models');

    expect($models)->toContain(\App\Models\User::class)
        ->toContain(\Modules\Blog\Models\Article::class)
        ->toContain(\Modules\SaaS\Models\Plan::class)
        ->toContain(\Modules\Blog\Models\Category::class)
        ->toContain(\Modules\Pages\Models\StaticPage::class)
        ->toContain(\Modules\Settings\Models\Setting::class);
});

test('config search contient les types avec labels et icônes', function () {
    $types = config('search.types');

    expect($types)->toBeArray()
        ->toHaveKeys(['users', 'articles', 'plans', 'categories', 'pages', 'settings']);
});

// ============================================================
// MODÈLE SETTING SEARCHABLE
// ============================================================

test('Setting a le trait Searchable', function () {
    expect(method_exists(Setting::class, 'search'))->toBeTrue();
    expect(method_exists(Setting::class, 'toSearchableArray'))->toBeTrue();
});

test('Setting toSearchableArray retourne les bonnes clés', function () {
    $setting = Setting::factory()->create([
        'key' => 'site_name',
        'value' => 'MonSite',
        'group' => 'general',
    ]);
    $array = $setting->toSearchableArray();

    expect($array)->toHaveKeys(['key', 'value', 'description', 'group'])
        ->and($array['key'])->toBe('site_name')
        ->and($array['value'])->toBe('MonSite');
});

test('Setting shouldBeSearchable exclut le groupe security', function () {
    $secure = Setting::factory()->make(['group' => 'security']);
    $general = Setting::factory()->make(['group' => 'general']);

    expect($secure->shouldBeSearchable())->toBeFalse()
        ->and($general->shouldBeSearchable())->toBeTrue();
});

// ============================================================
// SEARCH SERVICE
// ============================================================

test('SearchService est enregistré en singleton', function () {
    $s1 = app(SearchService::class);
    $s2 = app(SearchService::class);

    expect($s1)->toBe($s2);
});

test('SearchService searchAdmin retourne tous les groupes quand type=all', function () {
    $service = app(SearchService::class);
    $results = $service->searchAdmin('test', 'all');

    expect($results)->toBeArray()
        ->toHaveKeys(['users', 'articles', 'pages', 'plans', 'categories', 'settings']);
});

test('SearchService searchNavbar retourne users articles settings', function () {
    $service = app(SearchService::class);
    $results = $service->searchNavbar('test');

    expect($results)->toBeArray()
        ->toHaveKeys(['users', 'articles', 'settings']);
});

test('SearchService searchFront retourne articles pages total', function () {
    $service = app(SearchService::class);
    $results = $service->searchFront('test');

    expect($results)->toBeArray()
        ->toHaveKeys(['articles', 'pages', 'total'])
        ->and($results['total'])->toBeInt();
});

test('SearchService getSearchableModels lit la config', function () {
    $service = app(SearchService::class);
    $models = $service->getSearchableModels();

    expect($models)->toBeArray()
        ->not->toBeEmpty()
        ->toContain(\App\Models\User::class);
});

// ============================================================
// API /api/v1/search
// ============================================================

test('API search requiert authentification', function () {
    $this->getJson('/api/v1/search?q=test')->assertStatus(401);
});

test('API search nécessite le paramètre q', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/search')->assertStatus(422);
});

test('API search rejette q < 2 caractères', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum')->getJson('/api/v1/search?q=a')->assertStatus(422);
});

test('API search retourne des résultats', function () {
    $user = User::factory()->create(['name' => 'Charlie Bravo']);

    $this->actingAs($user, 'sanctum')->getJson('/api/v1/search?q=Charlie')
        ->assertOk()
        ->assertJsonStructure(['success', 'data']);
});

// ============================================================
// ADMIN SEARCH
// ============================================================

test('admin search requiert authentification', function () {
    $this->get(route('admin.search'))->assertRedirect(route('login'));
});

test('admin search accessible aux admins', function () {
    $admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.search'))
        ->assertOk();
});

test('admin search affiche les résultats pour une requête', function () {
    $admin = User::factory()->create(['name' => 'Admin User']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.search', ['q' => 'Admin']))
        ->assertOk();
});

test('admin search filtre par type users', function () {
    $admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.search', ['q' => 'test', 'type' => 'users']))
        ->assertOk();
});

// ============================================================
// FRONT /search
// ============================================================

test('page front /search est accessible publiquement', function () {
    $this->get(route('search.front'))->assertOk();
});

test('page front /search affiche le formulaire', function () {
    $this->get(route('search.front'))
        ->assertOk()
        ->assertSee('name="q"', false);
});

test('page front /search avec paramètre q fonctionne', function () {
    $this->get(route('search.front', ['q' => 'test']))
        ->assertOk();
});

// ============================================================
// LIVEWIRE GLOBAL SEARCH
// ============================================================

test('composant Livewire GlobalSearch existe', function () {
    expect(class_exists(\Modules\Backoffice\Livewire\GlobalSearch::class))->toBeTrue();
});
