<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Authentification requise sur le lecteur de leçon (BUG B01).
 *
 * Prouve que :
 *  - AVANT fix : un guest sur academy.lessons.show reçoit 200 (bug — aucun middleware auth)
 *  - APRÈS fix : le guest est redirigé 302 vers la connexion
 *  - Un étudiant inscrit peut accéder normalement (200)
 *
 * Helpers préfixés b01 (autonomes, sans conflit de noms).
 * Garde-fou : skippé si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    // Désactiver le mode « en construction » pour ne pas confondre les deux gates.
    config()->set('academy.under_construction', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers b01 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Crée un cours publié minimal. */
function b01Course(string $slug = 'b01-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'B01 Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Crée un chapitre et une leçon pour le cours donné. */
function b01Lesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre B01',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon B01',
        'slug'       => 'lecon-b01-' . $course->id,
        'position'   => 1,
    ]);
}

/** Crée un étudiant avec une inscription active. */
function b01Student(Course $course): User
{
    $user = User::factory()->create();

    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    return $user;
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Caractérisation du bug B01 (AVANT fix)
//    Ce test PASSE avant le fix : il prouve que le guest voit 200 (bug).
//    Il doit ÉCHOUER après l'application du fix.
// ─────────────────────────────────────────────────────────────────────────────

test('B01 caractérisation — guest sur lessons.show reçoit 200 (route sans middleware auth)', function (): void {
    $course = b01Course();
    $lesson = b01Lesson($course);

    // Sans le fix, le guest reçoit 200 (gate logicielle sans redirection).
    $this->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(200);
})->skip('Caractérisation du bug B01 — désactiver le skip pour confirmer le bug AVANT le fix.');

// ─────────────────────────────────────────────────────────────────────────────
// 2. Test de régression B01 (APRÈS fix)
//    Ce test ÉCHOUE avant le fix (guest → 200) et PASSE après (guest → 302).
// ─────────────────────────────────────────────────────────────────────────────

test('B01 régression — guest sur lessons.show est redirigé 302 vers la connexion', function (): void {
    $course = b01Course('b01-cours-auth');
    $lesson = b01Lesson($course);

    // Après le fix (middleware auth ajouté), le guest est redirigé.
    $this->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertRedirect(route('login'));
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Non-régression : un inscrit actif peut toujours accéder (200)
// ─────────────────────────────────────────────────────────────────────────────

test('B01 non-régression — étudiant inscrit accède à lessons.show (200)', function (): void {
    $course  = b01Course('b01-cours-inscrit');
    $lesson  = b01Lesson($course);
    $student = b01Student($course);

    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(200);
});
