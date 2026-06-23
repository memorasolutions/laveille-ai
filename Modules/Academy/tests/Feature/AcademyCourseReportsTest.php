<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F23 : Rapports et journaux par cours (CourseReports +
 * CourseReportService).
 *
 * Prouve :
 *  - PARTICIPATION agrège correctement (progression, items complétés/total,
 *    dernière activité, statut, note finale pondérée) ;
 *  - JOURNAL liste les évènements (consultation, complétion, tentative de quiz,
 *    remise) dans l'ordre chronologique + filtres (étudiant, type) ;
 *  - SÉCURITÉ (OWASP A01) : un non-gérant (étudiant/lambda) → 403 ; anti-IDOR :
 *    un formateur du cours A ne voit pas le cours B (slug/ID forgé) ;
 *  - rétrocompat : un cours SANS inscrit/évènement ne plante pas (états vides).
 *
 * Helpers préfixés `f23` (aucune redéclaration avec les autres fichiers).
 * Garde-fou : module Academy désactivé → tous les tests SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseReports;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Models\Submission;
use Modules\Academy\Services\CompletionService;
use Modules\Academy\Services\CourseReportService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Helper : crée un cours gratuit en brouillon minimal. */
function f23MakeCourse(string $slug, string $title = 'Cours'): Course
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

/** Helper : ajoute une leçon avec N items REQUIS. Retourne la Lesson + items. */
function f23AddLesson(Course $course, string $title, int $requiredItems, int $position = 1): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre '.$title,
        'position'  => $position,
    ]);

    $lesson = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => $title,
        'slug'       => \Illuminate\Support\Str::slug($title).'-'.$position,
        'position'   => $position,
    ]);

    for ($i = 1; $i <= $requiredItems; $i++) {
        LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => 'document',
            'title'       => $title.' item '.$i,
            'position'    => $i,
            'is_required' => true,
        ]);
    }

    return $lesson->load('lessonItems');
}

function f23MakeOwner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'owner']);

    return $user;
}

function f23MakeStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

function f23Enroll(Course $course, User $user, ?\Illuminate\Support\Carbon $when = null): Enrollment
{
    return Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => $when ?? now(),
    ]);
}

