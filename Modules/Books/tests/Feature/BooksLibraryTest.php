<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Gate « EN CONSTRUCTION » et rendu public du module Books (/livres).
 *
 * Comportement attendu tant que config('books.under_construction') === true :
 *   - Visiteur (guest) sur /livres et /livres/{slug} → page « en construction » (503).
 *   - Superadmin sur /livres → 200 + catalogue des 5 livres (2 essais + trilogie Nexus Neural).
 *   - Superadmin sur /livres/{slug} → 200 + JSON-LD Schema.org (@type Book).
 *   - Fiche inexistante → 404 (pas 503, pas 500), même pour le superadmin.
 *
 * Pattern calqué sur Modules\Academy\Tests\Feature\AcademyUnderConstructionTest.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Superadmin : isSuperAdmin() exige email === config('app.superadmin_email') ET rôle super_admin.
    $this->superadmin = User::factory()->create([
        'email' => config('app.superadmin_email'),
    ]);
    $this->superadmin->assignRole('super_admin');

    config()->set('books.under_construction', true);
});

// ── 1. Guest bloqué sur l'index ────────────────────────────────────────────

test('guest sur /livres reçoit la page en construction (503)', function (): void {
    $response = $this->get('/livres');

    $response->assertStatus(503);
    $response->assertSee('Bibliothèque en construction', false);
});

// ── 2. Guest bloqué sur une fiche livre ────────────────────────────────────

test('guest sur /livres/ia-sans-se-faire-poursuivre reçoit aussi la page en construction (503)', function (): void {
    $response = $this->get('/livres/ia-sans-se-faire-poursuivre');

    $response->assertStatus(503);
    $response->assertSee('Bibliothèque en construction', false);
});

// ── 3. Superadmin voit le catalogue des 5 livres ───────────────────────────

test('superadmin sur /livres voit le catalogue des 5 livres', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres');

    $response->assertOk();
    // Apostrophe échappée en HTML entity (L&#039;IA...) par Blade : on cherche le fragment sans apostrophe.
    $response->assertSee('IA sans se faire poursuivre', false);
    $response->assertSee('IA pour les parents', false);
    $response->assertSee('Nexus Neural : Tome 1', false);
    $response->assertSee('Nexus Neural : Tome 2', false);
    $response->assertSee('Nexus Neural : Tome 3', false);
});

// ── 4. Le catalogue regroupe visuellement les 3 tomes Nexus Neural ────────

test('le catalogue regroupe les 3 tomes sous la bannière Trilogie Nexus Neural', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres');

    $response->assertOk();
    $response->assertSee('Trilogie Nexus Neural', false);
});

// ── 5. Fiche livre : 200 + JSON-LD Book ─────────────────────────────────────

test('superadmin sur une fiche livre reçoit un 200 avec le JSON-LD Book', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres/ia-sans-se-faire-poursuivre');

    $response->assertOk();
    $response->assertSee('application/ld+json', false);
    $response->assertSee('"@type": "Book"', false);
});

// ── 6. Fiche inexistante → 404 (pas 503, pas 500) ──────────────────────────

test('une fiche livre inexistante retourne 404 pour un superadmin', function (): void {
    $response = $this->actingAs($this->superadmin)->get('/livres/slug-inexistant-xyz');

    $response->assertStatus(404);
});
