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
// 2. Test de régression B01 (APRÈS fix), réconcilié avec BUG-001 (2026-07-01)
//    La vraie faille B01 concernait les cours PRIVÉS/NON-LISTÉS accessibles à un
//    guest sans redirection. Un cours PUBLIC, lui, doit rester accessible à
//    l'anonyme (BUG-001, SEO/GEO) — voir section 4 ci-dessous.
//    Ce test ÉCHOUE avant le fix (guest → 200 sur un cours privé) et PASSE
//    après (guest → 302 vers la connexion).
// ─────────────────────────────────────────────────────────────────────────────

test('B01 régression — guest sur lessons.show d\'un cours PRIVÉ est redirigé 302 vers la connexion', function (): void {
    $course = b01Course('b01-cours-auth-prive');
    $course->update(['visibility' => 'private']);
    $lesson = b01Lesson($course);

    // Après le fix, le guest sur un cours non-public est redirigé (jamais de 404 muet).
    $this->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertRedirect(route('login'));
});

test('B01 régression — guest sur lessons.show d\'un cours NON-LISTÉ est redirigé 302 vers la connexion', function (): void {
    $course = b01Course('b01-cours-auth-non-liste');
    $course->update(['visibility' => 'unlisted']);
    $lesson = b01Lesson($course);

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

// ─────────────────────────────────────────────────────────────────────────────
// 4. Réconciliation B01 + BUG-001 (2026-07-01)
//    Documente le comportement voulu : un guest sur une leçon d'un cours PUBLIC
//    reçoit 200 (BUG-001, aucune régression SEO/GEO), tandis qu'un guest sur un
//    cours PRIVÉ/NON-LISTÉ est redirigé vers la connexion (B01, section 2 ci-dessus).
//    Ce test est le pendant de AcademyPreviewTest (« une leçon d'un cours publié
//    reste accessible sans preview ») : les deux doivent être verts simultanément.
// ─────────────────────────────────────────────────────────────────────────────

test('B01 + BUG-001 réconciliés — guest sur une leçon d\'un cours PUBLIC reçoit 200', function (): void {
    $course = b01Course('b01-bug001-cours-public');
    $lesson = b01Lesson($course);

    $this->get(route('academy.lessons.show', [$course, $lesson]))
        ->assertStatus(200);
});
