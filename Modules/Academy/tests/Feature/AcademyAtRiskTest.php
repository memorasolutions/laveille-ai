<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - D2 : Apprenants « à risque » (AnalyticsService::atRiskLearners +
 * section de la page CourseAnalytics).
 *
 * Prouve :
 *  - la classification est EXACTE : « jamais commencé » (inscrit > 7 j, 0 %),
 *    « inactif » (progression 1-99 %, aucune activité > 14 j), avec exclusion des
 *    apprenants actifs récents et de ceux à 100 % ;
 *  - SÉCURITÉ (OWASP A01) : un non-gérant → 403 ; anti-IDOR (cours A vs B) ;
 *  - état vide (aucun à risque) ne plante pas.
 *
 * Helpers préfixés `d2` (aucune redéclaration avec les autres fichiers de la suite).
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
use Modules\Academy\Models\Progress;
use Modules\Academy\Services\AnalyticsService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

/** Helper : crée un cours gratuit en brouillon minimal. */
function d2MakeCourse(string $slug, string $title = 'Cours'): Course
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

/** Helper : ajoute une leçon (dans un chapitre) avec N items requis. */
function d2AddLesson(Course $course, string $title, int $requiredItems, int $position = 1): Lesson
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

/** Helper : owner (formateur) d'un cours. */
function d2MakeOwner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'owner']);

    return $user;
}

/** Helper : étudiant (sans rôle de cours). */
function d2MakeStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');

    return $student;
}

/** Helper : inscrit un user (status='active') à un cours, avec date d'inscription. */
function d2Enroll(Course $course, User $user, ?\Illuminate\Support\Carbon $when = null): Enrollment
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
 * Helper : pose une ligne de progression explicite (percent + dernière activité).
 * Permet de simuler un apprenant inactif / bloqué sans dépendre de l'heure courante.
 */
