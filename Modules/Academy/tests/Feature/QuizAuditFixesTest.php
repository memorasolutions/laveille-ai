<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — correctifs d'audit de la section quiz/banque :
 *  - H1 : anti-TOCTOU sur attempts_allowed (re-vérification DANS la transaction de
 *         submitQuiz). Même en contournant startQuiz (session forgée), la tentative
 *         au-delà de la limite est refusée et AUCUN QuizAttempt n'est créé.
 *  - M1 : un `answers` plus grand que le round est rejeté proprement (pas de 500).
 *  - M3 : descendantIds() est borné en profondeur (MAX_DEPTH) ; l'arbre réel
 *         (2 niveaux) reste inchangé.
 *  - M4 : la vue quiz enrobe les groupes QCM/Vrai-Faux dans <fieldset>/<legend>.
 *
 * Autonome : helpers préfixés fix1 (aucune redéclaration). SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Models\QuizAttempt;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers fix1 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function fix1Course(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours audit',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function fix1Lesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
}

function fix1Owner(Course $course): User
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

function fix1Category(User $owner, ?int $parentId = null, string $name = 'Banque audit'): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => $parentId,
        'name'      => $name,
        'position'  => 0,
    ]);
}

function fix1FillTrueFalse(QuestionCategory $cat, int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        Question::create([
            'category_id' => $cat->id,
            'owner_id'    => $cat->owner_id,
            'type'        => 'truefalse',
            'prompt'      => "Affirmation #$i (vraie)",
            'payload'     => ['answer' => true],
            'difficulty'  => 'facile',
            'is_active'   => true,
        ]);
    }
}

function fix1QuizItem(Lesson $lesson, array $payload): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 1,
        'payload'     => $payload,
        'is_required' => false,
    ]);
}

function fix1Enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function fix1Student(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

function fix1StartUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start";
}

function fix1SubmitUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit";
}

/** Round « vraifaux » (bonne réponse = index 0) au format QuizService::score(). */
function fix1TrueFalseRound(int $n): array
{
    $round = [];
    for ($i = 0; $i < $n; $i++) {
        $round[] = [
            'theme'            => 'banque',
            'difficulty'       => 'facile',
            'question'         => "Affirmation #$i (vraie)",
            'explanation'      => '',
            'general_feedback' => '',
            'fiche'            => null,
            'type'             => 'vraifaux',
            'choices'          => ['Vrai', 'Faux'],
            'correct'          => 0,
            'choice_feedback'  => [],
            'points'           => 1,
        ];
    }

    return $round;
}

/** Réponses « toutes Vrai » (= index 0) pour un round de taille $n. */
function fix1AllCorrect(int $n): array
{
    $answers = [];
    for ($i = 0; $i < $n; $i++) {
        $answers[(string) $i] = 0;
    }

    return $answers;
}

// ─────────────────────────────────────────────────────────────────────────────
// H1 — anti-TOCTOU sur attempts_allowed (re-vérification en transaction)
// ─────────────────────────────────────────────────────────────────────────────

