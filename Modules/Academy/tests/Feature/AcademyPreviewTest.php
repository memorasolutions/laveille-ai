<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Mode « Prévisualiser en tant qu'étudiant » (B3).
 *
 * Prouve la SÉCURITÉ de la gate de prévisualisation : seul un GÉRANT de CE cours
 * (admin OU owner/instructor/editor) peut voir un cours BROUILLON via ?preview=1.
 * Le query param seul ne suffit JAMAIS (la gate exige can('update', $course)).
 *
 *  - gérant + ?preview=1 sur un cours brouillon  → 200 (voit le contenu) ;
 *  - user lambda connecté + ?preview=1           → 404 (can('update') échoue) ;
 *  - visiteur anonyme + ?preview=1               → ne fuite jamais le contenu (404/redirect) ;
 *  - sans ?preview=1, un brouillon reste 404 pour tous (comportement inchangé) ;
 *  - un cours publié + public reste accessible (régression).
 *
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    // On désactive le mode « en construction » pour que le middleware laisse passer :
    // on teste ici la gate de prévisualisation des contrôleurs, pas la gate bêta.
    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Helper : crée un cours (brouillon par défaut) avec un chapitre + une leçon. */
function makePreviewCourse(string $slug, string $status = 'draft'): Course
{
    $course = Course::create([
        'slug'        => $slug,
        'title'       => 'Cours '.$slug,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => $status,
        'currency'    => 'CAD',
        'published_at' => $status === 'published' ? now() : null,
    ]);

    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre 1',
        'position'  => 1,
    ]);

    Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon 1',
        'slug'       => 'lecon-1-'.$course->id,
        'position'   => 1,
    ]);

    return $course->fresh();
}

/** Helper : formateur owner d'un cours donné (gérant → can('update')). */
function makePreviewOwner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $owner->id,
        'role'      => 'owner',
    ]);

    return $owner;
}

/** Helper : admin Académie (gérant de TOUT cours). */
function makePreviewAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    return $admin;
}

/** Helper : première leçon du cours. */
function previewFirstLesson(Course $course): Lesson
{
    return $course->chapters()->orderBy('position')->first()
        ->lessons()->orderBy('position')->first();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. GÉRANT — voit le brouillon en preview (fiche + leçon)
// ─────────────────────────────────────────────────────────────────────────────

test('owner gérant voit la FICHE d\'un cours brouillon avec ?preview=1', function (): void {
    $course = makePreviewCourse('preview-owner');
    $owner  = makePreviewOwner($course);

    $this->actingAs($owner)
        ->get(route('academy.courses.show', $course->slug).'?preview=1')
        ->assertOk()
        ->assertSee('Mode prévisualisation', false);
});

test('owner gérant voit une LEÇON d\'un cours brouillon avec ?preview=1', function (): void {
    $course = makePreviewCourse('preview-owner-lesson');
    $owner  = makePreviewOwner($course);
    $lesson = previewFirstLesson($course);

    $this->actingAs($owner)
        ->get(route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $lesson]).'?preview=1')
        ->assertOk()
        ->assertSee('Mode prévisualisation', false);
});

test('admin voit n\'importe quel cours brouillon en preview', function (): void {
    $course = makePreviewCourse('preview-admin');
    $lesson = previewFirstLesson($course);

    $this->actingAs(makePreviewAdmin())
        ->get(route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $lesson]).'?preview=1')
        ->assertOk();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. NON-GÉRANT — le query param ?preview=1 ne donne JAMAIS accès au brouillon
// ─────────────────────────────────────────────────────────────────────────────

test('user lambda connecté + ?preview=1 sur un brouillon → 404 (FICHE)', function (): void {
    $course = makePreviewCourse('preview-lambda');
    $lambda = User::factory()->create(); // aucun rôle sur ce cours → can('update') faux

    $this->actingAs($lambda)
        ->get(route('academy.courses.show', $course->slug).'?preview=1')
        ->assertNotFound();
});

test('user lambda connecté + ?preview=1 sur un brouillon → 404 (LEÇON)', function (): void {
    $course = makePreviewCourse('preview-lambda-lesson');
    $lambda = User::factory()->create();
    $lesson = previewFirstLesson($course);

    $this->actingAs($lambda)
        ->get(route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $lesson]).'?preview=1')
        ->assertNotFound();
});

test('un owner ne peut pas prévisualiser le cours brouillon d\'un AUTRE (anti-escalade)', function (): void {
    $courseA = makePreviewCourse('preview-a');
    $courseB = makePreviewCourse('preview-b');
    $ownerA  = makePreviewOwner($courseA);

    $this->actingAs($ownerA)
        ->get(route('academy.courses.show', $courseB->slug).'?preview=1')
        ->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. ANONYME — aucune fuite de contenu
// ─────────────────────────────────────────────────────────────────────────────

test('visiteur anonyme + ?preview=1 sur un brouillon ne fuite jamais le contenu', function (): void {
    $course = makePreviewCourse('preview-anon');

    $response = $this->get(route('academy.courses.show', $course->slug).'?preview=1');

    // Anonyme : can('update') faux → 404 public (le contenu n'est jamais rendu).
    $response->assertNotFound();
    $response->assertDontSee('Mode prévisualisation', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. SANS ?preview=1 — comportement inchangé
// ─────────────────────────────────────────────────────────────────────────────

test('sans ?preview=1, un brouillon reste 404 même pour son owner', function (): void {
    $course = makePreviewCourse('preview-no-flag');
    $owner  = makePreviewOwner($course);

    $this->actingAs($owner)
        ->get(route('academy.courses.show', $course->slug))
        ->assertNotFound();
});

test('sans ?preview=1, un brouillon reste 404 pour un anonyme', function (): void {
    $course = makePreviewCourse('preview-no-flag-anon');

    $this->get(route('academy.courses.show', $course->slug))->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. RÉGRESSION — un cours publié + public reste accessible normalement
// ─────────────────────────────────────────────────────────────────────────────

test('un cours publié + public reste accessible sans preview', function (): void {
    $course = makePreviewCourse('preview-published', 'published');

    $this->get(route('academy.courses.show', $course->slug))
        ->assertOk()
        ->assertDontSee('Mode prévisualisation', false);
});

test('une leçon d\'un cours publié reste accessible sans preview', function (): void {
    $course = makePreviewCourse('preview-published-lesson', 'published');
    $lesson = previewFirstLesson($course);

    $this->get(route('academy.lessons.show', ['course' => $course->slug, 'lesson' => $lesson]))
        ->assertOk();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. PARTAGE MARKETING — bloc « Partager ce cours » visible seulement si publié
// ─────────────────────────────────────────────────────────────────────────────

test('un cours publié affiche le bloc « Partager ce cours » + liens FB/LinkedIn/X', function (): void {
    $course = makePreviewCourse('preview-share-published', 'published');

    $response = $this->get(route('academy.courses.show', $course->slug))->assertOk();

    $response->assertSee('Partager ce cours', false);
    $response->assertSee('facebook.com/sharer', false);
    $response->assertSee('linkedin.com/sharing', false);
    $response->assertSee('twitter.com/intent/tweet', false);
});

test('un cours brouillon en prévisualisation n\'affiche PAS le bloc de partage', function (): void {
    $course = makePreviewCourse('preview-share-draft');
    $owner  = makePreviewOwner($course);

    $this->actingAs($owner)
        ->get(route('academy.courses.show', $course->slug).'?preview=1')
        ->assertOk()
        ->assertDontSee('Partager ce cours', false);
});
