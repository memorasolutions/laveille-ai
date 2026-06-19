<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Gate « EN CONSTRUCTION » des pages publiques Academy.
 *
 * Comportement attendu tant que config('academy.under_construction') === true :
 *   - Visiteur (guest) sur /academie  → page « en construction » (503), PAS le catalogue.
 *   - Superadmin sur /academie        → 200 + catalogue des cours visible.
 *   - Lien « Académie » ABSENT du header public.
 *
 * Quand under_construction = false :
 *   - Guest sur /academie → catalogue accessible (gate désactivée).
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Course;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Superadmin : isSuperAdmin() exige email === config('app.superadmin_email') ET rôle super_admin.
    $this->superadmin = User::factory()->create([
        'email' => config('app.superadmin_email'),
    ]);
    $this->superadmin->assignRole('super_admin');

    // Un cours publié et public pour vérifier que le superadmin voit bien le catalogue.
    Course::create([
        'slug'         => 'cours-demo',
        'title'        => 'Cours public de démonstration',
        'status'       => 'published',
        'visibility'   => 'public',
        'access_type'  => 'free',
        'published_at' => now()->subDay(),
    ]);
});

// ── 1. Guest bloqué tant que under_construction = true ────────────────────────

test('guest sur /academie reçoit la page en construction (pas le catalogue)', function (): void {
    config()->set('academy.under_construction', true);

    $response = $this->get(route('academy.index'));

    $response->assertStatus(503);
    $response->assertSee('bientôt disponible', false);
    // NE doit PAS voir le cours du catalogue.
    $response->assertDontSee('Cours public de démonstration', false);
});

// ── 2. Superadmin voit le catalogue ───────────────────────────────────────────

test('superadmin sur /academie voit le catalogue malgré le mode construction', function (): void {
    config()->set('academy.under_construction', true);

    $response = $this->actingAs($this->superadmin)->get(route('academy.index'));

    $response->assertOk();
    $response->assertSee('Cours public de démonstration', false);
    $response->assertDontSee('bientôt disponible', false);
});

// ── 3. Lien « Académie » absent du header tant que under_construction = true ───

test('le lien Académie est absent du header public en mode construction', function (): void {
    config()->set('academy.under_construction', true);

    $response = $this->get(route('home'));

    $response->assertOk();
    // Le lien pointe vers route('academy.index') => /academie ; il ne doit pas figurer dans le menu.
    $response->assertDontSee('>Académie</a>', false);
});

// ── 4. Gate désactivée : guest accède au catalogue ────────────────────────────

test('quand under_construction = false, le guest voit le catalogue', function (): void {
    config()->set('academy.under_construction', false);

    $response = $this->get(route('academy.index'));

    $response->assertOk();
    $response->assertSee('Cours public de démonstration', false);
});
