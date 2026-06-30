<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - DeckPlayer (drapeau academy.lesson_deck_mode).
 *
 * Prouve que :
 *  (a) drapeau OFF : la vue classique est rendue (academy-lesson-content visible) ;
 *  (b) drapeau ON  : le DeckPlayer est rendu (deck-player-wrap visible, classique absent) ;
 *  (c) drapeau ON  : leçon vide rend le DeckPlayer sans erreur.
 *
 * Garde-fou : skippé si le module Academy est désactivé.
 */

declare(strict_types=1);

namespace Modules\Academy\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers dp_ (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function dp_course(string $slug = 'dp-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours DeckPlayer',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function dp_lesson(Course $course, string $slug = ''): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre DP',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon DP',
        'slug'       => $slug ?: 'lecon-dp-' . $chapter->id,
        'position'   => 1,
    ]);
}

function dp_item(Lesson $lesson, string $type = 'doc', int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => $type,
        'title'     => 'Item ' . $type,
        'position'  => $position,
        'payload'   => [],
    ]);
}

function dp_user(): User
{
    return User::factory()->create();
}

function dp_enroll(User $user, Course $course): Enrollment
{
    return Enrollment::create([
        'user_id'     => $user->id,
        'course_id'   => $course->id,
        'status'      => 'active',
        'enrolled_at' => now(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Setup commun
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    config()->set('academy.lesson_deck_mode', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Tests
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau OFF : la vue classique est rendue sans DeckPlayer', function (): void {
    config()->set('academy.lesson_deck_mode', false);

    $course = dp_course('dp-off');
    $lesson = dp_lesson($course, 'lecon-dp-off');
    dp_item($lesson, 'doc', 1);
    dp_item($lesson, 'doc', 2);

    $user = dp_user();
    dp_enroll($user, $course);

    $response = $this->actingAs($user)
        ->get(route('academy.lessons.show', [$course, $lesson]));

    $response->assertStatus(200);
    $response->assertSee('lesson-classic-view', false);
    $response->assertDontSee('deck-player-wrap', false);
});

test('drapeau ON : le DeckPlayer est rendu et contient le compteur de cartes', function (): void {
    config()->set('academy.lesson_deck_mode', true);

    $course = dp_course('dp-on');
    $lesson = dp_lesson($course, 'lecon-dp-on');
    dp_item($lesson, 'doc', 1);
    dp_item($lesson, 'quiz', 2);
    dp_item($lesson, 'choice', 3);

    $user = dp_user();
    dp_enroll($user, $course);

    $response = $this->actingAs($user)
        ->get(route('academy.lessons.show', [$course, $lesson]));

    $response->assertStatus(200);
    $response->assertSee('deck-player-wrap', false);
    $response->assertSee('Carte 1 / 3', false);
    $response->assertDontSee('lesson-classic-view', false);
});

test('drapeau ON : leçon vide rend le DeckPlayer sans erreur', function (): void {
    config()->set('academy.lesson_deck_mode', true);

    $course = dp_course('dp-empty');
    $lesson = dp_lesson($course, 'lecon-dp-empty');
    // Aucun item ajouté volontairement.

    $user = dp_user();
    dp_enroll($user, $course);

    $response = $this->actingAs($user)
        ->get(route('academy.lessons.show', [$course, $lesson]));

    $response->assertStatus(200);
    $response->assertSee('deck-player-wrap', false);
});
