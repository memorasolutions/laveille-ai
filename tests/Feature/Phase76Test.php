<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pages\Models\StaticPage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

// 1. admin pages index returns 200
test('admin pages index returns 200', function () {
    $this->get(route('admin.pages.index'))->assertStatus(200);
});

// 2. can create static page
test('can create static page', function () {
    $this->post(route('admin.pages.store'), [
        'title' => 'Ma Page Test',
        'content' => '<p>Contenu de test</p>',
        'status' => 'draft',
    ])
        ->assertRedirect(route('admin.pages.index'));

    expect(StaticPage::where('title->'.app()->getLocale(), 'Ma Page Test')->where('status', 'draft')->exists())->toBeTrue();
});

// 3. can update static page
test('can update static page', function () {
    $page = StaticPage::factory()->create(['title' => 'Ancien Titre']);
    $this->put(route('admin.pages.update', $page->slug), [
        'title' => 'Nouveau Titre',
        'status' => 'published',
    ])
        ->assertRedirect(route('admin.pages.index'));

    expect(StaticPage::where('id', $page->id)->where('title->'.app()->getLocale(), 'Nouveau Titre')->where('status', 'published')->exists())->toBeTrue();
});

// 4. can delete static page (soft delete)
test('can delete static page', function () {
    $page = StaticPage::factory()->create();
    $this->delete(route('admin.pages.destroy', $page->slug))
        ->assertRedirect(route('admin.pages.index'));

    $this->assertSoftDeleted('static_pages', ['id' => $page->id]);
});

// 5. published page visible public
test('published page visible on public route', function () {
    $page = StaticPage::factory()->published()->create(['title' => 'Page Publique']);
    $this->get('/pages/'.$page->slug)
        ->assertStatus(200)
        ->assertSee('Page Publique');
});

// 6. draft page not accessible public
test('draft page returns 404 on public route', function () {
    $page = StaticPage::factory()->draft()->create();
    $this->get('/pages/'.$page->slug)->assertStatus(404);
});

// 7. non-admin cannot access admin pages
test('non-admin gets 403 on admin pages index', function () {
    $regular = User::factory()->create();
    $this->actingAs($regular);
    $this->get(route('admin.pages.index'))->assertStatus(403);
});
