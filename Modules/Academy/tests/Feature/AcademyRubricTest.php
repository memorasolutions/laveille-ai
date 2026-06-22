<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - V2-a GRILLES D'ÉVALUATION (rubrics) des devoirs (parité Moodle
 * « rubric advanced grading »). ADDITIF : un devoir SANS grille garde la note
 * manuelle libre (rétrocompat prouvée).
 *
 * Prouve, côté SERVEUR (OWASP A01) :
 *  - construire une grille (2 critères × 3 niveaux) sur un devoir (manageStructure) ;
 *  - corriger par grille → score AUTO = somme des niveaux retenus, mise à l'échelle
 *    et bornée à max_points ; rubric_scores persistés ; graded_at/by posés ;
 *  - une sélection incomplète (un critère sans niveau) est REJETÉE ;
 *  - ANTI-IDOR : agir sur la grille / un niveau d'un AUTRE cours est refusé, et un
 *    niveau étranger « collé » dans la sélection est ignoré (donc rejeté) ;
 *  - gating : un étudiant ne peut pas monter le gérant (403) ;
 *  - l'étudiant voit SA grille corrigée (niveaux retenus), jamais celle d'un autre ;
 *  - RÉTROCOMPAT : devoir SANS grille → correction manuelle inchangée ;
 *  - ANTI-XSS : libellés de critère / niveau neutralisés ;
 *  - migrations additives (tables + colonne présentes).
 *
 * Helpers PRÉFIXÉS « v2a » pour éviter toute redéclaration avec les autres suites.
 * Garde-fou : module Academy désactivé → tous les tests SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Livewire\StudentAssignments;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\RubricCriterion;
use Modules\Academy\Models\RubricLevel;
use Modules\Academy\Models\Submission;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->courseA = v2aMakeCourse('rubric-a', 'Cours A');
    $this->courseB = v2aMakeCourse('rubric-b', 'Cours B');
});

function v2aMakeCourse(string $slug, string $title): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => $title,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v2aMakeOwner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $user->id,
        'role'      => 'owner',
    ]);

    return $user;
}

function v2aMakeEnrolledStudent(Course $course): User
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

function v2aMakeAssignment(Course $course, array $overrides = []): Assignment
{
    return Assignment::create(array_merge([
        'course_id'    => $course->id,
        'title'        => 'Devoir noté',
        'instructions' => 'Faites le travail.',
        'max_points'   => 20,
        'is_published' => true,
        'position'     => 1,
    ], $overrides));
}

/**
 * Construit une grille directement en base.
 * $spec : [ ['libellé critère', [['niveau', points], ...]], ... ]
 *
 * @return array<int, array{criterion: RubricCriterion, levels: array<int, RubricLevel>}>
 */
function v2aBuildRubric(Assignment $assignment, array $spec): array
{
    $out = [];
    $cPos = 1;
    foreach ($spec as [$label, $levels]) {
        $criterion = RubricCriterion::create([
            'assignment_id' => $assignment->id,
            'description'   => $label,
            'position'      => $cPos++,
        ]);
        $lvls = [];
        $lPos = 1;
        foreach ($levels as [$ldesc, $pts]) {
            $lvls[] = RubricLevel::create([
                'criterion_id' => $criterion->id,
                'description'  => $ldesc,
                'points'       => $pts,
                'position'     => $lPos++,
            ]);
        }
        $out[] = ['criterion' => $criterion, 'levels' => $lvls];
    }

    return $out;
}

// ─────────────────────────────────────────────────────────────────────────────
// Construire une grille (2 critères × 3 niveaux) via le composant
// ─────────────────────────────────────────────────────────────────────────────

