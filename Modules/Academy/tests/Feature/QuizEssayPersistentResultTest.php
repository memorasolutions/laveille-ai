<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - C1/C2/C4 : RÉSULTAT PERSISTANT d'un quiz (note + révision + feedback du
 * formateur visibles au RECHARGEMENT, sans flash de session).
 *
 * Prouve que :
 *  - C1 : un étudiant qui re-charge une leçon (sans flash) voit le DERNIER résultat de
 *    SA tentative - note, révision et, pour un essai corrigé, le manual_feedback du
 *    formateur + les points obtenus ;
 *  - C1 : une tentative encore needs_grading affiche « en attente de correction » (jamais
 *    de faux score) ;
 *  - C1 : anti-IDOR (un autre étudiant ne voit pas la tentative d'autrui) ;
 *  - C1 : review_options respecté (pas de bonne réponse si interdit) ;
 *  - C2 : à la correction, la complétion reçoit un score COHÉRENT (jamais 0 quand l'essai
 *    vaut des points) ;
 *  - C4 : une réponse d'essai > 50 000 caractères est rejetée proprement (pas de 500) ;
 *  - RÉTROCOMPAT : un quiz auto-noté (sans essai) reste inchangé.
 *
 * Autonome : helpers préfixés `fix5`. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
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

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers autonomes (préfixe fix5)
// ─────────────────────────────────────────────────────────────────────────────

function fix5Course(string $slug = 'cours-fix5'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours fix5',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Crée un item quiz + sa leçon/chapitre. @return array{course: Course, lesson: Lesson, item: LessonItem} */
function fix5Quiz(Course $course, array $payload = []): array
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

function fix5Owner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    return $owner;
}

function fix5Student(Course $course): User
{
    $student = User::factory()->create();
    Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

    return $student;
}

/** Round mixte 1 qcm (1 pt) + 1 essai (4 pts). L'essai est à l'index 1. */
function fix5MixedRound(): array
{
    return [
        ['type' => 'qcm', 'question' => 'Capitale ?', 'choices' => ['Bonne', 'Mauvaise'], 'correct' => 0, 'points' => 1],
        ['type' => 'essai', 'question' => 'Développez votre pensée.', 'grader_info' => 'Cherchez 3 arguments.', 'points' => 4],
    ];
}

function fix5LessonUrl(Course $course, Lesson $lesson): string
{
    return route('academy.lessons.show', [$course->slug, $lesson->id]);
}

// ─────────────────────────────────────────────────────────────────────────────
// C1 - Résultat persistant d'un essai corrigé : note + feedback du formateur
// ─────────────────────────────────────────────────────────────────────────────

test('C1 : un essai corrigé affiche, au rechargement (sans flash), la note + le feedback', function (): void {
    $course   = fix5Course('cours-c1-graded');
    $scaffold = fix5Quiz($course, ['passing_score' => 60]);
    $student  = fix5Student($course);

    // Tentative CORRIGÉE (needs_grading=false) avec points + feedback du formateur.
    QuizAttempt::create([
        'user_id'            => $student->id,
        'lesson_item_id'     => $scaffold['item']->id,
        'course_id'          => $course->id,
        'score'              => 2,
        'max_score'          => 2,
        'percent'            => 100,
        'passed'             => true,
        'needs_grading'      => false,
        'answers'            => ['0' => 0, '1' => 'Mon analyse rédigée détaillée'],
        'manual_scores'      => ['1' => 4],
        'manual_feedback'    => ['1' => 'Bravo, analyse remarquable et structurée.'],
        'questions_snapshot' => fix5MixedRound(),
        'submitted_at'       => now()->subMinutes(5),
        'graded_at'          => now(),
    ]);

    $html = $this->actingAs($student)
        ->get(fix5LessonUrl($course, $scaffold['lesson']))
        ->assertOk()
        ->getContent();

    // Note finale + révision + feedback du formateur visibles SANS flash.
    expect($html)->toContain('Révision de vos réponses');
    expect($html)->toContain('Bravo, analyse remarquable et structurée.');
    expect($html)->toContain('Points obtenus');
    expect($html)->toContain('Mon analyse rédigée détaillée');
    // Ce n'est PAS une tentative en attente.
    expect($html)->not->toContain('en attente de correction');
})->skip(fn () => ! \Illuminate\Support\Facades\Route::has('academy.lessons.show'), 'route lessons.show absente');

// ─────────────────────────────────────────────────────────────────────────────
// C1 - Tentative encore en attente : « en attente », jamais un faux score
// ─────────────────────────────────────────────────────────────────────────────

test('C1 : une tentative needs_grading affiche « en attente », sans note', function (): void {
    $course   = fix5Course('cours-c1-pending');
    $scaffold = fix5Quiz($course);
    $student  = fix5Student($course);

    QuizAttempt::create([
        'user_id'            => $student->id,
        'lesson_item_id'     => $scaffold['item']->id,
        'course_id'          => $course->id,
        'score'              => 1,
        'max_score'          => 2,
        'percent'            => 20, // provisoire (qcm seul)
        'passed'             => false,
        'needs_grading'      => true,
        'answers'            => ['0' => 0, '1' => 'Réponse en attente'],
        'questions_snapshot' => fix5MixedRound(),
        'submitted_at'       => now(),
    ]);

    $html = $this->actingAs($student)
        ->get(fix5LessonUrl($course, $scaffold['lesson']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('en attente de correction');
    // Aucun faux score : ni « bonnes réponses » (panneau score) ni « Quiz Réussi ».
    expect($html)->not->toContain('bonnes réponses');
    expect($html)->not->toContain('Quiz Réussi');
})->skip(fn () => ! \Illuminate\Support\Facades\Route::has('academy.lessons.show'), 'route lessons.show absente');

// ─────────────────────────────────────────────────────────────────────────────
// C1 - Anti-IDOR : un autre étudiant ne voit pas la tentative d'autrui
// ─────────────────────────────────────────────────────────────────────────────

test('C1 : anti-IDOR - un autre étudiant ne voit pas la tentative + feedback d’autrui', function (): void {
    $course   = fix5Course('cours-c1-idor');
    $scaffold = fix5Quiz($course, ['passing_score' => 60]);
    $victim   = fix5Student($course);
    $other    = fix5Student($course); // inscrit, AUCUNE tentative

    QuizAttempt::create([
        'user_id'            => $victim->id,
        'lesson_item_id'     => $scaffold['item']->id,
        'course_id'          => $course->id,
        'score'              => 2,
        'max_score'          => 2,
        'percent'            => 100,
        'passed'             => true,
        'needs_grading'      => false,
        'answers'            => ['0' => 0, '1' => 'Réponse privée de la victime'],
        'manual_scores'      => ['1' => 4],
        'manual_feedback'    => ['1' => 'Feedback prive de la victime'],
        'questions_snapshot' => fix5MixedRound(),
        'submitted_at'       => now()->subMinute(),
        'graded_at'          => now(),
    ]);

    $html = $this->actingAs($other)
        ->get(fix5LessonUrl($course, $scaffold['lesson']))
        ->assertOk()
        ->getContent();

    expect($html)->not->toContain('Révision de vos réponses');
    expect($html)->not->toContain('Feedback prive de la victime');
    expect($html)->not->toContain('Réponse privée de la victime');
})->skip(fn () => ! \Illuminate\Support\Facades\Route::has('academy.lessons.show'), 'route lessons.show absente');

// ─────────────────────────────────────────────────────────────────────────────
// C1 - review_options respecté : pas de bonne réponse si interdit
// ─────────────────────────────────────────────────────────────────────────────

test('C1 : persistant - review_options interdit la bonne réponse', function (): void {
    $course   = fix5Course('cours-c1-review');
    $scaffold = fix5Quiz($course, [
        'passing_score'  => 60,
        'review_options' => ['show_right_answer' => false],
    ]);
    $student = fix5Student($course);

    // Tentative auto-notée (qcm faux) → review affichée au rechargement (persistant).
    QuizAttempt::create([
        'user_id'            => $student->id,
        'lesson_item_id'     => $scaffold['item']->id,
        'course_id'          => $course->id,
        'score'              => 0,
        'max_score'          => 1,
        'percent'            => 0,
        'passed'             => false,
        'needs_grading'      => false,
        'answers'            => ['0' => 1], // mauvais choix
        'questions_snapshot' => [
            ['type' => 'qcm', 'question' => 'Capitale ?', 'choices' => ['Bonne', 'Mauvaise'], 'correct' => 0, 'points' => 1],
        ],
        'submitted_at'       => now(),
    ]);

    $html = $this->actingAs($student)
        ->get(fix5LessonUrl($course, $scaffold['lesson']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Révision de vos réponses');
    expect($html)->not->toContain('Bonne réponse :');
    // La justesse reste affichée (show_correctness défaut true).
    expect($html)->toContain('À revoir');
})->skip(fn () => ! \Illuminate\Support\Facades\Route::has('academy.lessons.show'), 'route lessons.show absente');

// ─────────────────────────────────────────────────────────────────────────────
// C2 - Complétion : score cohérent à la correction (jamais 0 si l'essai vaut des points)
// ─────────────────────────────────────────────────────────────────────────────

test('C2 : un quiz 100 % essai corrigé au maximum pose une complétion de score > 0', function (): void {
    $course   = fix5Course('cours-c2');
    $scaffold = fix5Quiz($course, ['passing_score' => 60]);
    $owner    = fix5Owner($course);
    $student  = fix5Student($course);

    // Quiz 100 % essai (1 seule question rédigée, 4 pts). Auto = 0 → la complétion
    // recevait historiquement score=0 même corrigée au maximum (incohérence C2).
    $round = [
        ['type' => 'essai', 'question' => 'Rédigez votre réponse.', 'grader_info' => 'Critères.', 'points' => 4],
    ];

    $attempt = QuizAttempt::create([
        'user_id'            => $student->id,
        'lesson_item_id'     => $scaffold['item']->id,
        'course_id'          => $course->id,
        'score'              => 0,
        'max_score'          => 1, // 1 question
        'percent'            => 0,
        'passed'             => false,
        'needs_grading'      => true,
        'answers'            => ['0' => 'Ma réponse rédigée'],
        'questions_snapshot' => $round,
        'submitted_at'       => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(EssayGrading::class, ['course' => $course])
        ->call('startGrading', $attempt->id)
        ->set('essayScores.0', '4') // note maximale
        ->call('gradeAttempt')
        ->assertHasNoErrors();

    $attempt->refresh();
    expect($attempt->needs_grading)->toBeFalse();
    expect($attempt->percent)->toBe(100);
    expect($attempt->passed)->toBeTrue();
    // C2 : la colonne score = nb de questions réussies (1), JAMAIS 0.
    expect($attempt->score)->toBe(1);

    // Complétion posée avec le MÊME score cohérent (> 0).
    $completion = Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $scaffold['item']->id)
        ->where('status', 'completed')
        ->first();
    expect($completion)->not->toBeNull();
    expect((int) $completion->score)->toBe(1);
});

test('C2 : un essai noté PARTIELLEMENT ne compte pas comme « sans faute »', function (): void {
    $course   = fix5Course('cours-c2-partiel');
    $scaffold = fix5Quiz($course, ['passing_score' => 40]);
    $owner    = fix5Owner($course);
    $student  = fix5Student($course);

    // 1 qcm (1 pt) + 1 essai (4 pts). qcm bon, essai noté 2/4 (partiel).
    $attempt = QuizAttempt::create([
        'user_id'            => $student->id,
        'lesson_item_id'     => $scaffold['item']->id,
        'course_id'          => $course->id,
        'score'              => 1,
        'max_score'          => 2,
        'percent'            => 20,
        'passed'             => false,
        'needs_grading'      => true,
        'answers'            => ['0' => 0, '1' => 'Réponse moyenne'],
        'questions_snapshot' => fix5MixedRound(),
        'submitted_at'       => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(EssayGrading::class, ['course' => $course])
        ->call('startGrading', $attempt->id)
        ->set('essayScores.1', '2') // partiel (sur 4)
        ->call('gradeAttempt')
        ->assertHasNoErrors();

    $attempt->refresh();
    // (1 + 2) / 5 = 60 %.
    expect($attempt->percent)->toBe(60);
    // qcm correct = 1 bonne réponse ; l'essai partiel n'en ajoute PAS (pas « sans faute »).
    expect($attempt->score)->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// C4 - Borne de longueur de la réponse d'essai
// ─────────────────────────────────────────────────────────────────────────────

test('C4 : une réponse d’essai > 50 000 caractères est rejetée proprement', function (): void {
    $course   = fix5Course('cours-c4');
    $scaffold = fix5Quiz($course);
    $student  = fix5Student($course);
    $item     = $scaffold['item'];

    $tooLong = str_repeat('a', 50001);

    $response = $this->actingAs($student)
        ->withSession([
            "academy.quiz.{$item->id}" => [
                'questions'  => fix5MixedRound(),
                'started_at' => now()->toIso8601String(),
            ],
        ])
        ->post(route('academy.quiz.submit', [$course->slug, $scaffold['lesson']->id, $item->id]), [
            'answers' => ['0' => 0, '1' => $tooLong],
        ]);

    $response->assertRedirect();
    $response->assertSessionHas('error');

    // Aucune tentative créée (rejet AVANT la persistance).
    expect(QuizAttempt::where('user_id', $student->id)->count())->toBe(0);
});

test('C4 : une réponse d’essai de longueur normale est acceptée (rétrocompat)', function (): void {
    $course   = fix5Course('cours-c4-ok');
    $scaffold = fix5Quiz($course);
    $student  = fix5Student($course);
    $item     = $scaffold['item'];

    $this->actingAs($student)
        ->withSession([
            "academy.quiz.{$item->id}" => [
                'questions'  => fix5MixedRound(),
                'started_at' => now()->toIso8601String(),
            ],
        ])
        ->post(route('academy.quiz.submit', [$course->slug, $scaffold['lesson']->id, $item->id]), [
            'answers' => ['0' => 0, '1' => 'Une réponse rédigée normale.'],
        ])
        ->assertRedirect();

    expect(QuizAttempt::where('user_id', $student->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// RÉTROCOMPAT - quiz auto-noté (sans essai) : résultat persistant inchangé
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : un quiz auto-noté affiche son résultat persistant (note + révision)', function (): void {
    $course   = fix5Course('cours-retro');
    $scaffold = fix5Quiz($course, ['passing_score' => 60]);
    $student  = fix5Student($course);

    QuizAttempt::create([
        'user_id'            => $student->id,
        'lesson_item_id'     => $scaffold['item']->id,
        'course_id'          => $course->id,
        'score'              => 1,
        'max_score'          => 1,
        'percent'            => 100,
        'passed'             => true,
        'needs_grading'      => false,
        'answers'            => ['0' => 0],
        'questions_snapshot' => [
            ['type' => 'qcm', 'question' => 'Capitale ?', 'choices' => ['Bonne', 'Mauvaise'], 'correct' => 0, 'points' => 1],
        ],
        'submitted_at'       => now(),
    ]);

    $html = $this->actingAs($student)
        ->get(fix5LessonUrl($course, $scaffold['lesson']))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('Quiz Réussi');
    expect($html)->toContain('Révision de vos réponses');
    expect($html)->not->toContain('en attente de correction');
})->skip(fn () => ! \Illuminate\Support\Facades\Route::has('academy.lessons.show'), 'route lessons.show absente');
