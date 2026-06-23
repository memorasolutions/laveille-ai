<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F14 ÉCHELLES PERSONNALISÉES + MÉTHODES D'AGRÉGATION du carnet.
 *
 * Prouve, côté SERVEUR :
 *  - chaque méthode d'agrégation d'une catégorie donne le score attendu
 *    (weighted_mean, simple_mean, sum, highest, lowest, median) ;
 *  - RÉTROCOMPAT STRICTE : défaut weighted_mean = calcul V2-b identique, et une
 *    méthode absente/illisible retombe sur weighted_mean ;
 *  - échelle : conversion niveau → points sur max_points (formule documentée) ;
 *    correction d'un devoir PAR ÉCHELLE stocke la bonne note (carnet inchangé) ;
 *  - anti-IDOR : éditer l'échelle d'un AUTRE propriétaire → ModelNotFound ;
 *    rattacher l'échelle d'un autre propriétaire à un devoir → ignoré (scale_id null) ;
 *  - rétrocompat : carnet sans méthode ni échelle = notes EXACTEMENT identiques ;
 *  - migrations additives présentes.
 *
 * Helpers PRÉFIXÉS « f14 » pour éviter toute redéclaration. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\GradeCategory;
use Modules\Academy\Models\GradeItem;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Models\Scale;
use Modules\Academy\Models\Submission;
use Modules\Academy\Services\GradebookService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->course = f14Course('f14-a');
});

function f14Course(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours F14 '.$slug,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function f14Owner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'owner']);

    return $user;
}

function f14Student(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('student');
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    return $user;
}

/** Crée un item quiz (chapitre+leçon dédiés) ET une tentative de l'étudiant à $percent. */
function f14QuizWithAttempt(Course $course, User $student, int $percent, string $title): LessonItem
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch '.$title, 'position' => 1]);
    $lesson  = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon '.$title,
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
    $item = LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => $title,
        'position'    => 1,
        'payload'     => ['grading_method' => 'highest', 'passing_score' => 60],
        'is_required' => false,
    ]);

    QuizAttempt::create([
        'user_id'        => $student->id,
        'lesson_item_id' => $item->id,
        'course_id'      => $course->id,
        'score'          => $percent,
        'max_score'      => 100,
        'percent'        => $percent,
        'passed'         => $percent >= 60,
        'answers'        => [],
        'submitted_at'   => now(),
    ]);

    return $item;
}

function f14Category(Course $course, string $name, float $weight, string $method): GradeCategory
{
    return GradeCategory::create([
        'course_id'          => $course->id,
        'name'               => $name,
        'weight'             => $weight,
        'aggregation_method' => $method,
        'position'           => GradeCategory::where('course_id', $course->id)->count() + 1,
    ]);
}

function f14AssignItem(Course $course, string $type, int $itemId, GradeCategory $cat, float $weight): GradeItem
{
    return GradeItem::create([
        'course_id'         => $course->id,
        'item_type'         => $type,
        'item_id'           => $itemId,
        'grade_category_id' => $cat->id,
        'weight'            => $weight,
    ]);
}

/**
 * Monte une catégorie UNIQUE avec deux items quiz : 80 % (poids 3) et 60 % (poids 1),
 * agrégés par $method. Renvoie la note finale calculée.
 */
