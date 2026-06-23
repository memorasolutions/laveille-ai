<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — TYPE ESSAI (réponse rédigée à CORRECTION MANUELLE, type Moodle « Essay »).
 *
 * Prouve que :
 *  - QuizService::score marque un essai « à corriger » (0 point auto, needs_grading) ;
 *  - à la soumission, les autres questions sont auto-notées, l'essai met la tentative
 *    EN ATTENTE (needs_grading=true), la complétion N'EST PAS posée, le lecteur affiche
 *    « en attente de correction » ;
 *  - le formateur corrige (points + feedback) → score/percent recalculés (auto + manuel),
 *    passed correct, complétion posée si réussi, needs_grading=false, graded_at/by ;
 *  - sécurité : non-gérant → 403, anti-IDOR (tentative d'un autre cours), points hors
 *    bornes rejetés ;
 *  - RÉTROCOMPAT : un quiz SANS essai reste auto-noté immédiat (needs_grading=false) ;
 *  - anti-XSS sur la réponse d'essai + grader_info (rendu strippé).
 *
 * Autonome : helpers préfixés `essai`. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\EssayGrading;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\QuizGradeService;
use Modules\Academy\Services\QuizService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers autonomes (préfixe essai)
// ─────────────────────────────────────────────────────────────────────────────

function essaiCourse(string $slug = 'cours-essai'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours essai',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Crée un item quiz + sa leçon/chapitre. @return array{course: Course, lesson: Lesson, item: LessonItem} */
function essaiQuizScaffold(Course $course, array $payload = []): array
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
    $item = LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 1,
        'payload'     => $payload,
        'is_required' => false,
    ]);

    return ['course' => $course, 'lesson' => $lesson, 'item' => $item];
}

function essaiOwner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    return $owner;
}

function essaiEnrolledStudent(Course $course): User
{
    $student = User::factory()->create();
    Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

    return $student;
}

/** Round mixte 1 qcm (1 pt) + 1 essai (4 pts). L'essai est à l'index 1. */
function essaiMixedRound(): array
{
    return [
        ['type' => 'qcm', 'question' => 'Capitale ?', 'choices' => ['Bonne', 'Mauvaise'], 'correct' => 0, 'points' => 1],
        ['type' => 'essai', 'question' => 'Développez votre pensée.', 'grader_info' => 'Cherchez 3 arguments.', 'points' => 4],
    ];
}

/** Soumet un round (déposé en session) via le contrôleur, en tant que $student inscrit. */
function essaiSubmit(object $test, User $student, array $scaffold, array $round, array $answers): \Illuminate\Testing\TestResponse
{
    $item   = $scaffold['item'];
    $course = $scaffold['course'];
    $lesson = $scaffold['lesson'];

    return $test->actingAs($student)
        ->withSession([
            "academy.quiz.{$item->id}" => [
                'questions'  => $round,
                'started_at' => now()->toIso8601String(),
            ],
        ])
        ->post(route('academy.quiz.submit', [$course->slug, $lesson->id, $item->id]), [
            'answers' => $answers,
        ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. QuizService::score — un essai est « à corriger » (0 auto)
// ─────────────────────────────────────────────────────────────────────────────

test('score : un essai rapporte 0 auto et lève needs_grading', function (): void {
    $round  = essaiMixedRound();
    $result = QuizService::score($round, ['0' => 0, '1' => 'Ma réponse']);

    expect($result['needs_grading'])->toBeTrue();
    expect($result['essay_indexes'])->toBe([1]);
    // Auto : seul le qcm (1 pt) est gagné ; l'essai (4 pts) reste à 0 mais COMPTE dans possible.
    expect($result['points_earned'])->toBe(1);
    expect($result['points_possible'])->toBe(5);
    expect($result['percent'])->toBe(20); // provisoire (1/5)
    expect($result['correct'])->toBe(1);  // seul le qcm est « correct »
});

test('score : un quiz SANS essai garde needs_grading=false (rétrocompat)', function (): void {
    $round  = [['type' => 'qcm', 'question' => 'Q', 'choices' => ['A', 'B'], 'correct' => 0, 'points' => 1]];
    $result = QuizService::score($round, ['0' => 0]);

    expect($result['needs_grading'])->toBeFalse();
    expect($result['essay_indexes'])->toBe([]);
    expect($result['percent'])->toBe(100);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Soumission : essai en attente, qcm auto-noté, complétion NON posée
// ─────────────────────────────────────────────────────────────────────────────

test('soumission d’un quiz à essai : tentative en attente, complétion non posée', function (): void {
    $course   = essaiCourse('cours-soumission');
    $scaffold = essaiQuizScaffold($course);
    $student  = essaiEnrolledStudent($course);

    essaiSubmit($this, $student, $scaffold, essaiMixedRound(), ['0' => 0, '1' => 'Ma réponse rédigée'])
        ->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)->first();
    expect($attempt)->not->toBeNull();
    expect($attempt->needs_grading)->toBeTrue();
    expect($attempt->passed)->toBeFalse();      // jamais réussi tant que non corrigé
    expect($attempt->percent)->toBe(20);        // provisoire (qcm seul)

    // Complétion NON posée (le quiz n'est complété qu'après correction).
    expect(Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $scaffold['item']->id)
        ->where('status', 'completed')->exists())->toBeFalse();

    // L'étudiant voit « en attente de correction » (flash result needs_grading).
    $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course->slug, $scaffold['lesson']->id]))
        ->assertSee('en attente de correction', false);
})->skip(fn () => ! \Illuminate\Support\Facades\Route::has('academy.lessons.show'), 'route lessons.show absente');