function f23CompleteLessonItems(User $user, Lesson $lesson, int $count): void
{
    $items = $lesson->lessonItems()->orderBy('position')->get()->take($count);
    foreach ($items as $item) {
        CompletionService::markComplete($user, $item);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. PARTICIPATION : agrégations exactes
// ─────────────────────────────────────────────────────────────────────────────

test('le rapport de participation agrège progression, items, statut et dernière activité', function (): void {
    $course = f23MakeCourse('f23-part');
    $l1 = f23AddLesson($course, 'Leçon 1', 2, 1);
    $l2 = f23AddLesson($course, 'Leçon 2', 2, 2); // 4 items requis au total

    $complete = f23MakeStudent();  // 100 %
    $partial  = f23MakeStudent();  // 50 %
    $idle     = f23MakeStudent();  // 0 %, jamais commencé

    f23Enroll($course, $complete, now()->subDays(3));
    f23Enroll($course, $partial, now()->subDays(5));
    f23Enroll($course, $idle, now()->subDays(20));

    f23CompleteLessonItems($complete, $l1, 2);
    f23CompleteLessonItems($complete, $l2, 2);
    f23CompleteLessonItems($partial, $l1, 2);

    $rows = app(CourseReportService::class)->participation($course)->keyBy('user_id');

    expect($rows)->toHaveCount(3);

    $a = $rows[$complete->id];
    expect($a['percent'])->toBe(100);
    expect($a['items_completed'])->toBe(4);
    expect($a['items_total'])->toBe(4);
    expect($a['status_key'])->toBe('completed');
    expect($a['last_activity'])->not->toBeNull();

    $b = $rows[$partial->id];
    expect($b['percent'])->toBe(50);
    expect($b['items_completed'])->toBe(2);
    expect($b['status_key'])->toBe('in_progress');
    expect($b['last_activity'])->not->toBeNull();

    $c = $rows[$idle->id];
    expect($c['percent'])->toBe(0);
    expect($c['items_completed'])->toBe(0);
    expect($c['status_key'])->toBe('never_started');
    expect($c['last_activity'])->toBeNull();
});

test('la dernière activité du rapport tient compte des tentatives de quiz', function (): void {
    $course = f23MakeCourse('f23-lastact');
    f23AddLesson($course, 'Leçon 1', 1, 1);

    $student = f23MakeStudent();
    f23Enroll($course, $student, now()->subDays(10));

    $quizItem = LessonItem::where('lesson_id', $course->chapters()->first()->lessons()->first()->id)->first();

    $when = now()->subDay();
    QuizAttempt::create([
        'user_id'        => $student->id,
        'lesson_item_id' => $quizItem->id,
        'course_id'      => $course->id,
        'score'          => 80,
        'max_score'      => 100,
        'percent'        => 80,
        'passed'         => true,
        'answers'        => [],
        'submitted_at'   => $when,
    ]);

    $row = app(CourseReportService::class)->participation($course)->firstWhere('user_id', $student->id);

    expect($row['last_activity'])->not->toBeNull();
    expect($row['last_activity']->format('Y-m-d H:i'))->toBe($when->format('Y-m-d H:i'));
    // A une activité (tentative) → n'est plus « jamais commencé ».
    expect($row['status_key'])->toBe('in_progress');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. JOURNAL : liste + filtres
// ─────────────────────────────────────────────────────────────────────────────

test('le journal liste les évènements (complétion, quiz, remise) avec filtres', function (): void {
    $course = f23MakeCourse('f23-log');
    $l1 = f23AddLesson($course, 'Leçon 1', 1, 1);

    $student = f23MakeStudent();
    f23Enroll($course, $student);

    // Complétion (item_completed) + consultation (item_viewed) via le service réel.
    f23CompleteLessonItems($student, $l1, 1);

    // Tentative de quiz.
    $item = $l1->lessonItems()->first();
    QuizAttempt::create([
        'user_id'        => $student->id,
        'lesson_item_id' => $item->id,
        'course_id'      => $course->id,
        'score'          => 90, 'max_score' => 100, 'percent' => 90,
        'passed'         => true, 'answers' => [], 'submitted_at' => now(),
    ]);

    // Remise de devoir.
    $assignment = Assignment::create([
        'course_id'    => $course->id,
        'title'        => 'Devoir 1',
        'max_points'   => 100,
        'is_published' => true,
        'position'     => 1,
    ]);
    Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'Ma remise',
        'submitted_at'  => now(),
    ]);

    $service = app(CourseReportService::class);

    $all = $service->activityLog($course);
    $types = $all->pluck('type')->unique()->values()->all();
    expect($types)->toContain('item_completed');
    expect($types)->toContain('quiz_attempt');
    expect($types)->toContain('submission');

    // Filtre par type.
    $onlyQuiz = $service->activityLog($course, ['type' => 'quiz_attempt']);
    expect($onlyQuiz)->not->toBeEmpty();
    expect($onlyQuiz->pluck('type')->unique()->all())->toBe(['quiz_attempt']);

    // Filtre par étudiant (un autre étudiant n'a aucun évènement).
    $other = f23MakeStudent();
    f23Enroll($course, $other);
    $forOther = $service->activityLog($course, ['user_id' => $other->id]);
    expect($forOther)->toBeEmpty();
});

test('le journal est trié du plus récent au plus ancien', function (): void {
    $course = f23MakeCourse('f23-order');
    $l1 = f23AddLesson($course, 'Leçon 1', 1, 1);
    $item = $l1->lessonItems()->first();
    $student = f23MakeStudent();
    f23Enroll($course, $student);

    QuizAttempt::create([
        'user_id' => $student->id, 'lesson_item_id' => $item->id, 'course_id' => $course->id,
        'score' => 50, 'max_score' => 100, 'percent' => 50, 'passed' => false,
        'answers' => [], 'submitted_at' => now()->subDays(3),
    ]);
    QuizAttempt::create([
        'user_id' => $student->id, 'lesson_item_id' => $item->id, 'course_id' => $course->id,
        'score' => 70, 'max_score' => 100, 'percent' => 70, 'passed' => true,
        'answers' => [], 'submitted_at' => now()->subDay(),
    ]);

    $log = app(CourseReportService::class)->activityLog($course, ['type' => 'quiz_attempt']);

    expect($log)->toHaveCount(2);
    expect($log[0]['at']->gte($log[1]['at']))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. SÉCURITÉ : autorisation + anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('un étudiant ne peut pas accéder aux rapports (403)', function (): void {
    $course = f23MakeCourse('f23-sec-student');
    $student = f23MakeStudent();
    f23Enroll($course, $student);

    $this->actingAs($student);

    Livewire::test(CourseReports::class, ['course' => $course])
        ->assertForbidden();
});

test('un utilisateur lambda ne peut pas accéder aux rapports (403)', function (): void {
    $course = f23MakeCourse('f23-sec-lambda');
    $lambda = User::factory()->create();

    $this->actingAs($lambda);

    Livewire::test(CourseReports::class, ['course' => $course])
        ->assertForbidden();
});

test('un formateur ne voit que SON cours (anti-IDOR sur cours forgé)', function (): void {
    $courseA = f23MakeCourse('f23-a');
    $courseB = f23MakeCourse('f23-b');

    $ownerA = f23MakeOwner($courseA); // formateur du cours A uniquement

    $this->actingAs($ownerA);

    // Le formateur de A accède à A...
    Livewire::test(CourseReports::class, ['course' => $courseA])
        ->assertOk();

    // ... mais pas à B (slug/ID d'un autre cours).
    Livewire::test(CourseReports::class, ['course' => $courseB])
        ->assertForbidden();
});

test('le formateur du cours voit le rapport (OK)', function (): void {
    $course = f23MakeCourse('f23-owner-ok');
    $l1 = f23AddLesson($course, 'Leçon 1', 1, 1);
    $student = f23MakeStudent();
    f23Enroll($course, $student);
    f23CompleteLessonItems($student, $l1, 1);

    $owner = f23MakeOwner($course);
    $this->actingAs($owner);

    Livewire::test(CourseReports::class, ['course' => $course])
        ->assertOk()
        ->assertSee($student->name);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. RÉTROCOMPAT : cours vide
// ─────────────────────────────────────────────────────────────────────────────

test('un cours sans inscrit ni évènement ne plante pas (états vides)', function (): void {
    $course = f23MakeCourse('f23-empty');
    $service = app(CourseReportService::class);

    expect($service->participation($course))->toBeEmpty();
    expect($service->activityLog($course))->toBeEmpty();
    expect($service->enrolledUsers($course))->toBeEmpty();

    // CSV : juste l'entête (BOM + ligne de titres), aucune exception.
    $csv = $service->participationCsv($course);
    expect($csv)->toContain('Étudiant');
    expect($csv)->toStartWith("\xEF\xBB\xBF");

    // Composant rendu pour un gérant : OK, états vides affichés.
    $owner = f23MakeOwner($course);
    $this->actingAs($owner);

    Livewire::test(CourseReports::class, ['course' => $course])
        ->assertOk()
        ->assertSee('Aucun étudiant inscrit');
});
