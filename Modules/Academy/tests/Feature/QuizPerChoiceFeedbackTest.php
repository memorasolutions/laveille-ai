<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — V1-a COUCHE 1 : feedback PAR CHOIX (mcq / vrai-faux).
 *
 * Prouve que :
 *  - le choix_feedback saisi dans la banque est SNAPSHOTÉ dans la QuizAttempt
 *    (via mapToRoundItem → round → questions_snapshot) ;
 *  - après soumission, le feedback du choix CHOISI est disponible dans la révision,
 *    pas celui des autres choix ;
 *  - le general_feedback (= explanation) est aussi recopié dans le round.
 *
 * Autonome : helpers préfixés v1a (aucune redéclaration). SKIPPED si Academy off.
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
// Helpers v1a (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v1aCourse(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V1-a',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v1aLesson(Course $course): Lesson
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

function v1aOwner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    return $owner;
}

function v1aCategory(User $owner, string $name = 'Banque V1-a'): QuestionCategory
{
    return QuestionCategory::create([
        'owner_id'  => $owner->id,
        'parent_id' => null,
        'name'      => $name,
        'position'  => 0,
    ]);
}

function v1aQuizItem(Lesson $lesson, array $payload): LessonItem
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

function v1aStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

function v1aEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v1aStartUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start";
}

function v1aSubmitUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit";
}

// ─────────────────────────────────────────────────────────────────────────────
// COUCHE 1 : feedback par choix snapshoté + spécifique au choix sélectionné
// ─────────────────────────────────────────────────────────────────────────────

test('le feedback du choix CHOISI est snapshoté (pas celui des autres)', function (): void {
    $course = v1aCourse('cours-perchoice');
    $lesson = v1aLesson($course);
    $owner  = v1aOwner($course);
    $cat    = v1aCategory($owner);

    // Question mcq : choix 0 = correct, feedback distinct par choix.
    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'mcq',
        'prompt'      => 'Quelle est la bonne réponse ?',
        'payload'     => [
            'choices'         => ['Bonne', 'Mauvaise A', 'Mauvaise B'],
            'correct'         => 0,
            'choice_feedback' => [
                0 => 'Bravo, choix correct.',
                1 => 'Non, A est un piège.',
                2 => 'Non, B est hors sujet.',
            ],
        ],
        'explanation' => 'Explication générale de la question.',
        'difficulty'  => 'facile',
        'is_active'   => true,
    ]);

    $item    = v1aQuizItem($lesson, ['question_bank' => ['category_id' => $cat->id, 'draw_count' => 1], 'passing_score' => 60]);
    $student = v1aStudent();
    v1aEnroll($course, $student);

    $this->actingAs($student)->post(v1aStartUrl($course, $lesson, $item));

    // L'apprenant choisit le mauvais choix B (index 2).
    $this->actingAs($student)
        ->post(v1aSubmitUrl($course, $lesson, $item), ['answers' => ['0' => 2]])
        ->assertRedirect();

    $attempt  = QuizAttempt::where('user_id', $student->id)->where('lesson_item_id', $item->id)->first();
    $snapshot = $attempt->questions_snapshot[0];

    // Le feedback par choix est intégralement snapshoté.
    expect($snapshot['choice_feedback'])->toBe([
        0 => 'Bravo, choix correct.',
        1 => 'Non, A est un piège.',
        2 => 'Non, B est hors sujet.',
    ]);

    // Le general_feedback (= explanation) est recopié.
    expect($snapshot['general_feedback'])->toBe('Explication générale de la question.');

    // Le feedback du choix sélectionné (index 2) est récupérable, pas confondu.
    $givenIdx = (int) $attempt->answers['0'];
    expect($snapshot['choice_feedback'][$givenIdx])->toBe('Non, B est hors sujet.');
    expect($snapshot['choice_feedback'][$givenIdx])->not->toBe('Bravo, choix correct.');
});

test('une question SANS choice_feedback n’ajoute aucune clé parasite (rétrocompat)', function (): void {
    $course = v1aCourse('cours-nofb');
    $lesson = v1aLesson($course);
    $owner  = v1aOwner($course);
    $cat    = v1aCategory($owner);

    Question::create([
        'category_id' => $cat->id,
        'owner_id'    => $owner->id,
        'type'        => 'mcq',
        'prompt'      => 'Sans feedback par choix ?',
        'payload'     => ['choices' => ['Oui', 'Non'], 'correct' => 0],
        'explanation' => null,
        'difficulty'  => 'facile',
        'is_active'   => true,
    ]);

    $item    = v1aQuizItem($lesson, ['question_bank' => ['category_id' => $cat->id, 'draw_count' => 1]]);
    $student = v1aStudent();
    v1aEnroll($course, $student);

    $this->actingAs($student)->post(v1aStartUrl($course, $lesson, $item));
    $this->actingAs($student)->post(v1aSubmitUrl($course, $lesson, $item), ['answers' => ['0' => 0]]);

    $attempt  = QuizAttempt::where('user_id', $student->id)->where('lesson_item_id', $item->id)->first();
    $snapshot = $attempt->questions_snapshot[0];

    // choice_feedback = [] (aucune entrée), general_feedback = '' (pas d'explanation).
    expect($snapshot['choice_feedback'])->toBe([]);
    expect($snapshot['general_feedback'])->toBe('');
});
