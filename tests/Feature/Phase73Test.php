<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Backoffice\Livewire\MetaTagsTable;
use Modules\SEO\Models\MetaTag;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

test('seo index accessible admin', function () {
    $this->get(route('admin.seo.index'))
        ->assertStatus(200)
        ->assertViewIs('backoffice::seo.index');
});

test('seo index non-admin retourne 403', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.seo.index'))
        ->assertForbidden();
});

test('seo index non-authentifié redirige vers login', function () {
    auth()->logout();
    $this->get(route('admin.seo.index'))
        ->assertRedirect(route('login'));
});

test('seo create accessible admin', function () {
    $this->get(route('admin.seo.create'))
        ->assertStatus(200);
});

test('seo store crée un meta tag', function () {
    $data = MetaTag::factory()->make()->toArray();
    foreach (['title', 'description', 'keywords', 'og_title', 'og_description'] as $field) {
        if (isset($data[$field]) && is_array($data[$field])) {
            $data[$field] = $data[$field][app()->getLocale()] ?? reset($data[$field]);
        }
    }
    $this->post(route('admin.seo.store'), $data)
        ->assertRedirect(route('admin.seo.index'));
    $this->assertDatabaseHas('seo_meta_tags', ['url_pattern' => $data['url_pattern']]);
});

test('seo store valide url_pattern requis', function () {
    $data = MetaTag::factory()->make(['url_pattern' => ''])->toArray();
    foreach (['title', 'description', 'keywords', 'og_title', 'og_description'] as $field) {
        if (isset($data[$field]) && is_array($data[$field])) {
            $data[$field] = $data[$field][app()->getLocale()] ?? reset($data[$field]);
        }
    }
    $this->post(route('admin.seo.store'), $data)
        ->assertInvalid(['url_pattern']);
});

test('seo store valide url_pattern unique', function () {
    $existing = MetaTag::factory()->create();
    $data = MetaTag::factory()->make(['url_pattern' => $existing->url_pattern])->toArray();
    foreach (['title', 'description', 'keywords', 'og_title', 'og_description'] as $field) {
        if (isset($data[$field]) && is_array($data[$field])) {
            $data[$field] = $data[$field][app()->getLocale()] ?? reset($data[$field]);
        }
    }
    $this->post(route('admin.seo.store'), $data)
        ->assertInvalid(['url_pattern']);
});

test('seo store valide twitter_card invalide', function () {
    $data = MetaTag::factory()->make(['twitter_card' => 'invalid_value'])->toArray();
    foreach (['title', 'description', 'keywords', 'og_title', 'og_description'] as $field) {
        if (isset($data[$field]) && is_array($data[$field])) {
            $data[$field] = $data[$field][app()->getLocale()] ?? reset($data[$field]);
        }
    }
    $this->post(route('admin.seo.store'), $data)
        ->assertInvalid(['twitter_card']);
});

test('seo edit affiche le formulaire', function () {
    $tag = MetaTag::factory()->create();
    $this->get(route('admin.seo.edit', $tag))
        ->assertStatus(200);
});

test('seo update modifie le meta tag', function () {
    $tag = MetaTag::factory()->create();
    $data = array_merge($tag->toArray(), ['title' => 'Nouveau titre SEO']);
    foreach (['title', 'description', 'keywords', 'og_title', 'og_description'] as $field) {
        if (isset($data[$field]) && is_array($data[$field])) {
            $data[$field] = $data[$field][app()->getLocale()] ?? reset($data[$field]);
        }
    }
    $this->put(route('admin.seo.update', $tag), $data)
        ->assertRedirect(route('admin.seo.index'));
    expect(MetaTag::where('id', $tag->id)->where('title->'.app()->getLocale(), 'Nouveau titre SEO')->exists())->toBeTrue();
});

test('seo update url_pattern unique exclut le tag lui-même', function () {
    $tag = MetaTag::factory()->create(['url_pattern' => '/mon-pattern-unique']);
    $data = array_merge($tag->toArray(), ['title' => 'Titre mis à jour']);
    foreach (['title', 'description', 'keywords', 'og_title', 'og_description'] as $field) {
        if (isset($data[$field]) && is_array($data[$field])) {
            $data[$field] = $data[$field][app()->getLocale()] ?? reset($data[$field]);
        }
    }
    $this->put(route('admin.seo.update', $tag), $data)
        ->assertRedirect(route('admin.seo.index'));
});

test('seo destroy supprime le meta tag', function () {
    $tag = MetaTag::factory()->create();
    $this->delete(route('admin.seo.destroy', $tag))
        ->assertRedirect(route('admin.seo.index'));
    $this->assertDatabaseMissing('seo_meta_tags', ['id' => $tag->id]);
});

test('seo destroy non-admin retourne 403', function () {
    $tag = MetaTag::factory()->create();
    $this->actingAs(User::factory()->create())
        ->delete(route('admin.seo.destroy', $tag))
        ->assertForbidden();
});

test('MetaTagsTable Livewire render affiche les meta tags', function () {
    $tag = MetaTag::factory()->create(['url_pattern' => '/page-visible-test']);
    Livewire::test(MetaTagsTable::class)
        ->assertSee($tag->url_pattern);
});

test('MetaTagsTable search filtre les résultats', function () {
    MetaTag::factory()->create(['url_pattern' => '/search-keyword-match']);
    MetaTag::factory()->create(['url_pattern' => '/autre-url-xyz']);
    Livewire::test(MetaTagsTable::class)
        ->set('search', 'search-keyword')
        ->assertSee('/search-keyword-match')
        ->assertDontSee('/autre-url-xyz');
});

test('MetaTagsTable filterActive filtre par statut actif', function () {
    MetaTag::factory()->create(['url_pattern' => '/active-visible', 'is_active' => true]);
    MetaTag::factory()->inactive()->create(['url_pattern' => '/inactive-hidden']);
    Livewire::test(MetaTagsTable::class)
        ->set('filterActive', '1')
        ->assertSee('/active-visible')
        ->assertDontSee('/inactive-hidden');
});
