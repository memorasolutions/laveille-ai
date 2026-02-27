<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Pages\Models\StaticPage;

uses(RefreshDatabase::class);

function createPublishedPage(string $template = 'default', string $slug = 'test-page'): StaticPage
{
    return StaticPage::create([
        'title' => 'Page de test',
        'slug' => $slug,
        'content' => '<p>Contenu de la page.</p>',
        'status' => 'published',
        'template' => $template,
    ]);
}

function adminUser(): User
{
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    return $user;
}

it('affiche le template default', function () {
    createPublishedPage('default');

    $this->get('/pages/test-page')
        ->assertOk()
        ->assertViewIs('pages::public.templates.default');
});

it('affiche le template full-width', function () {
    createPublishedPage('full-width', 'fw-page');

    $this->get('/pages/fw-page')
        ->assertOk()
        ->assertViewIs('pages::public.templates.full-width');
});

it('affiche le template sidebar', function () {
    createPublishedPage('sidebar', 'sb-page');

    $this->get('/pages/sb-page')
        ->assertOk()
        ->assertViewIs('pages::public.templates.sidebar');
});

it('affiche le template landing', function () {
    createPublishedPage('landing', 'lp-page');

    $this->get('/pages/lp-page')
        ->assertOk()
        ->assertViewIs('pages::public.templates.landing');
});

it('fallback vers default si template invalide', function () {
    $page = createPublishedPage('default', 'fallback-page');
    $page->update(['template' => 'nonexistent']);

    $this->get('/pages/fallback-page')
        ->assertOk()
        ->assertViewIs('pages::public.templates.default');
});

it('admin peut créer une page avec template', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.pages.store'), [
            'title' => 'Nouvelle page',
            'content' => '<p>Contenu.</p>',
            'status' => 'published',
            'template' => 'sidebar',
        ])
        ->assertRedirect(route('admin.pages.index'));

    expect(StaticPage::first()->template)->toBe('sidebar');
});

it('admin peut modifier le template d\'une page', function () {
    $page = createPublishedPage('default', 'edit-page');

    $this->actingAs(adminUser())
        ->put(route('admin.pages.update', $page), [
            'title' => $page->title,
            'content' => $page->content,
            'status' => 'published',
            'template' => 'landing',
        ])
        ->assertRedirect(route('admin.pages.index'));

    expect($page->fresh()->template)->toBe('landing');
});

it('la validation rejette un template invalide', function () {
    $this->actingAs(adminUser())
        ->post(route('admin.pages.store'), [
            'title' => 'Test page',
            'content' => '<p>Contenu.</p>',
            'status' => 'published',
            'template' => 'invalid-template',
        ])
        ->assertSessionHasErrors('template');
});

it('la constante TEMPLATES contient 4 entrées', function () {
    expect(StaticPage::TEMPLATES)->toHaveCount(4)
        ->toHaveKeys(['default', 'full-width', 'sidebar', 'landing']);
});

it('le template par défaut est "default" en base', function () {
    StaticPage::create([
        'title' => 'Sans template',
        'slug' => 'sans-template',
        'content' => '<p>Test.</p>',
        'status' => 'published',
    ]);

    expect(StaticPage::first()->template)->toBe('default');
});