// ─────────────────────────────────────────────────────────────────────────────
// 3. Correction par le formateur → recalcul + complétion posée
// ─────────────────────────────────────────────────────────────────────────────

test('le formateur corrige l’essai : note recalculée, réussite, complétion posée', function (): void {
    $course   = essaiCourse('cours-correction');
    $scaffold = essaiQuizScaffold($course, ['passing_score' => 60]);
    $owner    = essaiOwner($course);
    $student  = essaiEnrolledStudent($course);

    // Soumission (qcm bon = 1/5 provisoire, essai à corriger).
    essaiSubmit($this, $student, $scaffold, essaiMixedRound(), ['0' => 0, '1' => 'Une bonne réponse argumentée']);
    $attempt = QuizAttempt::where('user_id', $student->id)->firstOrFail();

    // Le formateur attribue 4/4 à l'essai.
    Livewire::actingAs($owner)
        ->test(EssayGrading::class, ['course' => $course])
        ->call('startGrading', $attempt->id)
        ->set('essayScores.1', '4')
        ->set('essayFeedback.1', 'Excellente analyse.')
        ->call('gradeAttempt')
        ->assertHasNoErrors();

    $attempt->refresh();
    expect($attempt->needs_grading)->toBeFalse();
    expect($attempt->percent)->toBe(100);        // (1 + 4) / 5
    expect($attempt->passed)->toBeTrue();
    expect($attempt->graded_by)->toBe($owner->id);
    expect($attempt->graded_at)->not->toBeNull();
    expect($attempt->manual_scores['1'] ?? ($attempt->manual_scores[1] ?? null))->toBe(4);
    expect($attempt->manual_feedback['1'] ?? ($attempt->manual_feedback[1] ?? null))->toBe('Excellente analyse.');

    // Complétion DÉSORMAIS posée (réussi après correction).
    expect(Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $scaffold['item']->id)
        ->where('status', 'completed')->exists())->toBeTrue();
});

