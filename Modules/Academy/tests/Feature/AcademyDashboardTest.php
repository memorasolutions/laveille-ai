<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Espace personnel front-end role-aware (/academie/espace, PHASE 2).
 *
 * Prouve que la sécurité est SERVEUR :
 *  - invité (non connecté)      → refusé/redirigé (middleware auth) ;
 *  - étudiant inscrit           → voit SA formation, AUCUNE section « Mes cours » ;
 *  - formateur owner du cours A → voit A, NE voit PAS le cours B d'un autre (anti-fuite) ;
 *  - admin                      → voit TOUS les cours.
 *
 * Le mode « en construction » est désactivé dans ces tests (config), pour
 * exercer la logique de RÔLE sans être bloqué par la gate superadmin-only.
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    // Désactive la gate « en construction » pour tester la logique de rôle.
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->courseA = makeDashCourse('cours-a', 'Cours A');
    $this->courseB = makeDashCourse('cours-b', 'Cours B');
});

/** Helper : crée un cours gratuit publié minimal. */
function makeDashCourse(string $slug, string $title): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Helper : ajoute un chapitre + une leçon à un cours (pour le lien « Continuer »). */
function addDashLesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre 1',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon 1',
        'slug'       => 'lecon-1-' . $course->id,
        'position'   => 1,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. INVITÉ - refusé (middleware auth)
// ─────────────────────────────────────────────────────────────────────────────

test('invité non connecté est redirigé hors de /academie/espace', function (): void {
    $response = $this->get(route('academy.dashboard'));

    $response->assertRedirect(route('login'));
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. ÉTUDIANT - voit SA formation, PAS de section « Mes cours »
// ─────────────────────────────────────────────────────────────────────────────

test('étudiant inscrit voit sa formation et aucune section « Mes cours »', function (): void {
    $etudiant = User::factory()->create();
    $etudiant->assignRole('student');

    Enrollment::create([
        'user_id'   => $etudiant->id,
        'course_id' => $this->courseA->id,
        'status'    => 'active',
        'source'    => 'free',
    ]);

    $response = $this->actingAs($etudiant)->get(route('academy.dashboard'));

    $response->assertOk();
    $response->assertSee('Mes formations', false);
    $response->assertSee('Cours A', false);
    // Un étudiant n'a pas can('viewAny', Course) → pas de bloc de gestion.
    $response->assertDontSee('id="academy-mes-cours"', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. FORMATEUR - voit SON cours A, JAMAIS le cours B d'un autre (TEST ANTI-FUITE)
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-FUITE : formateur owner de A voit A mais PAS le cours B d\'un autre', function (): void {
    $formateurA = User::factory()->create();
    $formateurA->assignRole('instructor');
    CourseRole::create([
        'course_id' => $this->courseA->id,
        'user_id'   => $formateurA->id,
        'role'      => 'owner',
    ]);

    // Un AUTRE formateur possède le cours B.
    $formateurB = User::factory()->create();
    $formateurB->assignRole('instructor');
    CourseRole::create([
        'course_id' => $this->courseB->id,
        'user_id'   => $formateurB->id,
        'role'      => 'owner',
    ]);

    $response = $this->actingAs($formateurA)->get(route('academy.dashboard'));

    $response->assertOk();
    $response->assertSee('Mes cours', false);
    $response->assertSee('Cours A', false);
    // Cœur du test : le cours B (d'un autre formateur) ne doit JAMAIS apparaître.
    $response->assertDontSee('Cours B', false);
});

test('formateur sans aucun course_role ne voit aucun cours géré', function (): void {
    $formateur = User::factory()->create();
    $formateur->assignRole('instructor');

    $response = $this->actingAs($formateur)->get(route('academy.dashboard'));

    $response->assertOk();
    $response->assertSee('Mes cours', false);
    $response->assertDontSee('Cours A', false);
    $response->assertDontSee('Cours B', false);
    $response->assertSee('Vous ne gérez aucun cours', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ADMIN - voit TOUS les cours
// ─────────────────────────────────────────────────────────────────────────────

test('admin voit tous les cours dans « Mes cours »', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    $response = $this->actingAs($admin)->get(route('academy.dashboard'));

    $response->assertOk();
    $response->assertSee('Mes cours', false);
    $response->assertSee('Cours A', false);
    $response->assertSee('Cours B', false);
    // L'admin dispose en plus du bloc d'administration.
    $response->assertSee('id="academy-vue-admin"', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Lien « Continuer » vers la 1re leçon quand l'étudiant a une leçon
// ─────────────────────────────────────────────────────────────────────────────

test('étudiant inscrit avec une leçon voit un lien Continuer vers la 1re leçon', function (): void {
    $lesson = addDashLesson($this->courseA);

    $etudiant = User::factory()->create();
    $etudiant->assignRole('student');
    Enrollment::create([
        'user_id'   => $etudiant->id,
        'course_id' => $this->courseA->id,
        'status'    => 'active',
        'source'    => 'free',
    ]);

    $response = $this->actingAs($etudiant)->get(route('academy.dashboard'));

    $response->assertOk();
    $response->assertSee('Continuer', false);
    $response->assertSee(
        route('academy.lessons.show', ['course' => $this->courseA->slug, 'lesson' => $lesson->id]),
        false
    );
});
