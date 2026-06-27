<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
});

// ── 1. NobleUI layout markers (shared admin layout always injects these) ──

it('admin dashboard has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin');

    $response->assertOk();
    // The admin layout always links build/nobleui/ assets
    $response->assertSee('nobleui', false);
    // The sidebar always renders data-lucide icons
    $response->assertSee('data-lucide', false);
    // Dashboard uses Bootstrap breadcrumb (not page-breadcrumb)
    $response->assertSee('breadcrumb', false);
});

it('blog articles index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/blog/articles');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

it('blog categories index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/blog/categories');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

it('blog tags index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/blog/tags');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

it('blog comments index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/blog/comments');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

it('pages index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/pages');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

it('faqs index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/faqs');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

it('testimonials index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/testimonials');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
})->skip(fn () => ! \Nwidart\Modules\Facades\Module::find('Testimonials')?->isEnabled(), 'Module Testimonials désactivé dans ce déploiement.');

it('contact messages index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/contact-messages');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

it('users index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/users');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

it('settings index has NobleUI layout markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/settings');

    $response->assertOk();
    $response->assertSee('nobleui', false);
    $response->assertSee('data-lucide', false);
    $response->assertSee('page-breadcrumb', false);
});

// ── 2. No wowdash markers (pages using clean NobleUI-themed views) ──
// Note: blog article/category/comment views still use legacy wowdash markup
// and are tracked separately as a charte migration task.

it('admin dashboard does not contain wowdash markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin');

    $response->assertOk();
    $response->assertDontSee('iconify-icon', false);
    $response->assertDontSee('radius-12', false);
    $response->assertDontSee('bg-base', false);
});

it('faqs index does not contain wowdash markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/faqs');

    $response->assertOk();
    $response->assertDontSee('iconify-icon', false);
    $response->assertDontSee('radius-12', false);
    $response->assertDontSee('bg-base', false);
});

it('testimonials index does not contain wowdash markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/testimonials');

    $response->assertOk();
    $response->assertDontSee('iconify-icon', false);
    $response->assertDontSee('radius-12', false);
    $response->assertDontSee('bg-base', false);
})->skip(fn () => ! \Nwidart\Modules\Facades\Module::find('Testimonials')?->isEnabled(), 'Module Testimonials désactivé dans ce déploiement.');

it('contact messages index does not contain wowdash markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/contact-messages');

    $response->assertOk();
    $response->assertDontSee('iconify-icon', false);
    $response->assertDontSee('radius-12', false);
    $response->assertDontSee('bg-base', false);
});

it('users index does not contain wowdash markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/users');

    $response->assertOk();
    $response->assertDontSee('iconify-icon', false);
    $response->assertDontSee('radius-12', false);
    $response->assertDontSee('bg-base', false);
});

it('settings index does not contain wowdash markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/settings');

    $response->assertOk();
    $response->assertDontSee('iconify-icon', false);
    $response->assertDontSee('radius-12', false);
    $response->assertDontSee('bg-base', false);
});

it('pages index does not contain wowdash markers', function () {
    $response = $this->actingAs($this->admin)->get('/admin/pages');

    $response->assertOk();
    $response->assertDontSee('iconify-icon', false);
    $response->assertDontSee('radius-12', false);
    $response->assertDontSee('bg-base', false);
});

// ── 3. Bootstrap card structure ──

it('admin dashboard has Bootstrap card structure', function () {
    $response = $this->actingAs($this->admin)->get('/admin');

    $response->assertOk();
    $response->assertSee('card-body', false);
});

it('users index has Bootstrap card structure', function () {
    $response = $this->actingAs($this->admin)->get('/admin/users');

    $response->assertOk();
    $response->assertSee('card-body', false);
});

it('settings index has Bootstrap card structure', function () {
    $response = $this->actingAs($this->admin)->get('/admin/settings');

    $response->assertOk();
    $response->assertSee('card-body', false);
});

it('faqs index has Bootstrap card structure', function () {
    $response = $this->actingAs($this->admin)->get('/admin/faqs');

    $response->assertOk();
    $response->assertSee('card-body', false);
});

it('testimonials index has Bootstrap card structure', function () {
    $response = $this->actingAs($this->admin)->get('/admin/testimonials');

    $response->assertOk();
    $response->assertSee('card-body', false);
})->skip(fn () => ! \Nwidart\Modules\Facades\Module::find('Testimonials')?->isEnabled(), 'Module Testimonials désactivé dans ce déploiement.');

// ── 4. Mode clair forcé dans l'admin (dark mode retiré 2026-06-02) ──
// Le layout admin force data-bs-theme="light" et nettoie localStorage ; color-modes.js
// n'est plus injecté. Les 4 tests ci-dessous valident le marqueur réel.

it('admin layout enforces light mode', function () {
    $response = $this->actingAs($this->admin)->get('/admin');

    $response->assertOk();
    $response->assertSee('data-bs-theme="light"', false);
});

it('blog articles page enforces light mode', function () {
    $response = $this->actingAs($this->admin)->get('/admin/blog/articles');

    $response->assertOk();
    $response->assertSee('data-bs-theme="light"', false);
});

it('users page enforces light mode', function () {
    $response = $this->actingAs($this->admin)->get('/admin/users');

    $response->assertOk();
    $response->assertSee('data-bs-theme="light"', false);
});

it('settings page enforces light mode', function () {
    $response = $this->actingAs($this->admin)->get('/admin/settings');

    $response->assertOk();
    $response->assertSee('data-bs-theme="light"', false);
});
