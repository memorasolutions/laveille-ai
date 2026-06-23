<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - correctifs d'AUDIT de l'activité Choice (V4-a), portée module quiz/choice :
 *  - C1 : QuizController ET ChoiceController utilisent le trait AuthorizesAcademyAccess.
 *         La sécurité (non-inscrit 403, anti-IDOR 404, anonyme rejeté) reste IDENTIQUE
 *         sur les DEUX surfaces (quiz submit + choice vote).
 *  - C2 : throttle:20,1 présent sur les routes POST de mutation étudiant ; un excès
 *         de votes renvoie 429.
 *  - C3 : tally() compte correctement (pas de régression) ; preloadUserVotes charge en
 *         UNE requête les votes d'une leçon multi-choice (pas de N+1 linéaire).
 *
 * Autonome : helpers préfixés fix4a (aucune redéclaration). SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Academy\Http\Controllers\ChoiceController;
use Modules\Academy\Http\Controllers\Concerns\AuthorizesAcademyAccess;
use Modules\Academy\Http\Controllers\QuizController;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\ChoiceResponse;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ChoiceService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers fix4a (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function fix4aCourse(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours fix4a',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function fix4aLesson(Course $course): Lesson
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

function fix4aChoiceItem(Lesson $lesson, array $payload = [], int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'choice',
        'title'       => 'Sondage '.$position,
        'position'    => $position,
        'payload'     => array_merge([
            'question' => 'Quelle est votre préférence ?',
            'options'  => ['Option A', 'Option B', 'Option C'],
        ], $payload),
        'is_required' => false,
    ]);
}

function fix4aQuizItem(Lesson $lesson): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz',
        'position'    => 2,
        'payload'     => ['questions' => []],
        'is_required' => false,
    ]);
}

function fix4aStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function fix4aEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function fix4aVoteUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/choice/vote";
}

function fix4aSubmitUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit";
}

// ─────────────────────────────────────────────────────────────────────────────
// C1 — Trait partagé : présence + sécurité identique sur quiz ET choice
// ─────────────────────────────────────────────────────────────────────────────

test('C1 : QuizController ET ChoiceController utilisent le trait AuthorizesAcademyAccess', function (): void {
    expect(class_uses_recursive(QuizController::class))->toContain(AuthorizesAcademyAccess::class);
    expect(class_uses_recursive(ChoiceController::class))->toContain(AuthorizesAcademyAccess::class);
});

test('C1 : un étudiant NON inscrit est rejeté (403) sur choice ET quiz', function (): void {
    $course = fix4aCourse('fix4a-noenroll');
    $lesson = fix4aLesson($course);
    $choice = fix4aChoiceItem($lesson);
    $quiz   = fix4aQuizItem($lesson);

    $student = fix4aStudent(); // jamais inscrit

    $this->actingAs($student)
        ->post(fix4aVoteUrl($course, $lesson, $choice), ['choice' => 0])
        ->assertForbidden();

    $this->actingAs($student)
        ->post(fix4aSubmitUrl($course, $lesson, $quiz), ['answers' => []])
        ->assertForbidden();

    expect(ChoiceResponse::where('lesson_item_id', $choice->id)->count())->toBe(0);
});

test('C1 : anti-IDOR (item d\'un AUTRE cours) renvoie 404 sur choice ET quiz', function (): void {
    $courseA = fix4aCourse('fix4a-idor-a');
    $lessonA = fix4aLesson($courseA);

    $courseB = fix4aCourse('fix4a-idor-b');
    $lessonB = fix4aLesson($courseB);
    $choiceB = fix4aChoiceItem($lessonB);
    $quizB   = fix4aQuizItem($lessonB);

    $student = fix4aStudent();
    fix4aEnroll($courseA, $student); // inscrit à A seulement

    // Item de B atteint via les params de A → l'appartenance échoue (anti-IDOR).
    $this->actingAs($student)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$choiceB->id}/choice/vote", ['choice' => 0])
        ->assertNotFound();

    $this->actingAs($student)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$quizB->id}/quiz/submit", ['answers' => []])
        ->assertNotFound();

    expect(ChoiceResponse::where('lesson_item_id', $choiceB->id)->count())->toBe(0);
});