test('H1 : submitQuiz refuse la tentative au-delà de attempts_allowed même en contournant startQuiz', function (): void {
    $course = fix1Course('audit-h1');
    $lesson = fix1Lesson($course);
    $owner  = fix1Owner($course);
    $cat    = fix1Category($owner);
    fix1FillTrueFalse($cat, 4);

    $item = fix1QuizItem($lesson, [
        'question_bank'    => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score'    => 60,
        'attempts_allowed' => 2,
    ]);

    $student = fix1Student();
    fix1Enroll($course, $student);

    // 2 tentatives légitimes (start + submit) → 2 QuizAttempt.
    for ($n = 0; $n < 2; $n++) {
        $this->actingAs($student)->post(fix1StartUrl($course, $lesson, $item));
        $round = session("academy.quiz.{$item->id}")['questions'] ?? [];
        $this->actingAs($student)
            ->post(fix1SubmitUrl($course, $lesson, $item), ['answers' => fix1AllCorrect(count($round))]);
    }

    expect(QuizAttempt::attemptCount($student->id, $item->id))->toBe(2);

    // CONTOURNEMENT : on FORGE une session de quiz active (comme un onglet ouvert
    // avant que la limite ne soit atteinte) puis on POST submit DIRECTEMENT. Sans
    // le garde H1, un 3e QuizAttempt serait créé. Avec H1 → refusé, count reste 2.
    $round = fix1TrueFalseRound(4);

    $this->actingAs($student)
        ->withSession([
            "academy.quiz.{$item->id}" => [
                'questions'  => $round,
                'started_at' => now()->toIso8601String(),
            ],
        ])
        ->post(fix1SubmitUrl($course, $lesson, $item), ['answers' => fix1AllCorrect(4)])
        ->assertSessionHas('error', 'Nombre de tentatives maximum atteint.');

    expect(QuizAttempt::attemptCount($student->id, $item->id))->toBe(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// M1 — answers bornées (pas de 500)
// ─────────────────────────────────────────────────────────────────────────────

test('M1 : un answers plus grand que le round est rejeté proprement', function (): void {
    $course = fix1Course('audit-m1');
    $lesson = fix1Lesson($course);
    $owner  = fix1Owner($course);
    $cat    = fix1Category($owner);
    fix1FillTrueFalse($cat, 4);

    $item = fix1QuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = fix1Student();
    fix1Enroll($course, $student);

    $this->actingAs($student)->post(fix1StartUrl($course, $lesson, $item));
    $round = session("academy.quiz.{$item->id}")['questions'] ?? [];

    // Round de 4 questions ; on soumet 9 réponses → rejet propre (pas de 500).
    $tooMany = [];
    for ($i = 0; $i < count($round) + 5; $i++) {
        $tooMany[(string) $i] = 0;
    }

    $this->actingAs($student)
        ->post(fix1SubmitUrl($course, $lesson, $item), ['answers' => $tooMany])
        ->assertRedirect()
        ->assertSessionHas('error', 'Réponses invalides. Recommencez le quiz.');

    // Aucune tentative enregistrée pour une soumission invalide.
    expect(QuizAttempt::attemptCount($student->id, $item->id))->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// M3 — descendantIds() borné en profondeur (l'arbre réel 2 niveaux inchangé)
// ─────────────────────────────────────────────────────────────────────────────

test('M3 : descendantIds borne la profondeur (chaîne profonde tronquée à MAX_DEPTH)', function (): void {
    $owner = User::factory()->create();

    // Chaîne profonde forgée : racine → 9 niveaux imbriqués (1 enfant par niveau).
    $root     = fix1Category($owner, null, 'Racine');
    $parentId = $root->id;
    for ($d = 1; $d <= 9; $d++) {
        $node     = fix1Category($owner, $parentId, "Niveau $d");
        $parentId = $node->id;
    }

    $ids = $root->descendantIds();

    // MAX_DEPTH = 6 → racine + 6 niveaux explorés = 7 ids max (les niveaux 7-9 exclus).
    expect(count($ids))->toBe(7);
    expect($ids)->toContain($root->id);
});

test('M3 : arbre réel 2 niveaux inchangé (racine + sous-catégories directes)', function (): void {
    $owner = User::factory()->create();

    $root = fix1Category($owner, null, 'Racine');
    $c1   = fix1Category($owner, $root->id, 'Sous 1');
    $c2   = fix1Category($owner, $root->id, 'Sous 2');
    $c3   = fix1Category($owner, $root->id, 'Sous 3');

    $ids = $root->descendantIds();

    expect(count($ids))->toBe(4);
    expect($ids)->toContain($root->id, $c1->id, $c2->id, $c3->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// M4 — fieldset/legend autour des groupes QCM/Vrai-Faux dans la vue quiz
// ─────────────────────────────────────────────────────────────────────────────

test('M4 : le lecteur de quiz enrobe les groupes QCM/Vrai-Faux dans fieldset/legend', function (): void {
    $course = fix1Course('audit-m4');
    $lesson = fix1Lesson($course);
    $owner  = fix1Owner($course);
    $cat    = fix1Category($owner);
    fix1FillTrueFalse($cat, 4);

    $item = fix1QuizItem($lesson, [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ]);

    $student = fix1Student();
    fix1Enroll($course, $student);

    // Quiz actif → la vue rend les groupes de choix (radios) enrobés.
    $this->actingAs($student)->post(fix1StartUrl($course, $lesson, $item));

    $this->actingAs($student)
        ->get("/academie/courses/{$course->slug}/lessons/{$lesson->id}")
        ->assertOk()
        ->assertSee('<fieldset', false)
        ->assertSee('<legend', false);
});