it('construit une grille (2 critères × 3 niveaux) via le gérant', function (): void {
    $owner      = v2aMakeOwner($this->courseA);
    $assignment = v2aMakeAssignment($this->courseA);

    $component = Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('openRubric', $assignment->id);

    // 2 critères.
    $component->set('newCriterion', 'Argumentation')->call('addCriterion')
        ->set('newCriterion', 'Clarté')->call('addCriterion');

    $criteria = RubricCriterion::where('assignment_id', $assignment->id)->orderBy('position')->get();
    expect($criteria)->toHaveCount(2);

    // 3 niveaux par critère.
    foreach ($criteria as $criterion) {
        foreach ([['Faible', 0], ['Moyen', 1], ['Fort', 2]] as [$desc, $pts]) {
            $component->call('startAddLevel', $criterion->id)
                ->set('levelDescription', $desc)
                ->set('levelPoints', (string) $pts)
                ->call('addLevel');
        }
    }

    expect(RubricLevel::whereIn('criterion_id', $criteria->pluck('id'))->count())->toBe(6);
    expect($assignment->fresh()->hasRubric())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// Gating + anti-IDOR de construction
// ─────────────────────────────────────────────────────────────────────────────

it('interdit le montage du gérant à un étudiant (403)', function (): void {
    $student = v2aMakeEnrolledStudent($this->courseA);

    Livewire::actingAs($student)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->assertForbidden();
});

it('empêche un gérant d\'ouvrir/éditer la grille d\'un devoir d\'un autre cours (anti-IDOR)', function (): void {
    $ownerA = v2aMakeOwner($this->courseA);
    v2aMakeOwner($this->courseB);
    $foreign = v2aMakeAssignment($this->courseB, ['title' => 'Devoir B']);

    expect(function () use ($ownerA, $foreign): void {
        Livewire::actingAs($ownerA)
            ->test(CourseAssignments::class, ['course' => $this->courseA])
            ->call('openRubric', $foreign->id);
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(RubricCriterion::where('assignment_id', $foreign->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Corriger PAR GRILLE : score auto = somme des niveaux, mis à l'échelle / borné
// ─────────────────────────────────────────────────────────────────────────────

it('corrige par grille : score auto-calculé + rubric_scores persistés + graded_at/by', function (): void {
    $owner   = v2aMakeOwner($this->courseA);
    $student = v2aMakeEnrolledStudent($this->courseA);
    // max_points 20 ; barème max = 2 + 2 = 4.
    $assignment = v2aMakeAssignment($this->courseA, ['max_points' => 20]);
    $rubric     = v2aBuildRubric($assignment, [
        ['Argumentation', [['Faible', 0], ['Moyen', 1], ['Fort', 2]]],
        ['Clarté', [['Faible', 0], ['Moyen', 1], ['Fort', 2]]],
    ]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'Ma réponse.',
        'submitted_at'  => now(),
    ]);

    $critA = $rubric[0]['criterion'];
    $critB = $rubric[1]['criterion'];
    $topA  = $rubric[0]['levels'][2]; // 2 pts
    $midB  = $rubric[1]['levels'][1]; // 1 pt

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('reviewAssignment', $assignment->id)
        ->call('startGrading', $submission->id)
        ->set('rubricSelection', [$critA->id => (string) $topA->id, $critB->id => (string) $midB->id])
        ->set('gradeFeedback', 'Bon travail.')
        ->call('gradeSubmission');

    $submission->refresh();
    // raw = 2 + 1 = 3 ; barème max = 4 ; scaled = round(3/4*20) = 15 ; <= max_points.
    expect($submission->score)->toBe(15);
    expect($submission->score)->toBeLessThanOrEqual($assignment->max_points);
    expect($submission->rubric_scores)->toBe([
        (string) $critA->id => $topA->id,
        (string) $critB->id => $midB->id,
    ]);
    expect($submission->graded_at)->not->toBeNull();
    expect($submission->graded_by)->toBe($owner->id);
    expect($submission->gradedWithRubric())->toBeTrue();
});

it('plafonne la note à max_points (niveaux maximaux → exactement max_points)', function (): void {
    $owner   = v2aMakeOwner($this->courseA);
    $student = v2aMakeEnrolledStudent($this->courseA);
    $assignment = v2aMakeAssignment($this->courseA, ['max_points' => 10]);
    $rubric     = v2aBuildRubric($assignment, [
        ['C1', [['Bas', 0], ['Haut', 7]]],
        ['C2', [['Bas', 0], ['Haut', 3]]],
    ]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'X',
        'submitted_at'  => now(),
    ]);

    $critA = $rubric[0]['criterion'];
    $critB = $rubric[1]['criterion'];

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('reviewAssignment', $assignment->id)
        ->call('startGrading', $submission->id)
        ->set('rubricSelection', [
            $critA->id => (string) $rubric[0]['levels'][1]->id, // 7
            $critB->id => (string) $rubric[1]['levels'][1]->id, // 3
        ])
        ->call('gradeSubmission');

    $submission->refresh();
    // raw = 10 ; barème max = 10 ; scaled = 10 = max_points.
    expect($submission->score)->toBe(10);
});

it('rejette une correction par grille incomplète (un critère sans niveau retenu)', function (): void {
    $owner   = v2aMakeOwner($this->courseA);
    $student = v2aMakeEnrolledStudent($this->courseA);
    $assignment = v2aMakeAssignment($this->courseA);
    $rubric     = v2aBuildRubric($assignment, [
        ['C1', [['Bas', 0], ['Haut', 2]]],
        ['C2', [['Bas', 0], ['Haut', 2]]],
    ]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'X',
        'submitted_at'  => now(),
    ]);

    $critA = $rubric[0]['criterion'];

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('reviewAssignment', $assignment->id)
        ->call('startGrading', $submission->id)
        ->set('rubricSelection', [$critA->id => (string) $rubric[0]['levels'][1]->id]) // C2 manquant
        ->call('gradeSubmission')
        ->assertHasErrors('rubricSelection');

    $submission->refresh();
    expect($submission->score)->toBeNull();
    expect($submission->graded_at)->toBeNull();
});

it('ignore un niveau étranger collé dans la sélection (anti-IDOR → correction rejetée)', function (): void {
    $owner   = v2aMakeOwner($this->courseA);
    $student = v2aMakeEnrolledStudent($this->courseA);
    $assignment = v2aMakeAssignment($this->courseA);
    $rubric     = v2aBuildRubric($assignment, [
        ['C1', [['Bas', 0], ['Haut', 2]]],
    ]);

    // Niveau appartenant à un AUTRE devoir/critère.
    v2aMakeOwner($this->courseB);
    $foreignAssign = v2aMakeAssignment($this->courseB);
    $foreignRubric = v2aBuildRubric($foreignAssign, [['CB', [['Bas', 0], ['Haut', 9]]]]);
    $foreignLevel  = $foreignRubric[0]['levels'][1];

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'X',
        'submitted_at'  => now(),
    ]);

    $critA = $rubric[0]['criterion'];

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('reviewAssignment', $assignment->id)
        ->call('startGrading', $submission->id)
        ->set('rubricSelection', [$critA->id => (string) $foreignLevel->id])
        ->call('gradeSubmission')
        ->assertHasErrors('rubricSelection');

    $submission->refresh();
    // Niveau étranger ignoré → critère non noté → incomplet → rien écrit.
    expect($submission->score)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// Vue étudiant : voit SA grille corrigée, pas celle d'un autre
// ─────────────────────────────────────────────────────────────────────────────

it('affiche à l\'étudiant la grille corrigée (niveaux retenus)', function (): void {
    v2aMakeOwner($this->courseA);
    $student = v2aMakeEnrolledStudent($this->courseA);
    $assignment = v2aMakeAssignment($this->courseA, ['max_points' => 20]);
    $rubric     = v2aBuildRubric($assignment, [
        ['Argumentation', [['Faible', 0], ['Solide', 2]]],
        ['Clarté', [['Confuse', 0], ['Limpide', 2]]],
    ]);

    $critA = $rubric[0]['criterion'];
    $critB = $rubric[1]['criterion'];
    $lvlA  = $rubric[0]['levels'][1]; // Solide
    $lvlB  = $rubric[1]['levels'][0]; // Confuse

    Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'X',
        'submitted_at'  => now(),
        'score'         => 10,
        'rubric_scores' => [$critA->id => $lvlA->id, $critB->id => $lvlB->id],
        'graded_at'     => now(),
    ]);

    Livewire::actingAs($student)
        ->test(StudentAssignments::class)
        ->assertSee('Argumentation')
        ->assertSee('Solide')
        ->assertSee('Confuse')
        ->assertSee('10');
});

it('n\'expose pas à un étudiant la grille corrigée d\'un autre étudiant', function (): void {
    v2aMakeOwner($this->courseA);
    $alice = v2aMakeEnrolledStudent($this->courseA);
    $bob   = v2aMakeEnrolledStudent($this->courseA);
    $assignment = v2aMakeAssignment($this->courseA);
    $rubric     = v2aBuildRubric($assignment, [['C1', [['Bas', 0], ['Excellence', 2]]]]);
    $crit = $rubric[0]['criterion'];

    // Remise corrigée d'Alice (niveau « Excellence »).
    Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $alice->id,
        'body'          => 'Alice',
        'submitted_at'  => now(),
        'score'         => 2,
        'rubric_scores' => [$crit->id => $rubric[0]['levels'][1]->id],
        'graded_at'     => now(),
    ]);

    // Bob ne voit que SA propre situation (non remis) : pas la grille d'Alice.
    Livewire::actingAs($bob)
        ->test(StudentAssignments::class)
        ->assertDontSee('Excellence');
});

// ─────────────────────────────────────────────────────────────────────────────
// RÉTROCOMPAT : devoir SANS grille → correction manuelle inchangée
// ─────────────────────────────────────────────────────────────────────────────

it('garde la correction manuelle inchangée pour un devoir SANS grille', function (): void {
    $owner   = v2aMakeOwner($this->courseA);
    $student = v2aMakeEnrolledStudent($this->courseA);
    $assignment = v2aMakeAssignment($this->courseA, ['max_points' => 20]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'X',
        'submitted_at'  => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('reviewAssignment', $assignment->id)
        ->call('startGrading', $submission->id)
        ->set('gradeScore', '17')
        ->set('gradeFeedback', 'Manuel.')
        ->call('gradeSubmission');

    $submission->refresh();
    expect($submission->score)->toBe(17);
    expect($submission->rubric_scores)->toBeNull();      // aucune grille appliquée
    expect($submission->gradedWithRubric())->toBeFalse();
    expect($submission->graded_by)->toBe($owner->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// Anti-XSS sur libellés de critère / niveau
// ─────────────────────────────────────────────────────────────────────────────

it('neutralise le HTML brut des libellés de critère et de niveau (anti-XSS)', function (): void {
    $criterion = RubricCriterion::create([
        'assignment_id' => v2aMakeAssignment($this->courseA)->id,
        'description'   => "Critère <script>alert('xss')</script> **gras**",
        'position'      => 1,
    ]);
    $level = RubricLevel::create([
        'criterion_id' => $criterion->id,
        'description'  => "Niveau <script>alert('x')</script> **fort**",
        'points'       => 2,
        'position'     => 1,
    ]);

    expect($criterion->renderedDescription())->not->toContain('<script>');
    expect($criterion->renderedDescription())->toContain('<strong>gras</strong>');
    expect($level->renderedDescription())->not->toContain('<script>');
    expect($level->renderedDescription())->toContain('<strong>fort</strong>');
});

// ─────────────────────────────────────────────────────────────────────────────
// Migrations additives
// ─────────────────────────────────────────────────────────────────────────────

it('a créé les tables/colonne additives de la grille', function (): void {
    expect(Schema::hasTable('academy_rubric_criteria'))->toBeTrue();
    expect(Schema::hasTable('academy_rubric_levels'))->toBeTrue();
    expect(Schema::hasColumn('academy_submissions', 'rubric_scores'))->toBeTrue();
});
