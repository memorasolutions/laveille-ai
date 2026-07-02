<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Admin Academy (post D03, 2026-07-02)
 * Couvre : redirections douces des anciennes URLs de gestion de cours vers la
 * surface unique front-end (CourseEditor/Dashboard/CourseCreate), gating 403
 * pour un non-admin, et non-régression de la route publique.
 *
 * L'ancien CRUD admin (cours/structure/instructeurs, AdminCourseController /
 * AdminStructureController / AdminInstructorController) a été retiré : il
 * était 100 % dupliqué par le front-end (admin=academy.manage a déjà accès
 * total via CoursePolicy). Voir routes/admin.php pour le détail.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Course;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    // Sauter si le module n'est pas activé (ex. CI sans Academy)
    if (! \Nwidart\Modules\Facades\Module::find('Academy')?->isEnabled()) {
        test()->markTestSkipped('Module Academy désactivé — tests skipped.');
    }

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

    // Admin avec permission academy.manage
    $this->admin = User::factory()->create();
    $this->admin->assignRole('super_admin');
    // super_admin reçoit academy.manage via AcademyPermissionsSeeder (run dans RefreshDatabase + seeder)
    // Si seeder non lancé automatiquement, donner la permission manuellement :
    if (! $this->admin->can('academy.manage')) {
        $this->admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    // Utilisateur sans permission
    $this->user = User::factory()->create();
});

// ── 1. Ancienne URL de liste → redirige vers le tableau de bord front-end ────

test('admin.academy.courses.index redirige un admin vers le tableau de bord front-end', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.academy.courses.index'))
        ->assertRedirect(route('academy.dashboard'));
});

// ── 2. Ancienne URL de création → redirige vers la création front-end ───────

test('admin.academy.courses.create redirige un admin vers la création front-end', function (): void {
    $this->actingAs($this->admin)
        ->get(route('admin.academy.courses.create'))
        ->assertRedirect(route('academy.courses.create'));
});

// ── 3. Ancienne URL d'édition → redirige vers l'éditeur front-end (slug) ────

test('admin.academy.courses.edit redirige un admin vers l\'éditeur front-end du cours', function (): void {
    $course = Course::create([
        'slug'        => 'cours-redirection',
        'title'       => 'Cours Redirection',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.academy.courses.edit', $course))
        ->assertRedirect(route('academy.courses.manage', $course->slug));
});

// ── 4. Gating 403 pour non-admin (inchangé, toujours derrière can:academy.manage) ─

test('utilisateur sans permission reçoit 403 sur les anciennes URLs admin', function (): void {
    $this->actingAs($this->user)
        ->get(route('admin.academy.courses.index'))
        ->assertForbidden();

    $this->actingAs($this->user)
        ->get(route('admin.academy.courses.create'))
        ->assertForbidden();
});

// ── 5. Non-régression : routes publiques Academy non cassées ─────────────────

test('la route publique academy.index répond sans erreur 500', function (): void {
    // Mode « en construction » par défaut → un guest reçoit la page 503 (gate volontaire),
    // jamais une 500 (erreur applicative). On vérifie l'absence d'erreur serveur réelle.
    $response = $this->get(route('academy.index'));
    expect($response->status())->not->toBe(500);
    expect(in_array($response->status(), [200, 503], true))->toBeTrue();
});
