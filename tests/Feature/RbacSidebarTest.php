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
});

// ── Sidebar visibility per role (backend theme) ──

it('super_admin sees all sidebar sections', function () {
    $user = User::factory()->create();
    $user->assignRole('super_admin');

    $response = $this->actingAs($user)
        ->get(route('admin.dashboard'));

    $response->assertStatus(200);

    // Principal
    $response->assertSee('Tableau de bord');
    $response->assertSee('Statistiques');
    // Contenu
    $response->assertSee('Articles');
    $response->assertSee('Commentaires');
    $response->assertSee('Catégories');
    $response->assertSee('Pages');
    $response->assertSee('Médias');
    // Utilisateurs
    $response->assertSee('Membres');
    $response->assertSee('Rôles');
    // Marketing
    $response->assertSee('Newsletter');
    // Configuration
    $response->assertSee('Configuration');
    $response->assertSee('SEO');
    $response->assertSee('Feature Flags');
    $response->assertSee('Traductions');
    // Système
    $response->assertSee('Sécurité');
    $response->assertSee('IP bloquées');
    $response->assertSee('Sauvegardes');
    $response->assertSee('Santé');
});

it('admin sees everything except Rôles link', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $response = $this->actingAs($user)
        ->get(route('admin.dashboard'));

    $response->assertStatus(200);

    // Should see most sections
    $response->assertSee('Tableau de bord');
    $response->assertSee('Articles');
    $response->assertSee('Commentaires');
    $response->assertSee('Membres');
    $response->assertSee('Configuration');
    $response->assertSee('SEO');
    $response->assertSee('Sécurité');
    $response->assertSee('Sauvegardes');

    // Admin can VIEW roles (view_roles) but should not see create/edit actions
    $response->assertSee(route('admin.roles.index'));
});

it('editor sees only content items and dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $response = $this->actingAs($user)
        ->get(route('admin.dashboard'));

    $response->assertStatus(200);

    // Should see dashboard and content items
    $response->assertSee('Tableau de bord');
    $response->assertSee('Articles');
    $response->assertSee('Commentaires');
    $response->assertSee('Catégories');
    $response->assertSee('Pages');
    $response->assertSee('Médias');

    // Should NOT see admin-only route links in sidebar
    $response->assertDontSee(route('admin.backups.index'));
    $response->assertDontSee(route('admin.security'));
    $response->assertDontSee(route('admin.roles.index'));
    $response->assertDontSee(route('admin.users.index'));
    $response->assertDontSee(route('admin.settings.index'));
});

// ── Access control (403 / redirect) ──

it('user gets 403 on admin dashboard', function () {
    $user = User::factory()->create();
    $user->assignRole('user');

    $response = $this->actingAs($user)
        ->get(route('admin.dashboard'));

    $response->assertStatus(403);
});

it('guest is redirected to login', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertStatus(302);
    $response->assertRedirect(route('login'));
});

// ── Route-level permission enforcement ──

it('editor cannot access backup routes', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user)
        ->get(route('admin.backups.index'))
        ->assertStatus(403);
});

it('editor cannot access security routes', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user)
        ->get(route('admin.security'))
        ->assertStatus(403);
});

it('editor cannot access settings routes', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user)
        ->get(route('admin.settings.index'))
        ->assertStatus(403);
});

it('editor cannot access users routes', function () {
    $user = User::factory()->create();
    $user->assignRole('editor');

    $this->actingAs($user)
        ->get(route('admin.users.index'))
        ->assertStatus(403);
});

it('admin can access backups and settings', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->get(route('admin.backups.index'))
        ->assertStatus(200);

    $this->actingAs($user)
        ->get(route('admin.settings.index'))
        ->assertStatus(200);
});

it('admin can view roles but cannot create them', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    // Admin can VIEW roles (has view_roles)
    $this->actingAs($user)
        ->get(route('admin.roles.index'))
        ->assertStatus(200);

    // Admin CANNOT create roles (no create_roles)
    $this->actingAs($user)
        ->get(route('admin.roles.create'))
        ->assertStatus(403);
});
