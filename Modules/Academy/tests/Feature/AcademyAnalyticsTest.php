<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - D1 : Analytics par cours (CourseAnalytics + AnalyticsService).
 *
 * Prouve :
 *  - les KPIs (inscrits, % complétion, progression moyenne) sont EXACTS sur un
 *    cours à N inscrits (certains 100 %, d'autres à mi-parcours) ;
 *  - le « point de décrochage » identifie bien la leçon la moins complétée ;
 *  - SÉCURITÉ (OWASP A01) : un non-gérant (étudiant/lambda) → 403 ; anti-IDOR :
 *    un gérant du cours A ne voit pas les stats du cours B (slug/ID forgé) ;
 *  - un cours SANS inscrit ne plante pas (états vides).
 *
 * Helpers préfixés `d1` (aucune redéclaration avec les autres fichiers).
 * Garde-fou : module Academy désactivé → tous les tests SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseAnalytics;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\AnalyticsService;
use Modules\Academy\Services\CompletionService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Helper : crée un cours gratuit en brouillon minimal. */
function d1MakeCourse(string $slug, string $title = 'Cours'): Course
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

/**
 * Helper : ajoute une leçon au cours (dans un chapitre) avec N items REQUIS.
 * Retourne la Lesson avec ses items chargés.
 */
function d1AddLesson(Course $course, string $title, int $requiredItems, int $position = 1): Lesson
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

/** Helper : owner d'un cours (formateur). */
function d1MakeOwner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'owner']);

    return $user;
}

/** Helper : admin academy.manage. */
function d1MakeAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    if (! $admin->can('academy.manage')) {
        $admin->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'academy.manage', 'guard_name' => 'web'])
        );
    }

    return $admin;
}

/** Helper : étudiant (sans rôle de cours). */
function d1MakeStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

/** Helper : inscrit un user (status='active') à un cours, avec une date d'inscription optionnelle. */
function d1Enroll(Course $course, User $user, ?\Illuminate\Support\Carbon $when = null): Enrollment
{
    return Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => $when ?? now(),
    ]);
}

/**
 * Helper : marque comme complétés les `count` premiers items requis (ordre de
 * création) d'une leçon pour un utilisateur, en passant par CompletionService
 * (qui recalcule la progression — DRY avec la prod).
 */