function f14FinalFor(Course $course, User $student, string $method): float
{
    $q1 = f14QuizWithAttempt($course, $student, 80, 'Quiz haut '.$method);
    $q2 = f14QuizWithAttempt($course, $student, 60, 'Quiz bas '.$method);

    $cat = f14Category($course, 'Cat '.$method, 100, $method);
    f14AssignItem($course, 'quiz', $q1->id, $cat, 3.0);
    f14AssignItem($course, 'quiz', $q2->id, $cat, 1.0);

    return GradebookService::finalGradeFor($student->fresh(), $course->fresh())['final'];
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Méthodes d'agrégation
// ─────────────────────────────────────────────────────────────────────────────

it('weighted_mean = moyenne pondérée (80×3 + 60×1)/4 = 75', function (): void {
    $student = f14Student($this->course);
    f14Owner($this->course);

    expect(f14FinalFor($this->course, $student, GradeCategory::AGGREGATION_WEIGHTED_MEAN))->toBe(75.0);
});

it('simple_mean ignore les poids : (80 + 60)/2 = 70', function (): void {
    $student = f14Student($this->course);

    expect(f14FinalFor($this->course, $student, GradeCategory::AGGREGATION_SIMPLE_MEAN))->toBe(70.0);
});

it('sum = somme des pourcentages plafonnée à 100', function (): void {
    $student = f14Student($this->course);

    expect(f14FinalFor($this->course, $student, GradeCategory::AGGREGATION_SUM))->toBe(100.0);
});

it('highest = note la plus haute (80)', function (): void {
    $student = f14Student($this->course);

    expect(f14FinalFor($this->course, $student, GradeCategory::AGGREGATION_HIGHEST))->toBe(80.0);
});

it('lowest = note la plus basse (60)', function (): void {
    $student = f14Student($this->course);

    expect(f14FinalFor($this->course, $student, GradeCategory::AGGREGATION_LOWEST))->toBe(60.0);
});

it('median = médiane des notes', function (): void {
    $student = f14Student($this->course);
    f14Owner($this->course);

    // Trois items : 80, 60, 50 → médiane = 60.
    $q1 = f14QuizWithAttempt($this->course, $student, 80, 'M1');
    $q2 = f14QuizWithAttempt($this->course, $student, 60, 'M2');
    $q3 = f14QuizWithAttempt($this->course, $student, 50, 'M3');

    $cat = f14Category($this->course, 'Med', 100, GradeCategory::AGGREGATION_MEDIAN);
    f14AssignItem($this->course, 'quiz', $q1->id, $cat, 1.0);
    f14AssignItem($this->course, 'quiz', $q2->id, $cat, 1.0);
    f14AssignItem($this->course, 'quiz', $q3->id, $cat, 1.0);

    expect(GradebookService::finalGradeFor($student->fresh(), $this->course->fresh())['final'])->toBe(60.0);
});

it('aggregate() statique : médiane d\'un nombre pair = moyenne des 2 centrales', function (): void {
    $entries = [
        ['pct' => 40.0, 'weight' => 1.0],
        ['pct' => 60.0, 'weight' => 1.0],
        ['pct' => 80.0, 'weight' => 1.0],
        ['pct' => 100.0, 'weight' => 1.0],
    ];

    expect(GradebookService::aggregate($entries, GradeCategory::AGGREGATION_MEDIAN))->toBe(70.0);
    expect(GradebookService::aggregate([], GradeCategory::AGGREGATION_HIGHEST))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Rétrocompat : défaut weighted_mean + méthode illisible
// ─────────────────────────────────────────────────────────────────────────────

it('défaut weighted_mean (méthode non précisée) = calcul V2-b identique', function (): void {
    $student = f14Student($this->course);
    f14Owner($this->course);

    $q1 = f14QuizWithAttempt($this->course, $student, 80, 'D1');
    $q2 = f14QuizWithAttempt($this->course, $student, 60, 'D2');

    // Catégorie créée SANS aggregation_method → défaut du modèle = weighted_mean.
    $cat = GradeCategory::create([
        'course_id' => $this->course->id,
        'name'      => 'Sans méthode',
        'weight'    => 100,
        'position'  => 1,
    ]);
    f14AssignItem($this->course, 'quiz', $q1->id, $cat, 3.0);
    f14AssignItem($this->course, 'quiz', $q2->id, $cat, 1.0);

    expect($cat->fresh()->aggregation_method)->toBe('weighted_mean');
    expect(GradebookService::finalGradeFor($student->fresh(), $this->course->fresh())['final'])->toBe(75.0);
});

it('effectiveAggregationMethod() retombe sur weighted_mean si valeur nulle/illisible', function (): void {
    $cat = f14Category($this->course, 'X', 100, GradeCategory::AGGREGATION_HIGHEST);

    // Force une valeur illisible directement en base (simule legacy/forgé).
    GradeCategory::where('id', $cat->id)->update(['aggregation_method' => 'inconnu']);

    expect($cat->fresh()->effectiveAggregationMethod())->toBe('weighted_mean');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Échelles : conversion + correction par niveau
// ─────────────────────────────────────────────────────────────────────────────

it('convertit la valeur d\'un niveau en points sur max_points (formule documentée)', function (): void {
    $scale = Scale::create([
        'owner_id' => null,
        'name'     => 'Maîtrise',
        'items'    => [
            ['label' => 'Insuffisant', 'value' => 0],
            ['label' => 'Acceptable', 'value' => 1],
            ['label' => 'Maîtrisé', 'value' => 2],
        ],
    ]);

    // max_points = 10, maxValue = 2 → 0/2*10=0 ; 1/2*10=5 ; 2/2*10=10.
    expect(GradebookService::scaleValueToPoints($scale, 0.0, 10))->toBe(0);
    expect(GradebookService::scaleValueToPoints($scale, 1.0, 10))->toBe(5);
    expect(GradebookService::scaleValueToPoints($scale, 2.0, 10))->toBe(10);
    expect($scale->maxValue())->toBe(2.0);
});

it('corrige un devoir PAR ÉCHELLE : le niveau choisi est converti et stocké', function (): void {
    $owner   = f14Owner($this->course);
    $student = f14Student($this->course);

    $scale = Scale::create([
        'owner_id' => $owner->id,
        'name'     => 'Niveaux',
        'items'    => [
            ['label' => 'Faible', 'value' => 0],
            ['label' => 'Moyen', 'value' => 1],
            ['label' => 'Fort', 'value' => 2],
        ],
    ]);

    $devoir = Assignment::create([
        'course_id'    => $this->course->id,
        'title'        => 'Devoir échelle',
        'max_points'   => 10,
        'scale_id'     => $scale->id,
        'is_published' => true,
        'position'     => 1,
    ]);

    $submission = Submission::create([
        'assignment_id' => $devoir->id,
        'user_id'       => $student->id,
        'body'          => 'Réponse.',
        'submitted_at'  => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $devoir->id)
        ->call('startGrading', $submission->id)
        ->set('gradeScaleLevel', '1') // niveau « Moyen » (value 1) → 5/10
        ->call('gradeSubmission')
        ->assertHasNoErrors();

    expect((int) $submission->fresh()->score)->toBe(5);
    expect($submission->fresh()->graded_at)->not->toBeNull();
});

it('refuse une correction par échelle sans niveau choisi', function (): void {
    $owner   = f14Owner($this->course);
    $student = f14Student($this->course);

    $scale = Scale::create([
        'owner_id' => $owner->id,
        'name'     => 'N',
        'items'    => [['label' => 'A', 'value' => 0], ['label' => 'B', 'value' => 1]],
    ]);
    $devoir = Assignment::create([
        'course_id' => $this->course->id, 'title' => 'D', 'max_points' => 10,
        'scale_id'  => $scale->id, 'is_published' => true, 'position' => 1,
    ]);
    $submission = Submission::create([
        'assignment_id' => $devoir->id, 'user_id' => $student->id, 'submitted_at' => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $devoir->id)
        ->call('startGrading', $submission->id)
        ->set('gradeScaleLevel', '')
        ->call('gradeSubmission')
        ->assertHasErrors('gradeScaleLevel');

    expect($submission->fresh()->score)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. CRUD échelle + anti-IDOR owner
// ─────────────────────────────────────────────────────────────────────────────

it('crée une échelle owner-scopée via le composant', function (): void {
    $owner = f14Owner($this->course);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->set('newScaleName', 'Mon échelle')
        ->set('newScaleItems', "Insuffisant | 0\nAcceptable | 1\nMaîtrisé | 2")
        ->call('addScale')
        ->assertHasNoErrors();

    $scale = Scale::where('name', 'Mon échelle')->first();
    expect($scale)->not->toBeNull();
    expect($scale->owner_id)->toBe($owner->id);
    expect(count($scale->levels()))->toBe(3);
});

it('refuse une échelle à moins de deux niveaux', function (): void {
    $owner = f14Owner($this->course);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->set('newScaleName', 'Trop court')
        ->set('newScaleItems', 'Seul niveau | 1')
        ->call('addScale')
        ->assertHasErrors('newScaleItems');

    expect(Scale::where('name', 'Trop court')->exists())->toBeFalse();
});

it('empêche d\'éditer l\'échelle d\'un AUTRE propriétaire (anti-IDOR)', function (): void {
    $ownerA = f14Owner($this->course);
    $ownerB = f14Owner($this->course); // autre formateur
    $scaleB = Scale::create(['owner_id' => $ownerB->id, 'name' => 'B', 'items' => [['label' => 'x', 'value' => 0], ['label' => 'y', 'value' => 1]]]);

    expect(function () use ($ownerA, $scaleB): void {
        Livewire::actingAs($ownerA)
            ->test(CourseAssignments::class, ['course' => $this->course])
            ->call('editScale', $scaleB->id);
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('ignore le rattachement à un devoir d\'une échelle d\'un autre propriétaire (anti-IDOR)', function (): void {
    $ownerA = f14Owner($this->course);
    $ownerB = f14Owner($this->course);
    $scaleB = Scale::create(['owner_id' => $ownerB->id, 'name' => 'B', 'items' => [['label' => 'x', 'value' => 0], ['label' => 'y', 'value' => 1]]]);

    Livewire::actingAs($ownerA)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->set('title', 'Devoir A')
        ->set('maxPoints', 20)
        ->set('scaleId', (string) $scaleB->id)
        ->call('saveAssignment', true)
        ->assertHasNoErrors();

    $devoir = Assignment::where('course_id', $this->course->id)->where('title', 'Devoir A')->first();
    expect($devoir)->not->toBeNull();
    expect($devoir->scale_id)->toBeNull(); // échelle étrangère ignorée
});

it('rattache une échelle POSSÉDÉE au devoir', function (): void {
    $owner = f14Owner($this->course);
    $scale = Scale::create(['owner_id' => $owner->id, 'name' => 'OK', 'items' => [['label' => 'x', 'value' => 0], ['label' => 'y', 'value' => 1]]]);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->set('title', 'Devoir échelle OK')
        ->set('maxPoints', 20)
        ->set('scaleId', (string) $scale->id)
        ->call('saveAssignment', true)
        ->assertHasNoErrors();

    $devoir = Assignment::where('title', 'Devoir échelle OK')->first();
    expect($devoir->scale_id)->toBe($scale->id);
    expect($devoir->hasScale())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Rétrocompat carnet sans méthode ni échelle (notes inchangées) + UI méthode
// ─────────────────────────────────────────────────────────────────────────────

it('un devoir SANS échelle reste noté numériquement (rétrocompat)', function (): void {
    $owner   = f14Owner($this->course);
    $student = f14Student($this->course);

    $devoir = Assignment::create([
        'course_id' => $this->course->id, 'title' => 'Numérique', 'max_points' => 20,
        'is_published' => true, 'position' => 1,
    ]);
    $submission = Submission::create([
        'assignment_id' => $devoir->id, 'user_id' => $student->id, 'submitted_at' => now(),
    ]);

    expect($devoir->hasScale())->toBeFalse();

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $devoir->id)
        ->call('startGrading', $submission->id)
        ->set('gradeScore', '17')
        ->call('gradeSubmission')
        ->assertHasNoErrors();

    expect((int) $submission->fresh()->score)->toBe(17);
});

it('crée une catégorie avec une méthode d\'agrégation choisie (liste blanche)', function (): void {
    $owner = f14Owner($this->course);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->set('newCategoryName', 'Examens')
        ->set('newCategoryWeight', '50')
        ->set('newCategoryMethod', GradeCategory::AGGREGATION_HIGHEST)
        ->call('addCategory')
        ->assertHasNoErrors();

    $cat = GradeCategory::where('course_id', $this->course->id)->where('name', 'Examens')->first();
    expect($cat->aggregation_method)->toBe(GradeCategory::AGGREGATION_HIGHEST);
});

it('rejette une méthode d\'agrégation hors liste blanche', function (): void {
    $owner = f14Owner($this->course);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->course])
        ->set('newCategoryName', 'Mauvaise')
        ->set('newCategoryWeight', '50')
        ->set('newCategoryMethod', 'pirate')
        ->call('addCategory')
        ->assertHasErrors('newCategoryMethod');

    expect(GradeCategory::where('name', 'Mauvaise')->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. Migrations additives
// ─────────────────────────────────────────────────────────────────────────────

it('a créé les colonnes/table additives F14', function (): void {
    expect(Schema::hasColumn('academy_grade_categories', 'aggregation_method'))->toBeTrue();
    expect(Schema::hasTable('academy_scales'))->toBeTrue();
    expect(Schema::hasColumn('academy_assignments', 'scale_id'))->toBeTrue();
});
