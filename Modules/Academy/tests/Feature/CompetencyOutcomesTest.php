<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F22 COMPÉTENCES / RÉSULTATS (outcomes, parité Moodle).
 *
 * Prouve que :
 *  - une compétence est créée OWNER-SCOPÉE (owner_id = auth, jamais du navigateur) et
 *    qu'un autre formateur ne la voit pas / ne peut pas l'éditer (anti-IDOR) ;
 *  - l'association cours/item est gâtée manageStructure et anti-IDOR (un item d'un
 *    autre cours est refusé ; le formateur d'un autre cours reçoit 403) ;
 *  - l'acquisition est DÉRIVÉE correctement : acquise quand les items liés sont
 *    complétés (V2-c), et par note >= seuil sur les items notés liés ;
 *  - un étudiant ne voit QUE ses compétences (cours suivis) ;
 *  - RÉTROCOMPAT : sans aucune compétence/lien, rien ne change (section vide).
 *
 * Autonome : helpers préfixés « f22 ». Skippé si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CompetencyManager;
use Modules\Academy\Livewire\CourseCompetencies;
use Modules\Academy\Livewire\StudentCompetencies;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Competency;
use Modules\Academy\Models\CompetencyLink;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\CompetencyService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers f22 (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function f22Course(string $slug = 'f22-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'F22 Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function f22Instructor(): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');

    return $u;
}

function f22Owner(Course $course): User
{
    $owner = f22Instructor();
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    return $owner;
}

function f22Student(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

/** @return array<int, LessonItem> */
function f22Items(Course $course, int $count, string $type = 'document'): array
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chap', 'position' => 1]);
    $items   = [];
    for ($i = 1; $i <= $count; $i++) {
        $lesson  = Lesson::create([
            'chapter_id' => $chapter->id,
            'title'      => "Leçon $i",
            'slug'       => "f22-l-$i-{$course->id}",
            'position'   => $i,
        ]);
        $items[] = LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => $type,
            'title'       => "Item $i",
            'position'    => 1,
            'is_required' => true,
            'payload'     => $type === 'quiz' ? ['questions' => [['q' => 'x']]] : null,
        ]);
    }

    return $items;
}