function d1CompleteLessonItems(User $user, Lesson $lesson, int $count): void
{
    $items = $lesson->lessonItems()->orderBy('position')->get()->take($count);
    foreach ($items as $item) {
        CompletionService::markComplete($user, $item);
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. KPIs exacts (inscrits, % complétion, progression moyenne)
// ─────────────────────────────────────────────────────────────────────────────

test('les KPIs inscrits / complétion / progression moyenne sont exacts', function (): void {
    $course = d1MakeCourse('d1-kpi');

    // 2 leçons, 2 items requis chacune → 4 items requis au total.
    $l1 = d1AddLesson($course, 'Leçon 1', 2, 1);
    $l2 = d1AddLesson($course, 'Leçon 2', 2, 2);

    // 4 inscrits actifs.
    $u1 = d1MakeStudent(); // 100 % (4/4)
    $u2 = d1MakeStudent(); // 50 % (2/4)
    $u3 = d1MakeStudent(); // 0 %
    $u4 = d1MakeStudent(); // 100 % (4/4)

    d1Enroll($course, $u1, now()->subDays(2));   // dans 7 j
    d1Enroll($course, $u2, now()->subDays(10));  // dans 30 j, hors 7 j
    d1Enroll($course, $u3, now()->subDays(40));  // hors 30 j
    d1Enroll($course, $u4, now()->subDays(1));   // dans 7 j

    // Complétions : u1 et u4 terminent tout ; u2 ne fait que la leçon 1.
    d1CompleteLessonItems($u1, $l1, 2);
    d1CompleteLessonItems($u1, $l2, 2);
    d1CompleteLessonItems($u4, $l1, 2);
    d1CompleteLessonItems($u4, $l2, 2);
    d1CompleteLessonItems($u2, $l1, 2);

    $service = app(AnalyticsService::class);

    $enroll = $service->enrollmentKpis($course);
    expect($enroll['total'])->toBe(4);
    expect($enroll['last7'])->toBe(2);   // u1, u4
    expect($enroll['last30'])->toBe(3);  // u1, u2, u4

    $comp = $service->completionKpis($course);
    expect($comp['enrolled'])->toBe(4);
    expect($comp['completed'])->toBe(2); // u1, u4 à 100 %
    expect($comp['rate'])->toBe(50);     // 2/4
    // Moyenne : (100 + 50 + 0 + 100) / 4 = 62.5 → 63 (arrondi).
    expect($comp['avgPercent'])->toBe(63);

    // Certificats émis automatiquement à 100 % (u1 + u4).
    expect($service->certificatesCount($course))->toBe(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Point de décrochage = leçon la moins complétée
// ─────────────────────────────────────────────────────────────────────────────

test('le point de décrochage identifie la leçon la moins complétée', function (): void {
    $course = d1MakeCourse('d1-dropoff');

    $l1 = d1AddLesson($course, 'Intro', 1, 1);
    $l2 = d1AddLesson($course, 'Avancé', 1, 2);

    $u1 = d1MakeStudent();
    $u2 = d1MakeStudent();
    $u3 = d1MakeStudent();

    d1Enroll($course, $u1);
    d1Enroll($course, $u2);
    d1Enroll($course, $u3);

    // Leçon 1 (Intro) : 3/3 la terminent. Leçon 2 (Avancé) : 1/3 seulement.
    d1CompleteLessonItems($u1, $l1, 1);
    d1CompleteLessonItems($u2, $l1, 1);
    d1CompleteLessonItems($u3, $l1, 1);
    d1CompleteLessonItems($u1, $l2, 1);

    $service = app(AnalyticsService::class);
    $dropoff = $service->lessonDropoff($course);

    // 2 leçons remontées, ordonnées.
    expect($dropoff)->toHaveCount(2);
    expect($dropoff[0]['title'])->toBe('Intro');
    expect($dropoff[0]['rate'])->toBe(100);
    expect($dropoff[1]['title'])->toBe('Avancé');
    expect($dropoff[1]['rate'])->toBe(33); // 1/3

    $point = $service->dropoffPoint($course, $dropoff);
    expect($point)->not->toBeNull();
    expect($point['lesson_id'])->toBe($l2->id);
    expect($point['title'])->toBe('Avancé');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Le composant Livewire rend les métriques (owner autorisé)
// ─────────────────────────────────────────────────────────────────────────────

test('owner peut ouvrir le tableau de bord d\'analytics de SON cours', function (): void {
    $course = d1MakeCourse('d1-render');
    $owner = d1MakeOwner($course);

    $l1 = d1AddLesson($course, 'Leçon A', 1, 1);
    $student = d1MakeStudent();
    d1Enroll($course, $student);
    d1CompleteLessonItems($student, $l1, 1);

    Livewire::actingAs($owner)
        ->test(CourseAnalytics::class, ['course' => $course])
        ->assertOk()
        ->assertViewIs('academy::livewire.course-analytics');
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. SÉCURITÉ — non-gérant interdit (étudiant / lambda) → 403
// ─────────────────────────────────────────────────────────────────────────────

test('un étudiant ne peut pas ouvrir les analytics → 403', function (): void {
    $course = d1MakeCourse('d1-sec-student');

    Livewire::actingAs(d1MakeStudent())
        ->test(CourseAnalytics::class, ['course' => $course])
        ->assertForbidden();
});

test('un utilisateur sans aucun rôle ne peut pas ouvrir les analytics → 403', function (): void {
    $course = d1MakeCourse('d1-sec-lambda');

    Livewire::actingAs(User::factory()->create())
        ->test(CourseAnalytics::class, ['course' => $course])
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. ANTI-IDOR — owner de A ne voit pas les stats de B (slug/ID forgé)
// ─────────────────────────────────────────────────────────────────────────────

test('ANTI-IDOR : owner de A ne peut pas ouvrir les analytics du cours B', function (): void {
    $courseA = d1MakeCourse('d1-idor-a');
    $courseB = d1MakeCourse('d1-idor-b');
    $owner = d1MakeOwner($courseA);

    Livewire::actingAs($owner)
        ->test(CourseAnalytics::class, ['course' => $courseB])
        ->assertForbidden();
});

test('ANTI-IDOR : owner de A qui force le courseId vers B se voit refuser les métriques', function (): void {
    $courseA = d1MakeCourse('d1-idor-a2');
    $courseB = d1MakeCourse('d1-idor-b2');
    $owner = d1MakeOwner($courseA);

    // Inscrits sur B uniquement (données qui ne doivent jamais fuiter à l'owner de A).
    d1Enroll($courseB, d1MakeStudent());
    d1Enroll($courseB, d1MakeStudent());

    // Montage légitime sur A, puis forçage du courseId vers B côté « navigateur ».
    // Au re-rendu, metrics() re-résout B et ré-autorise manageEnrollments → 403.
    Livewire::actingAs($owner)
        ->test(CourseAnalytics::class, ['course' => $courseA])
        ->set('courseId', $courseB->id)
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. État vide — un cours sans inscrit ne plante pas
// ─────────────────────────────────────────────────────────────────────────────

test('un cours sans inscrit retourne des KPIs à zéro sans planter', function (): void {
    $course = d1MakeCourse('d1-empty');
    d1AddLesson($course, 'Leçon vide', 1, 1);

    $service = app(AnalyticsService::class);

    expect($service->enrollmentKpis($course))->toBe(['total' => 0, 'last7' => 0, 'last30' => 0]);
    expect($service->completionKpis($course))->toBe(['enrolled' => 0, 'completed' => 0, 'rate' => 0, 'avgPercent' => 0]);

    // Décrochage : 1 leçon requise, 0 inscrit → rate 0, total 0 (pas de division par zéro).
    $dropoff = $service->lessonDropoff($course);
    expect($dropoff)->toHaveCount(1);
    expect($dropoff[0]['total'])->toBe(0);
    expect($dropoff[0]['rate'])->toBe(0);

    $point = $service->dropoffPoint($course, $dropoff);
    expect($point)->not->toBeNull();
    expect($point['rate'])->toBe(0);

    $activity = $service->recentActivity($course);
    expect($activity['completions'])->toHaveCount(0);
    expect($activity['enrollments'])->toHaveCount(0);

    expect($service->certificatesCount($course))->toBe(0);
});

test('le composant rend l\'état vide sans erreur (cours sans inscrit)', function (): void {
    $course = d1MakeCourse('d1-empty-render');
    $owner = d1MakeOwner($course);

    Livewire::actingAs($owner)
        ->test(CourseAnalytics::class, ['course' => $course])
        ->assertOk();
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. Activité récente — scopée au cours, données nominatives
// ─────────────────────────────────────────────────────────────────────────────

test('l\'activité récente est scopée au cours et ne mélange pas deux cours', function (): void {
    $courseA = d1MakeCourse('d1-act-a');
    $courseB = d1MakeCourse('d1-act-b');

    $la = d1AddLesson($courseA, 'A1', 1, 1);
    $lb = d1AddLesson($courseB, 'B1', 1, 1);

    $ua = d1MakeStudent();
    $ub = d1MakeStudent();
    d1Enroll($courseA, $ua);
    d1Enroll($courseB, $ub);
    d1CompleteLessonItems($ua, $la, 1);
    d1CompleteLessonItems($ub, $lb, 1);

    $service = app(AnalyticsService::class);
    $activity = $service->recentActivity($courseA);

    // Seules les complétions/inscriptions du cours A remontent.
    expect($activity['completions'])->toHaveCount(1);
    expect($activity['completions']->first()->user_id)->toBe($ua->id);
    expect($activity['enrollments'])->toHaveCount(1);
    expect($activity['enrollments']->first()->user_id)->toBe($ua->id);
});