function d2SetProgress(Course $course, User $user, int $percent, \Illuminate\Support\Carbon $lastActivity): Progress
{
    return Progress::updateOrCreate(
        ['user_id' => $user->id, 'course_id' => $course->id],
        ['percent' => $percent, 'last_activity_at' => $lastActivity]
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Classification exacte
// ─────────────────────────────────────────────────────────────────────────────

test('atRiskLearners retourne exactement les apprenants à risque avec la bonne raison', function (): void {
    $course = d2MakeCourse('d2-classify');
    d2AddLesson($course, 'Leçon 1', 2, 1);

    // 1) Jamais commencé : inscrit il y a 10 j, aucune progression (0 %).
    $neverStarted = d2MakeStudent();
    d2Enroll($course, $neverStarted, now()->subDays(10));

    // 2) Inactif : progression 40 %, dernière activité il y a 20 j (> 14 j).
    $inactive = d2MakeStudent();
    d2Enroll($course, $inactive, now()->subDays(30));
    d2SetProgress($course, $inactive, 40, now()->subDays(20));

    // 3) Actif récent : progression 60 %, activité il y a 2 j → PAS à risque.
    $activeRecent = d2MakeStudent();
    d2Enroll($course, $activeRecent, now()->subDays(30));
    d2SetProgress($course, $activeRecent, 60, now()->subDays(2));

    // 4) Terminé : 100 % → JAMAIS à risque (même si vieux).
    $done = d2MakeStudent();
    d2Enroll($course, $done, now()->subDays(40));
    d2SetProgress($course, $done, 100, now()->subDays(30));

    $service = app(AnalyticsService::class);
    $atRisk = $service->atRiskLearners($course);

    // Exactement 2 apprenants à risque : never_started + inactive.
    expect($atRisk)->toHaveCount(2);

    $byUser = $atRisk->keyBy('user_id');

    expect($byUser->has($neverStarted->id))->toBeTrue();
    expect($byUser[$neverStarted->id]['reason_key'])->toBe('never_started');
    expect($byUser[$neverStarted->id]['percent'])->toBe(0);

    expect($byUser->has($inactive->id))->toBeTrue();
    expect($byUser[$inactive->id]['reason_key'])->toBe('inactive');
    expect($byUser[$inactive->id]['percent'])->toBe(40);

    // Les deux non-à-risque sont absents.
    expect($byUser->has($activeRecent->id))->toBeFalse();
    expect($byUser->has($done->id))->toBeFalse();

    // Tri par gravité : « jamais commencé » (severity 1) en tête.
    expect($atRisk->first()['user_id'])->toBe($neverStarted->id);
});

test('un apprenant bloqué (1-99 %, inactivité 7-14 j) est détecté comme « Bloqué »', function (): void {
    $course = d2MakeCourse('d2-stuck');
    d2AddLesson($course, 'Leçon 1', 2, 1);

    $stuck = d2MakeStudent();
    d2Enroll($course, $stuck, now()->subDays(30));
    d2SetProgress($course, $stuck, 30, now()->subDays(10)); // > 7 j et <= 14 j

    $service = app(AnalyticsService::class);
    $atRisk = $service->atRiskLearners($course);

    expect($atRisk)->toHaveCount(1);
    expect($atRisk->first()['reason_key'])->toBe('stuck');
    expect($atRisk->first()['reason'])->toBe('Bloqué');
});

test('un inscrit récent à 0 % (<= 7 j) n\'est PAS encore « jamais commencé »', function (): void {
    $course = d2MakeCourse('d2-fresh');
    d2AddLesson($course, 'Leçon 1', 1, 1);

    $fresh = d2MakeStudent();
    d2Enroll($course, $fresh, now()->subDays(3)); // 0 %, inscrit récemment

    $service = app(AnalyticsService::class);
    expect($service->atRiskLearners($course))->toHaveCount(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. SÉCURITÉ — non-gérant → 403, anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('un étudiant ne peut pas ouvrir les analytics (donc la section à-risque) → 403', function (): void {
    $course = d2MakeCourse('d2-sec-student');

    Livewire::actingAs(d2MakeStudent())
        ->test(CourseAnalytics::class, ['course' => $course])
        ->assertForbidden();
});

test('ANTI-IDOR : owner de A ne peut pas voir les apprenants à risque du cours B', function (): void {
    $courseA = d2MakeCourse('d2-idor-a');
    $courseB = d2MakeCourse('d2-idor-b');
    $owner = d2MakeOwner($courseA);

    // Apprenant à risque sur B uniquement (ne doit jamais fuiter à l'owner de A).
    $risky = d2MakeStudent();
    d2Enroll($courseB, $risky, now()->subDays(10)); // jamais commencé sur B

    // Montage légitime sur A, puis forçage du courseId vers B → ré-autorisation = 403.
    Livewire::actingAs($owner)
        ->test(CourseAnalytics::class, ['course' => $courseA])
        ->set('courseId', $courseB->id)
        ->assertForbidden();
});

test('scope : les apprenants à risque de B ne remontent pas dans A', function (): void {
    $courseA = d2MakeCourse('d2-scope-a');
    $courseB = d2MakeCourse('d2-scope-b');
    d2AddLesson($courseA, 'A1', 1, 1);

    $riskyB = d2MakeStudent();
    d2Enroll($courseB, $riskyB, now()->subDays(10));

    $service = app(AnalyticsService::class);

    // A n'a aucun inscrit → aucun à risque ; B en a 1.
    expect($service->atRiskLearners($courseA))->toHaveCount(0);
    expect($service->atRiskLearners($courseB))->toHaveCount(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. État vide — aucun à risque ne plante pas
// ─────────────────────────────────────────────────────────────────────────────

test('un cours sans apprenant à risque retourne une collection vide sans planter', function (): void {
    $course = d2MakeCourse('d2-empty');
    d2AddLesson($course, 'Leçon 1', 1, 1);

    // Inscrit récent + apprenant à 100 % : aucun à risque.
    $fresh = d2MakeStudent();
    d2Enroll($course, $fresh, now()->subDays(2));

    $done = d2MakeStudent();
    d2Enroll($course, $done, now()->subDays(20));
    d2SetProgress($course, $done, 100, now()->subDays(15));

    $service = app(AnalyticsService::class);
    $atRisk = $service->atRiskLearners($course);

    expect($atRisk)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    expect($atRisk)->toHaveCount(0);
});

test('le composant rend la section à-risque (owner) sans erreur, avec et sans apprenants à risque', function (): void {
    $course = d2MakeCourse('d2-render');
    $owner = d2MakeOwner($course);
    d2AddLesson($course, 'Leçon 1', 1, 1);

    // Sans apprenant à risque → état vide positif.
    Livewire::actingAs($owner)
        ->test(CourseAnalytics::class, ['course' => $course])
        ->assertOk()
        ->assertSee('Apprenants à accompagner')
        ->assertSee('Aucun apprenant à risque');

    // Avec un apprenant « jamais commencé ».
    $risky = d2MakeStudent();
    d2Enroll($course, $risky, now()->subDays(10));

    Livewire::actingAs($owner)
        ->test(CourseAnalytics::class, ['course' => $course])
        ->assertOk()
        ->assertSee('Jamais commencé');
});