test('C1 : un visiteur anonyme est rejeté (redirigé) sur choice ET quiz', function (): void {
    $course = fix4aCourse('fix4a-anon');
    $lesson = fix4aLesson($course);
    $choice = fix4aChoiceItem($lesson);
    $quiz   = fix4aQuizItem($lesson);

    $this->post(fix4aVoteUrl($course, $lesson, $choice), ['choice' => 0])->assertRedirect();
    $this->post(fix4aSubmitUrl($course, $lesson, $quiz), ['answers' => []])->assertRedirect();

    expect(ChoiceResponse::where('lesson_item_id', $choice->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// C2 — throttle:20,1 sur les POST de mutation étudiant
// ─────────────────────────────────────────────────────────────────────────────

test('C2 : les routes POST de mutation portent le middleware throttle:20,1', function (): void {
    $router = app('router');

    foreach (['academy.choice.vote', 'academy.quiz.submit', 'academy.quiz.verify', 'academy.lessons.complete', 'academy.courses.enroll'] as $name) {
        $route = $router->getRoutes()->getByName($name);
        expect($route)->not->toBeNull();
        expect($route->gatherMiddleware())->toContain('throttle:20,1');
    }
});

test('C2 : un excès de votes (> 20/min) renvoie 429', function (): void {
    $course  = fix4aCourse('fix4a-throttle');
    $lesson  = fix4aLesson($course);
    $item    = fix4aChoiceItem($lesson);
    $student = fix4aStudent();
    fix4aEnroll($course, $student);

    $this->actingAs($student);

    // Les 20 premières passent (redirect), la 21e est throttlée (429).
    for ($i = 0; $i < 20; $i++) {
        $this->post(fix4aVoteUrl($course, $lesson, $item), ['choice' => 0])->assertRedirect();
    }

    $this->post(fix4aVoteUrl($course, $lesson, $item), ['choice' => 0])->assertStatus(429);
});

// ─────────────────────────────────────────────────────────────────────────────
// C3 — tally correct + preloadUserVotes (anti N+1)
// ─────────────────────────────────────────────────────────────────────────────

test('C3 : tally() compte correctement (votants + par option), aucune régression', function (): void {
    $course = fix4aCourse('fix4a-tally');
    $lesson = fix4aLesson($course);
    $item   = fix4aChoiceItem($lesson, ['allow_multiple' => true]);

    // 3 votants : A→[0], B→[0,1], C→[2]. Option0=2, option1=1, option2=1, voters=3.
    foreach ([[0], [0, 1], [2]] as $choices) {
        $u = fix4aStudent();
        ChoiceResponse::create(['lesson_item_id' => $item->id, 'user_id' => $u->id, 'choices' => $choices]);
    }

    $tally = ChoiceService::tally($item);

    expect($tally['total_voters'])->toBe(3);
    expect($tally['options'][0]['count'])->toBe(2);
    expect($tally['options'][1]['count'])->toBe(1);
    expect($tally['options'][2]['count'])->toBe(1);
    expect($tally['options'][0]['percent'])->toBe(67); // round(2/3*100)
});

test('C3 : preloadUserVotes charge les votes d\'une leçon multi-choice en UNE requête', function (): void {
    $course  = fix4aCourse('fix4a-n1');
    $lesson  = fix4aLesson($course);
    $student = fix4aStudent();

    // 5 items « choice » dans la même leçon, l'étudiant a voté à chacun.
    $items = [];
    for ($i = 0; $i < 5; $i++) {
        $it = fix4aChoiceItem($lesson, [], $i + 1);
        ChoiceResponse::create(['lesson_item_id' => $it->id, 'user_id' => $student->id, 'choices' => [$i % 3]]);
        $items[] = $it;
    }

    // UNE seule requête pour précharger les 5 votes (pas 5).
    DB::enableQueryLog();
    $preloaded = ChoiceService::preloadUserVotes(collect($items)->pluck('id'), $student);
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queryCount)->toBe(1);
    expect($preloaded)->toHaveCount(5);

    // userVote consulte la map préchargée SANS aucune requête supplémentaire.
    // (flush : getQueryLog() est cumulatif, on repart de zéro pour ne mesurer que la lecture).
    DB::flushQueryLog();
    DB::enableQueryLog();
    foreach ($items as $idx => $it) {
        expect(ChoiceService::userVote($it, $student, $preloaded))->toBe([$idx % 3]);
    }
    $readQueries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($readQueries)->toBe(0); // zéro requête : la map fait autorité (anti N+1)
});

test('C3 : userVote sans préchargement reste fonctionnel (rétrocompat)', function (): void {
    $course  = fix4aCourse('fix4a-uv');
    $lesson  = fix4aLesson($course);
    $item    = fix4aChoiceItem($lesson);
    $student = fix4aStudent();

    expect(ChoiceService::userVote($item, $student))->toBeNull();

    ChoiceResponse::create(['lesson_item_id' => $item->id, 'user_id' => $student->id, 'choices' => [1]]);

    expect(ChoiceService::userVote($item, $student))->toBe([1]);
});