function f22Complete(User $user, LessonItem $item): void
{
    Completion::create([
        'user_id'        => $user->id,
        'course_id'      => $item->lesson->chapter->course_id,
        'lesson_item_id' => $item->id,
        'status'         => 'completed',
        'completed_at'   => now(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Compétence créée owner-scopée + anti-IDOR du référentiel
// ─────────────────────────────────────────────────────────────────────────────

it('crée une compétence owner-scopée (owner_id forcé = auth)', function (): void {
    $owner = f22Instructor();

    Livewire::actingAs($owner)
        ->test(CompetencyManager::class)
        ->set('name', 'Penser de façon critique')
        ->set('description', 'Analyser une source IA.')
        ->call('save')
        ->assertHasNoErrors();

    $c = Competency::where('name', 'Penser de façon critique')->first();
    expect($c)->not->toBeNull()
        ->and($c->owner_id)->toBe($owner->id)
        ->and($c->slug)->toBe('penser-de-facon-critique')
        ->and($c->is_active)->toBeTrue();
});

it('un autre formateur ne voit pas et ne peut pas éditer la compétence d’autrui (anti-IDOR)', function (): void {
    $owner = f22Instructor();
    $other = f22Instructor();

    $c = Competency::create(['owner_id' => $owner->id, 'name' => 'À moi', 'slug' => 'a-moi', 'is_active' => true]);

    // Le voisin ne la voit pas dans sa liste (owner-scopée à lui).
    $component = Livewire::actingAs($other)->test(CompetencyManager::class);
    expect($component->instance()->competencies()->pluck('id'))->not->toContain($c->id);

    // Et ne peut pas l'éditer (re-résolution scopée → ModelNotFound).
    expect(fn () => $component->call('edit', $c->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Association cours/item : gâtée manageStructure + anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

it('un formateur d’un autre cours reçoit 403 à l’ouverture des compétences du cours', function (): void {
    $course = f22Course();
    f22Owner($course);
    $intruder = f22Instructor(); // formateur mais PAS de ce cours

    // L'autorisation manageStructure échoue dans mount() : Livewire rend un 403.
    Livewire::actingAs($intruder)
        ->test(CourseCompetencies::class, ['course' => $course])
        ->assertForbidden();
});

it('associe une compétence au cours et à un item (anti-IDOR sur l’item d’un autre cours)', function (): void {
    $course = f22Course();
    $owner  = f22Owner($course);
    [$item] = f22Items($course, 1);

    $competency = Competency::create(['owner_id' => $owner->id, 'name' => 'Comp', 'slug' => 'comp', 'is_active' => true]);

    $component = Livewire::actingAs($owner)
        ->test(CourseCompetencies::class, ['course' => $course])
        ->set('selectedCompetencyId', $competency->id)
        ->call('attachToCourse', $competency->id)
        ->call('attachToItem', $competency->id, $item->id);

    expect(CompetencyLink::where('competency_id', $competency->id)->where('course_id', $course->id)->exists())->toBeTrue()
        ->and(CompetencyLink::where('competency_id', $competency->id)->where('lesson_item_id', $item->id)->exists())->toBeTrue();

    // Un item d'un AUTRE cours est refusé (re-scopé au cours → ModelNotFound).
    $foreignCourse = f22Course('f22-autre');
    [$foreignItem] = f22Items($foreignCourse, 1);

    expect(fn () => $component->call('attachToItem', $competency->id, $foreignItem->id))
        ->toThrow(Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Acquisition DÉRIVÉE : achèvement (V2-c) + note >= seuil
// ─────────────────────────────────────────────────────────────────────────────

it('dérive l’acquisition par achèvement : acquise quand tous les items liés sont complétés', function (): void {
    $course  = f22Course();
    $owner   = f22Owner($course);
    $student = f22Student();
    Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

    $items      = f22Items($course, 2);
    $competency = Competency::create(['owner_id' => $owner->id, 'name' => 'C', 'slug' => 'c', 'is_active' => true]);
    foreach ($items as $item) {
        CompetencyLink::create(['competency_id' => $competency->id, 'lesson_item_id' => $item->id]);
    }

    // Rien de complété → non commencée.
    expect(CompetencyService::acquisitionState($student, $competency)['state'])
        ->toBe(CompetencyService::STATE_NOT_STARTED);

    // Un item complété → en cours.
    f22Complete($student, $items[0]);
    expect(CompetencyService::acquisitionState($student, $competency)['state'])
        ->toBe(CompetencyService::STATE_IN_PROGRESS);

    // Tous complétés → acquise (niveau binaire « Atteint »).
    f22Complete($student, $items[1]);
    $state = CompetencyService::acquisitionState($student, $competency);
    expect($state['state'])->toBe(CompetencyService::STATE_ACQUIRED)
        ->and($state['achieved'])->toBe(2)
        ->and($state['total'])->toBe(2)
        ->and($state['level'])->toBe('Atteint');
});

it('dérive l’acquisition par NOTE >= seuil sur un item noté lié', function (): void {
    $course  = f22Course();
    $owner   = f22Owner($course);
    $student = f22Student();
    Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

    [$quiz]     = f22Items($course, 1, 'quiz');
    $competency = Competency::create([
        'owner_id'       => $owner->id,
        'name'           => 'Q',
        'slug'           => 'q',
        'pass_threshold' => 70,
        'is_active'      => true,
    ]);
    CompetencyLink::create(['competency_id' => $competency->id, 'lesson_item_id' => $quiz->id]);

    // Tentative en dessous du seuil (50 < 70) → en cours (tentative existe), pas acquise.
    QuizAttempt::create([
        'user_id' => $student->id, 'lesson_item_id' => $quiz->id, 'course_id' => $course->id,
        'score' => 50, 'max_score' => 100, 'percent' => 50, 'passed' => false,
        'answers' => [], 'needs_grading' => false, 'submitted_at' => now(),
    ]);
    expect(CompetencyService::acquisitionState($student, $competency)['state'])
        ->toBe(CompetencyService::STATE_IN_PROGRESS);

    // Nouvelle tentative au-dessus du seuil (90 >= 70) → acquise (méthode défaut highest).
    QuizAttempt::create([
        'user_id' => $student->id, 'lesson_item_id' => $quiz->id, 'course_id' => $course->id,
        'score' => 90, 'max_score' => 100, 'percent' => 90, 'passed' => true,
        'answers' => [], 'needs_grading' => false, 'submitted_at' => now()->addMinute(),
    ]);
    expect(CompetencyService::acquisitionState($student, $competency)['state'])
        ->toBe(CompetencyService::STATE_ACQUIRED);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Étudiant ne voit QUE ses compétences
// ─────────────────────────────────────────────────────────────────────────────

it('un étudiant ne voit que les compétences de SES cours suivis', function (): void {
    $owner = f22Instructor();

    // Cours suivi par l'étudiant.
    $mine        = f22Course('f22-mine');
    $myComp      = Competency::create(['owner_id' => $owner->id, 'name' => 'Mienne', 'slug' => 'mienne', 'is_active' => true]);
    CompetencyLink::create(['competency_id' => $myComp->id, 'course_id' => $mine->id]);

    // Cours NON suivi.
    $other       = f22Course('f22-other');
    $otherComp   = Competency::create(['owner_id' => $owner->id, 'name' => 'Autre', 'slug' => 'autre', 'is_active' => true]);
    CompetencyLink::create(['competency_id' => $otherComp->id, 'course_id' => $other->id]);

    $student = f22Student();
    Enrollment::create(['user_id' => $student->id, 'course_id' => $mine->id, 'status' => 'active']);

    $names = CompetencyService::studentCompetencies($student)->pluck('competency.name');
    expect($names)->toContain('Mienne')->not->toContain('Autre');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Rétrocompat : aucune compétence = section vide, aucun changement
// ─────────────────────────────────────────────────────────────────────────────

it('reste rétrocompatible : sans aucune compétence, la vue étudiant est vide', function (): void {
    $student = f22Student();
    $course  = f22Course();
    Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'status' => 'active']);

    expect(CompetencyService::studentCompetencies($student))->toHaveCount(0);

    Livewire::actingAs($student)
        ->test(StudentCompetencies::class)
        ->assertOk();
});