test('correction donnant un score insuffisant : pas de complétion', function (): void {
    $course   = essaiCourse('cours-echec');
    $scaffold = essaiQuizScaffold($course, ['passing_score' => 60]);
    $owner    = essaiOwner($course);
    $student  = essaiEnrolledStudent($course);

    // qcm MAUVAIS (0) + essai noté 0 → 0/5 → échec.
    essaiSubmit($this, $student, $scaffold, essaiMixedRound(), ['0' => 1, '1' => 'Réponse hors sujet']);
    $attempt = QuizAttempt::where('user_id', $student->id)->firstOrFail();

    Livewire::actingAs($owner)
        ->test(EssayGrading::class, ['course' => $course])
        ->call('startGrading', $attempt->id)
        ->set('essayScores.1', '0')
        ->call('gradeAttempt')
        ->assertHasNoErrors();

    $attempt->refresh();
    expect($attempt->needs_grading)->toBeFalse();
    expect($attempt->percent)->toBe(0);
    expect($attempt->passed)->toBeFalse();
    expect(Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $scaffold['item']->id)
        ->where('status', 'completed')->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Sécurité : gate, anti-IDOR, bornes
// ─────────────────────────────────────────────────────────────────────────────

test('un étudiant (non-gérant) ne peut pas accéder à l’écran de correction', function (): void {
    $course  = essaiCourse('cours-gate');
    $student = essaiEnrolledStudent($course);

    // L'autorisation d'entrée (manageEnrollments) échoue → 403 (Livewire convertit
    // l'AuthorizationException du mount en réponse interdite).
    Livewire::actingAs($student)
        ->test(EssayGrading::class, ['course' => $course])
        ->assertForbidden();
});

test('anti-IDOR : impossible de corriger une tentative d’un AUTRE cours', function (): void {
    $courseA = essaiCourse('cours-a');
    $courseB = essaiCourse('cours-b');
    $ownerA  = essaiOwner($courseA);

    $scaffoldB = essaiQuizScaffold($courseB);
    $studentB  = essaiEnrolledStudent($courseB);

    // Tentative en attente dans le cours B.
    $attemptB = QuizAttempt::create([
        'user_id'            => $studentB->id,
        'lesson_item_id'     => $scaffoldB['item']->id,
        'course_id'          => $courseB->id,
        'score'              => 0,
        'max_score'          => 2,
        'percent'            => 0,
        'passed'             => false,
        'needs_grading'      => true,
        'answers'            => ['1' => 'Réponse B'],
        'questions_snapshot' => essaiMixedRound(),
        'submitted_at'       => now(),
    ]);

    // ownerA gère le cours A → ne doit JAMAIS résoudre la tentative du cours B.
    expect(fn () => Livewire::actingAs($ownerA)
        ->test(EssayGrading::class, ['course' => $courseA])
        ->call('startGrading', $attemptB->id))
        ->toThrow(ModelNotFoundException::class);

    // Toujours en attente (rien écrit).
    expect($attemptB->fresh()->needs_grading)->toBeTrue();
});

test('points hors bornes [0..max] rejetés (rien écrit)', function (): void {
    $course   = essaiCourse('cours-bornes');
    $scaffold = essaiQuizScaffold($course);
    $owner    = essaiOwner($course);
    $student  = essaiEnrolledStudent($course);

    essaiSubmit($this, $student, $scaffold, essaiMixedRound(), ['0' => 0, '1' => 'Réponse']);
    $attempt = QuizAttempt::where('user_id', $student->id)->firstOrFail();

    // L'essai vaut 4 points max → 99 est rejeté.
    Livewire::actingAs($owner)
        ->test(EssayGrading::class, ['course' => $course])
        ->call('startGrading', $attempt->id)
        ->set('essayScores.1', '99')
        ->call('gradeAttempt')
        ->assertHasErrors('essayScores.1');

    expect($attempt->fresh()->needs_grading)->toBeTrue(); // toujours à corriger
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Rétrocompat : quiz SANS essai = auto-noté immédiat
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : un quiz sans essai est auto-noté et complété immédiatement', function (): void {
    $course   = essaiCourse('cours-retrocompat');
    $scaffold = essaiQuizScaffold($course, ['passing_score' => 60]);
    $student  = essaiEnrolledStudent($course);

    $round = [['type' => 'qcm', 'question' => 'Q', 'choices' => ['A', 'B'], 'correct' => 0, 'points' => 1]];

    essaiSubmit($this, $student, $scaffold, $round, ['0' => 0])->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)->firstOrFail();
    expect($attempt->needs_grading)->toBeFalse();
    expect($attempt->passed)->toBeTrue();
    expect($attempt->percent)->toBe(100);

    expect(Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $scaffold['item']->id)
        ->where('status', 'completed')->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. Carnet : une tentative en attente = « à corriger » (ni 0 ni 100)
// ─────────────────────────────────────────────────────────────────────────────

test('effectiveGrade : tentative en attente → pending (exclue de la note finale)', function (): void {
    $course   = essaiCourse('cours-effective');
    $scaffold = essaiQuizScaffold($course);
    $student  = essaiEnrolledStudent($course);

    essaiSubmit($this, $student, $scaffold, essaiMixedRound(), ['0' => 0, '1' => 'Réponse']);

    $grade = QuizGradeService::effectiveGrade($student->id, $scaffold['item']);
    expect($grade['attempts'])->toBe(1);
    expect($grade['pending'])->toBeTrue();   // « à corriger », pas une note définitive

    // Après correction → la note finale est reflétée (plus pending).
    $owner   = essaiOwner($course);
    $attempt = QuizAttempt::where('user_id', $student->id)->firstOrFail();
    Livewire::actingAs($owner)
        ->test(EssayGrading::class, ['course' => $course])
        ->call('startGrading', $attempt->id)
        ->set('essayScores.1', '4')
        ->call('gradeAttempt')
        ->assertHasNoErrors();

    $graded = QuizGradeService::effectiveGrade($student->id, $scaffold['item']);
    expect($graded['pending'])->toBeFalse();
    expect($graded['percent'])->toBe(100);
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. Anti-XSS : réponse d'essai + grader_info rendus strippés
// ─────────────────────────────────────────────────────────────────────────────

test('anti-XSS : la réponse d’essai et grader_info sont rendus sans balise script', function (): void {
    $course   = essaiCourse('cours-xss');
    $scaffold = essaiQuizScaffold($course);
    $owner    = essaiOwner($course);
    $student  = essaiEnrolledStudent($course);

    $round = [[
        'type'        => 'essai',
        'question'    => 'Énoncé',
        'grader_info' => 'Consigne <script>alert("info")</script>',
        'points'      => 4,
    ]];
    $answers = ['0' => 'Ma réponse <script>alert("xss")</script>'];

    essaiSubmit($this, $student, $scaffold, $round, $answers);
    $attempt = QuizAttempt::where('user_id', $student->id)->firstOrFail();

    Livewire::actingAs($owner)
        ->test(EssayGrading::class, ['course' => $course])
        ->call('startGrading', $attempt->id)
        ->assertDontSee('<script>', false);
});
