<?php

declare(strict_types=1);

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\StudentRiskBanner;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Progress;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\RiskScoreService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    config()->set('academy.predictive_analytics_enabled', true);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─── Helpers préfixés rss (pas de collision avec les helpers d1 existants) ──

function rssMakeCourse(string $slug, string $title = 'Cours RSS'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'draft',
        'currency'    => 'CAD',
    ]);
}

function rssMakeStudent(): User
{
    $user = User::factory()->create();
    $user->assignRole('student');

    return $user;
}

function rssEnroll(Course $course, User $user, ?Carbon $when = null): Enrollment
{
    return Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => $when ?? now(),
    ]);
}

function rssCreateProgress(User $user, Course $course, int $percent, ?Carbon $lastActivity = null): Progress
{
    return Progress::create([
        'user_id'            => $user->id,
        'course_id'          => $course->id,
        'percent'            => $percent,
        'last_activity_at'   => $lastActivity ?? now(),
        'required_total'     => 10,
        'required_completed' => (int) round($percent / 10),
    ]);
}

/**
 * Crée une tentative de quiz. Nécessite un LessonItem réel (FK).
 * On crée un chapitre + leçon + item minimaux si besoin.
 */
function rssCreateQuizAttempt(User $user, Course $course, bool $passed, int $lessonItemId): QuizAttempt
{
    return QuizAttempt::create([
        'user_id'        => $user->id,
        'course_id'      => $course->id,
        'lesson_item_id' => $lessonItemId,
        'score'          => $passed ? 10 : 0,
        'max_score'      => 10,
        'percent'        => $passed ? 100 : 0,
        'passed'         => $passed,
        'timed_out'      => false,
        'needs_grading'  => false,
        'answers'        => [],
        'submitted_at'   => now(),
    ]);
}

/**
 * Crée un LessonItem minimal dans le cours (pour les FK de QuizAttempt).
 */
function rssMakeQuizItem(Course $course): LessonItem
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre RSS',
        'position'  => 1,
    ]);

    $lesson = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon RSS',
        'slug'       => 'lecon-rss-' . $course->id,
        'position'   => 1,
    ]);

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz RSS',
        'position'    => 1,
        'is_required' => true,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Apprenant à 100 % → score 0, risque faible, raisons vides
// ─────────────────────────────────────────────────────────────────────────────

test('apprenant à 100% de progression → score 0, level faible, pas de raisons', function (): void {
    $course  = rssMakeCourse('rss-100pct');
    $student = rssMakeStudent();
    rssEnroll($course, $student, now()->subDays(30));
    rssCreateProgress($student, $course, 100, now()->subDays(2));

    $service = app(RiskScoreService::class);
    $result  = $service->scoreForEnrollee($student->id, $course);

    expect($result['score'])->toBe(0);
    expect($result['level'])->toBe('faible');
    expect($result['reasons'])->toBeEmpty();
    expect($result['details']['percent'])->toBe(100);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Apprenant inactif (20 j) + 3 quiz échoués → risque élevé
// ─────────────────────────────────────────────────────────────────────────────

test('apprenant inactif 20 jours + 3 quiz échoués → risque élevé', function (): void {
    $course  = rssMakeCourse('rss-high-risk');
    $student = rssMakeStudent();
    rssEnroll($course, $student, now()->subDays(20));
    rssCreateProgress($student, $course, 20, now()->subDays(20));

    $item = rssMakeQuizItem($course);
    rssCreateQuizAttempt($student, $course, false, $item->id);
    rssCreateQuizAttempt($student, $course, false, $item->id);
    rssCreateQuizAttempt($student, $course, false, $item->id);

    $service = app(RiskScoreService::class);
    $result  = $service->scoreForEnrollee($student->id, $course);

    expect($result['score'])->toBeGreaterThan(66);
    expect($result['level'])->toBe('élevé');
    expect($result['reasons'])->not()->toBeEmpty();
    expect($result['details']['days_inactive'])->toBeGreaterThanOrEqual(20);
    expect($result['details']['consecutive_fails'])->toBe(3);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Apprenant actif (3 j) avec 30 % → risque faible
// ─────────────────────────────────────────────────────────────────────────────

test('apprenant actif depuis 3 jours avec 30% de progression → risque faible', function (): void {
    $course  = rssMakeCourse('rss-low-risk');
    $student = rssMakeStudent();
    rssEnroll($course, $student, now()->subDays(3));
    rssCreateProgress($student, $course, 30, now());

    $service = app(RiskScoreService::class);
    $result  = $service->scoreForEnrollee($student->id, $course);

    expect($result['score'])->toBeLessThanOrEqual(33);
    expect($result['level'])->toBe('faible');
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Gating Livewire : un étudiant ne voit PAS le risque d'un autre cours
// ─────────────────────────────────────────────────────────────────────────────

test('gating : un étudiant non inscrit à un cours obtient riskData null', function (): void {
    $courseA = rssMakeCourse('rss-gate-a');
    $courseB = rssMakeCourse('rss-gate-b');

    $studentA = rssMakeStudent();
    rssEnroll($courseA, $studentA);

    // studentA est inscrit à courseA seulement.
    // StudentRiskBanner::mount() pour courseA → OK.
    Livewire::actingAs($studentA)
        ->test(StudentRiskBanner::class, ['course' => $courseA])
        ->assertOk();

    // StudentRiskBanner::mount() pour courseB → 403 (non inscrit).
    Livewire::actingAs($studentA)
        ->test(StudentRiskBanner::class, ['course' => $courseB])
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. scoresForCourse : tous les inscrits apparaissent, triés score décroissant
// ─────────────────────────────────────────────────────────────────────────────

test('scoresForCourse retourne tous les inscrits, trié par score décroissant', function (): void {
    $course = rssMakeCourse('rss-scores-course');

    // Étudiant inactif (risque haut).
    $inactive = rssMakeStudent();
    rssEnroll($course, $inactive, now()->subDays(20));
    rssCreateProgress($inactive, $course, 20, now()->subDays(20));
    $item = rssMakeQuizItem($course);
    rssCreateQuizAttempt($inactive, $course, false, $item->id);
    rssCreateQuizAttempt($inactive, $course, false, $item->id);
    rssCreateQuizAttempt($inactive, $course, false, $item->id);

    // Étudiant actif (risque faible).
    $active = rssMakeStudent();
    rssEnroll($course, $active, now()->subDays(3));
    rssCreateProgress($active, $course, 30, now());

    $service = app(RiskScoreService::class);
    $results = $service->scoresForCourse($course);

    expect($results)->toHaveCount(2);

    // Premier = l'inactif (score le plus haut).
    expect($results[0]['user_id'])->toBe($inactive->id);
    expect($results[0]['score'])->toBeGreaterThan($results[1]['score']);

    // Champs obligatoires présents.
    expect($results[0])->toHaveKeys(['user_id', 'name', 'email', 'score', 'level', 'reasons', 'details']);
    expect($results[1])->toHaveKeys(['user_id', 'name', 'email', 'score', 'level', 'reasons', 'details']);
});
