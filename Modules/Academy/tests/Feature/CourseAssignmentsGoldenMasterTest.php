<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * TESTS GOLDEN-MASTER / CARACTÉRISATION — CourseAssignments (God-component).
 *
 * OBJECTIF : FIGER le comportement ACTUEL des 5 groupes cohésifs AVANT
 * toute extraction en traits. Ces tests décrivent CE QUI EST. Si un
 * comportement paraît surprenant, il est figé tel quel (commentaire
 * CARACTÉRISATION ou BIZARRERIE) et jamais corrigé ici.
 *
 * GROUPES COUVERTS :
 *  CA. CRUD devoirs         → HandlesAssignmentCrud
 *  SG. Correction remises   → HandlesSubmissionGrading
 *  RB. Grille (rubric)      → HandlesRubric
 *  GS. Carnet pondéré       → HandlesGradeStructure
 *  SC. Échelles             → HandlesScales
 *
 * CONVENTION : tous les helpers portent le préfixe « gmCA_ » pour éviter
 * toute redéclaration dans les autres suites Pest.
 * GARDE-FOU : module Academy désactivé → tous les tests SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\GradeCategory;
use Modules\Academy\Models\RubricCriterion;
use Modules\Academy\Models\RubricLevel;
use Modules\Academy\Models\Scale;
use Modules\Academy\Models\Submission;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe gmCA_ — évite collision avec les autres suites Pest)
// ─────────────────────────────────────────────────────────────────────────────

function gmCA_makeCourse(string $slug = 'cours-gm', string $title = 'Cours GM'): Course
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

/** Crée un admin avec toutes les permissions Academy. */
function gmCA_makeAdmin(): User
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

/** Crée un owner du cours (manageStructure + manageEnrollments). */
function gmCA_makeOwner(Course $course): User
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

/** Crée un inscrit actif dans le cours. */
function gmCA_makeStudent(Course $course): User
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

/** Crée un devoir minimal attaché au cours. */
function gmCA_makeAssignment(Course $course, array $attrs = []): Assignment
{
    static $pos = 0;
    $pos++;

    return Assignment::create(array_merge([
        'course_id'   => $course->id,
        'title'       => 'Devoir GM '.$pos,
        'max_points'  => 100,
        'is_published'=> true,
        'position'    => $pos,
    ], $attrs));
}

/** Crée une remise (submission) pour un devoir. */
function gmCA_makeSubmission(Assignment $assignment, User $student, array $attrs = []): Submission
{
    return Submission::create(array_merge([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'submitted_at'  => now(),
    ], $attrs));
}

/** Crée une échelle simple à 2 niveaux (Échec | Réussite). */
function gmCA_makeScale(User $owner, string $name = 'Échelle GM'): Scale
{
    return Scale::create([
        'owner_id' => $owner->id,
        'name'     => $name,
        'slug'     => \Illuminate\Support\Str::slug($name).'-'.rand(100, 999),
        'items'    => [
            ['label' => 'Échec',    'value' => 1.0],
            ['label' => 'Réussite', 'value' => 2.0],
        ],
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// Bootstrap commun à tous les tests
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->course  = gmCA_makeCourse('cours-gm-a', 'Cours GM A');
    $this->courseB = gmCA_makeCourse('cours-gm-b', 'Cours GM B');
    $this->owner   = gmCA_makeOwner($this->course);
});

// ═════════════════════════════════════════════════════════════════════════════
// CA. CRUD DEVOIRS — HandlesAssignmentCrud
// ═════════════════════════════════════════════════════════════════════════════

it('CA1 : crée un devoir brouillon via saveAssignment(false)', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->set('title', 'Mon premier devoir')
        ->set('instructions', 'Consignes du devoir.')
        ->set('maxPoints', 50)
        ->call('saveAssignment', false);

    $a = Assignment::where('course_id', $this->course->id)->first();
    expect($a)->not->toBeNull();
    expect($a->title)->toBe('Mon premier devoir');
    expect($a->max_points)->toBe(50);
    expect($a->is_published)->toBeFalse();
});

it('CA2 : crée un devoir PUBLIÉ via saveAssignment(true)', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->set('title', 'Devoir publié')
        ->set('maxPoints', 20)
        ->call('saveAssignment', true);

    $a = Assignment::where('course_id', $this->course->id)->first();
    expect($a)->not->toBeNull();
    expect($a->is_published)->toBeTrue();
});

it('CA3 : saveAssignment réinitialise le formulaire après création', function (): void {
    $this->actingAs($this->owner);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->set('title', 'Devoir tmp')
        ->set('maxPoints', 30)
        ->call('saveAssignment', false);

    $comp->assertSet('title', '');
    $comp->assertSet('editingAssignment', null);
    $comp->assertSet('maxPoints', 100); // valeur par défaut
});

it('CA4 : editAssignment charge le devoir dans le formulaire', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course, ['title' => 'À éditer', 'max_points' => 77]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editAssignment', $a->id)
        ->assertSet('editingAssignment', $a->id)
        ->assertSet('title', 'À éditer')
        ->assertSet('maxPoints', 77);
});

it('CA5 : saveAssignment en mode édition met à jour le devoir', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course, ['title' => 'Ancien titre', 'max_points' => 40]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editAssignment', $a->id)
        ->set('title', 'Nouveau titre')
        ->set('maxPoints', 80)
        ->call('saveAssignment', false);

    $a->refresh();
    expect($a->title)->toBe('Nouveau titre');
    expect($a->max_points)->toBe(80);
});

it('CA6 : publishAssignment passe un brouillon en publié', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course, ['is_published' => false]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('publishAssignment', $a->id);

    expect($a->fresh()?->is_published)->toBeTrue();
});

it('CA7 : unpublishAssignment repasse un devoir publié en brouillon', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course, ['is_published' => true]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('unpublishAssignment', $a->id);

    expect($a->fresh()?->is_published)->toBeFalse();
});

it('CA8 : deleteAssignment supprime le devoir', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('deleteAssignment', $a->id);

    expect(Assignment::find($a->id))->toBeNull();
});

it('CA9 : deleteAssignment remet le formulaire à zéro si le devoir édité est supprimé', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course, ['title' => 'À supprimer']);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editAssignment', $a->id)
        ->call('deleteAssignment', $a->id);

    $comp->assertSet('editingAssignment', null);
    $comp->assertSet('title', '');
});

it('CA10 : resetAssignmentForm vide les champs et annule editingAssignment', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editAssignment', $a->id)
        ->call('resetAssignmentForm');

    $comp->assertSet('editingAssignment', null);
    $comp->assertSet('title', '');
    $comp->assertSet('maxPoints', 100);
});

it('CA11 : confirmAssignmentRemoval et cancelAssignmentRemoval basculent la prop', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('confirmAssignmentRemoval', $a->id)
        ->assertSet('confirmingAssignmentRemoval', $a->id)
        ->call('cancelAssignmentRemoval')
        ->assertSet('confirmingAssignmentRemoval', null);
});

it('CA12 : saveAssignment rejette un titre vide (validation)', function (): void {
    $this->actingAs($this->owner);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->set('title', '')
        ->set('maxPoints', 10)
        ->call('saveAssignment', false);

    $comp->assertHasErrors('title');
    expect(Assignment::where('course_id', $this->course->id)->count())->toBe(0);
});

it('CA13 : editAssignment anti-IDOR — devoir d\'un autre cours → ModelNotFound', function (): void {
    $this->actingAs($this->owner);
    $foreign = gmCA_makeAssignment($this->courseB, ['title' => 'Étranger']);

    // CARACTÉRISATION : ModelNotFoundException (firstOrFail scopé cours) — non converti en 404.
    expect(fn () =>
        Livewire::test(CourseAssignments::class, ['course' => $this->course])
            ->call('editAssignment', $foreign->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

// ═════════════════════════════════════════════════════════════════════════════
// SG. CORRECTION REMISES — HandlesSubmissionGrading
// ═════════════════════════════════════════════════════════════════════════════

it('SG1 : reviewAssignment ouvre le panneau de remises', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $a->id)
        ->assertSet('reviewingAssignment', $a->id);
});

it('SG2 : closeReview ferme le panneau de remises', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $a->id)
        ->call('closeReview')
        ->assertSet('reviewingAssignment', null);
});

it('SG3 : startGrading charge une remise dans le formulaire de correction', function (): void {
    $this->actingAs($this->owner);
    $a       = gmCA_makeAssignment($this->course, ['max_points' => 50]);
    $student = gmCA_makeStudent($this->course);
    $sub     = gmCA_makeSubmission($a, $student, ['score' => 35, 'feedback' => 'Bien']);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $a->id)
        ->call('startGrading', $sub->id)
        ->assertSet('gradingSubmission', $sub->id)
        ->assertSet('gradeScore', '35')
        ->assertSet('gradeFeedback', 'Bien');
});

it('SG4 : cancelGrading vide le formulaire de correction', function (): void {
    $this->actingAs($this->owner);
    $a       = gmCA_makeAssignment($this->course);
    $student = gmCA_makeStudent($this->course);
    $sub     = gmCA_makeSubmission($a, $student);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $a->id)
        ->call('startGrading', $sub->id)
        ->call('cancelGrading');

    $comp->assertSet('gradingSubmission', null);
    $comp->assertSet('gradeScore', '');
    $comp->assertSet('gradeFeedback', '');
});

it('SG5 : gradeSubmission enregistre note + feedback + graded_at/by', function (): void {
    $this->actingAs($this->owner);
    $a       = gmCA_makeAssignment($this->course, ['max_points' => 100]);
    $student = gmCA_makeStudent($this->course);
    $sub     = gmCA_makeSubmission($a, $student);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $a->id)
        ->call('startGrading', $sub->id)
        ->set('gradeScore', '75')
        ->set('gradeFeedback', 'Excellent travail')
        ->call('gradeSubmission');

    $sub->refresh();
    expect($sub->score)->toBe(75);
    expect($sub->feedback)->toBe('Excellent travail');
    expect($sub->graded_at)->not->toBeNull();
    expect($sub->graded_by)->toBe($this->owner->id);
});

it('SG6 : gradeSubmission ferme le formulaire après correction', function (): void {
    $this->actingAs($this->owner);
    $a       = gmCA_makeAssignment($this->course, ['max_points' => 100]);
    $student = gmCA_makeStudent($this->course);
    $sub     = gmCA_makeSubmission($a, $student);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $a->id)
        ->call('startGrading', $sub->id)
        ->set('gradeScore', '60')
        ->call('gradeSubmission');

    $comp->assertSet('gradingSubmission', null);
});

it('SG7 : gradeSubmission rejette une note supérieure à max_points', function (): void {
    $this->actingAs($this->owner);
    $a       = gmCA_makeAssignment($this->course, ['max_points' => 20]);
    $student = gmCA_makeStudent($this->course);
    $sub     = gmCA_makeSubmission($a, $student);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $a->id)
        ->call('startGrading', $sub->id)
        ->set('gradeScore', '999')
        ->call('gradeSubmission');

    $comp->assertHasErrors('gradeScore');
    expect($sub->fresh()?->graded_at)->toBeNull();
});

it('SG8 : gradeSubmission rejette une note négative', function (): void {
    $this->actingAs($this->owner);
    $a       = gmCA_makeAssignment($this->course, ['max_points' => 100]);
    $student = gmCA_makeStudent($this->course);
    $sub     = gmCA_makeSubmission($a, $student);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('reviewAssignment', $a->id)
        ->call('startGrading', $sub->id)
        ->set('gradeScore', '-5')
        ->call('gradeSubmission');

    $comp->assertHasErrors('gradeScore');
});

it('SG9 : startGrading anti-IDOR — remise d\'un autre cours → ModelNotFound', function (): void {
    $this->actingAs($this->owner);
    $aForeign = gmCA_makeAssignment($this->courseB);
    $student  = gmCA_makeStudent($this->courseB);
    $subFgn   = gmCA_makeSubmission($aForeign, $student);

    // CARACTÉRISATION : ModelNotFoundException (firstOrFail scopé cours) — non converti en 404.
    expect(fn () =>
        Livewire::test(CourseAssignments::class, ['course' => $this->course])
            ->call('startGrading', $subFgn->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

// ═════════════════════════════════════════════════════════════════════════════
// RB. GRILLE D'ÉVALUATION (RUBRIC) — HandlesRubric
// ═════════════════════════════════════════════════════════════════════════════

it('RB1 : openRubric ouvre le panneau grille du devoir', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('openRubric', $a->id)
        ->assertSet('rubricAssignment', $a->id);
});

it('RB2 : closeRubric ferme le panneau grille', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('openRubric', $a->id)
        ->call('closeRubric')
        ->assertSet('rubricAssignment', null);
});

it('RB3 : addCriterion crée un critère sur le devoir ouvert', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('openRubric', $a->id)
        ->set('newCriterion', 'Clarté de l\'argumentation')
        ->call('addCriterion');

    $c = RubricCriterion::where('assignment_id', $a->id)->first();
    expect($c)->not->toBeNull();
    expect($c->description)->toBe('Clarté de l\'argumentation');
});

it('RB4 : addCriterion vide le champ après ajout', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('openRubric', $a->id)
        ->set('newCriterion', 'Critère test')
        ->call('addCriterion');

    $comp->assertSet('newCriterion', '');
});

it('RB5 : editCriterion → saveCriterion met à jour le libellé', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);
    $c = RubricCriterion::create([
        'assignment_id' => $a->id,
        'description'   => 'Ancien libellé',
        'position'      => 1,
    ]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editCriterion', $c->id)
        ->assertSet('editingCriterion', $c->id)
        ->assertSet('criterionDescription', 'Ancien libellé')
        ->set('criterionDescription', 'Nouveau libellé')
        ->call('saveCriterion');

    expect($c->fresh()?->description)->toBe('Nouveau libellé');
});

it('RB6 : cancelCriterionEdit annule l\'édition du critère', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);
    $c = RubricCriterion::create(['assignment_id' => $a->id, 'description' => 'X', 'position' => 1]);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editCriterion', $c->id)
        ->call('cancelCriterionEdit');

    $comp->assertSet('editingCriterion', null);
    $comp->assertSet('criterionDescription', '');
});

it('RB7 : confirmCriterionRemoval → deleteCriterion supprime le critère', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);
    $c = RubricCriterion::create(['assignment_id' => $a->id, 'description' => 'À supprimer', 'position' => 1]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('confirmCriterionRemoval', $c->id)
        ->assertSet('confirmingCriterionRemoval', $c->id)
        ->call('deleteCriterion', $c->id);

    expect(RubricCriterion::find($c->id))->toBeNull();
});

it('RB8 : startAddLevel → addLevel crée un niveau sur le critère', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);
    $c = RubricCriterion::create(['assignment_id' => $a->id, 'description' => 'Critère', 'position' => 1]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('openRubric', $a->id)
        ->call('startAddLevel', $c->id)
        ->assertSet('addingLevelTo', $c->id)
        ->set('levelDescription', 'Excellent')
        ->set('levelPoints', '30')
        ->call('addLevel');

    $lvl = RubricLevel::where('criterion_id', $c->id)->first();
    expect($lvl)->not->toBeNull();
    expect($lvl->description)->toBe('Excellent');
    expect($lvl->points)->toBe(30);
});

it('RB9 : cancelAddLevel annule l\'ajout d\'un niveau', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);
    $c = RubricCriterion::create(['assignment_id' => $a->id, 'description' => 'C', 'position' => 1]);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('openRubric', $a->id)
        ->call('startAddLevel', $c->id)
        ->call('cancelAddLevel');

    $comp->assertSet('addingLevelTo', null);
    $comp->assertSet('levelDescription', '');
    $comp->assertSet('levelPoints', '');
});

it('RB10 : editLevel → saveLevel met à jour un niveau', function (): void {
    $this->actingAs($this->owner);
    $a   = gmCA_makeAssignment($this->course);
    $c   = RubricCriterion::create(['assignment_id' => $a->id, 'description' => 'C', 'position' => 1]);
    $lvl = RubricLevel::create(['criterion_id' => $c->id, 'description' => 'Bien', 'points' => 10, 'position' => 1]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editLevel', $lvl->id)
        ->assertSet('editingLevel', $lvl->id)
        ->set('levelDescription', 'Très bien')
        ->set('levelPoints', '20')
        ->call('saveLevel');

    $lvl->refresh();
    expect($lvl->description)->toBe('Très bien');
    expect($lvl->points)->toBe(20);
});

it('RB11 : confirmLevelRemoval → deleteLevel supprime le niveau', function (): void {
    $this->actingAs($this->owner);
    $a   = gmCA_makeAssignment($this->course);
    $c   = RubricCriterion::create(['assignment_id' => $a->id, 'description' => 'C', 'position' => 1]);
    $lvl = RubricLevel::create(['criterion_id' => $c->id, 'description' => 'L', 'points' => 5, 'position' => 1]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('confirmLevelRemoval', $lvl->id)
        ->assertSet('confirmingLevelRemoval', $lvl->id)
        ->call('deleteLevel', $lvl->id);

    expect(RubricLevel::find($lvl->id))->toBeNull();
});

it('RB12 : addCriterion rejette un libellé vide', function (): void {
    $this->actingAs($this->owner);
    $a = gmCA_makeAssignment($this->course);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('openRubric', $a->id)
        ->set('newCriterion', '')
        ->call('addCriterion');

    $comp->assertHasErrors('newCriterion');
    expect(RubricCriterion::where('assignment_id', $a->id)->count())->toBe(0);
});

// ═════════════════════════════════════════════════════════════════════════════
// GS. CARNET PONDÉRÉ — HandlesGradeStructure
// ═════════════════════════════════════════════════════════════════════════════

it('GS1 : toggleGradeStructure ouvre le panneau de pondération', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->assertSet('showGradeStructure', false)
        ->call('toggleGradeStructure')
        ->assertSet('showGradeStructure', true);
});

it('GS2 : toggleGradeStructure ferme le panneau s\'il était ouvert', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('toggleGradeStructure')
        ->call('toggleGradeStructure')
        ->assertSet('showGradeStructure', false);
});

it('GS3 : addCategory crée une catégorie liée au cours', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('toggleGradeStructure')
        ->set('newCategoryName', 'Travaux pratiques')
        ->set('newCategoryWeight', '40')
        ->call('addCategory');

    $cat = GradeCategory::where('course_id', $this->course->id)->first();
    expect($cat)->not->toBeNull();
    expect($cat->name)->toBe('Travaux pratiques');
    expect((float) $cat->weight)->toBe(40.0);
});

it('GS4 : addCategory réinitialise le formulaire après ajout', function (): void {
    $this->actingAs($this->owner);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('toggleGradeStructure')
        ->set('newCategoryName', 'Examens')
        ->set('newCategoryWeight', '60')
        ->call('addCategory');

    $comp->assertSet('newCategoryName', '');
    $comp->assertSet('newCategoryWeight', '');
});

it('GS5 : editCategory → saveCategory met à jour la catégorie', function (): void {
    $this->actingAs($this->owner);
    $cat = GradeCategory::create([
        'course_id' => $this->course->id,
        'name'      => 'Ancienne catégorie',
        'weight'    => 30.0,
        'position'  => 1,
    ]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editCategory', $cat->id)
        ->assertSet('editingCategory', $cat->id)
        ->set('editCategoryName', 'Nouvelle catégorie')
        ->set('editCategoryWeight', '50')
        ->call('saveCategory');

    $cat->refresh();
    expect($cat->name)->toBe('Nouvelle catégorie');
    expect((float) $cat->weight)->toBe(50.0);
});

it('GS6 : cancelCategoryEdit annule l\'édition de catégorie', function (): void {
    $this->actingAs($this->owner);
    $cat = GradeCategory::create([
        'course_id' => $this->course->id,
        'name'      => 'Cat test',
        'weight'    => 20.0,
        'position'  => 1,
    ]);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editCategory', $cat->id)
        ->call('cancelCategoryEdit');

    $comp->assertSet('editingCategory', null);
    $comp->assertSet('editCategoryName', '');
    $comp->assertSet('editCategoryWeight', '');
});

it('GS7 : confirmCategoryRemoval → deleteCategory supprime la catégorie', function (): void {
    $this->actingAs($this->owner);
    $cat = GradeCategory::create([
        'course_id' => $this->course->id,
        'name'      => 'À supprimer',
        'weight'    => 25.0,
        'position'  => 1,
    ]);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('confirmCategoryRemoval', $cat->id)
        ->assertSet('confirmingCategoryRemoval', $cat->id)
        ->call('deleteCategory', $cat->id);

    expect(GradeCategory::find($cat->id))->toBeNull();
});

it('GS8 : addLetterBand ajoute une bande au barème (non persistée)', function (): void {
    $this->actingAs($this->owner);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('addLetterBand');

    $bands = $comp->get('letterBands');
    expect(count($bands))->toBe(1);
    expect($bands[0]['letter'])->toBe('');
    expect($bands[0]['min'])->toBe('0');
});

it('GS9 : removeLetterBand retire la bande à l\'index donné', function (): void {
    $this->actingAs($this->owner);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('addLetterBand')
        ->call('addLetterBand')
        ->call('removeLetterBand', 0);

    expect(count($comp->get('letterBands')))->toBe(1);
});

it('GS10 : saveLetterScheme persiste le barème de lettres dans le cours', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('addLetterBand')
        ->set('letterBands.0.letter', 'A')
        ->set('letterBands.0.min', '85')
        ->call('saveLetterScheme');

    $this->course->refresh();
    $scheme = $this->course->grade_letter_scheme;
    expect($scheme)->not->toBeNull();

    $arr = is_string($scheme) ? json_decode($scheme, true) : (array) $scheme;
    expect(count($arr))->toBe(1);
    expect($arr[0]['letter'])->toBe('A');
});

it('GS11 : toggleGradebook bascule showGradebook', function (): void {
    $this->actingAs($this->owner);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->assertSet('showGradebook', false)
        ->call('toggleGradebook')
        ->assertSet('showGradebook', true)
        ->call('toggleGradebook')
        ->assertSet('showGradebook', false);
});

it('GS12 : addCategory rejette un nom vide', function (): void {
    $this->actingAs($this->owner);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('toggleGradeStructure')
        ->set('newCategoryName', '')
        ->set('newCategoryWeight', '30')
        ->call('addCategory');

    $comp->assertHasErrors('newCategoryName');
    expect(GradeCategory::where('course_id', $this->course->id)->count())->toBe(0);
});

// ═════════════════════════════════════════════════════════════════════════════
// SC. ÉCHELLES PERSONNALISÉES — HandlesScales
// ═════════════════════════════════════════════════════════════════════════════

it('SC1 : toggleScales ouvre le panneau des échelles', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->assertSet('showScales', false)
        ->call('toggleScales')
        ->assertSet('showScales', true);
});

it('SC2 : toggleScales ferme le panneau s\'il était ouvert', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('toggleScales')
        ->call('toggleScales')
        ->assertSet('showScales', false);
});

it('SC3 : addScale crée une échelle possédée par l\'utilisateur courant', function (): void {
    $this->actingAs($this->owner);

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('toggleScales')
        ->set('newScaleName', 'Mon échelle')
        ->set('newScaleItems', "Insuffisant | 1\nSuffisant | 2\nExcellent | 3")
        ->call('addScale');

    $scale = Scale::where('owner_id', $this->owner->id)->first();
    expect($scale)->not->toBeNull();
    expect($scale->name)->toBe('Mon échelle');
    expect(count((array) $scale->items))->toBe(3);
});

it('SC4 : addScale rejette une échelle avec un seul niveau', function (): void {
    $this->actingAs($this->owner);

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('toggleScales')
        ->set('newScaleName', 'Invalide')
        ->set('newScaleItems', "Seul niveau | 1")
        ->call('addScale');

    $comp->assertHasErrors('newScaleItems');
    expect(Scale::where('owner_id', $this->owner->id)->count())->toBe(0);
});

it('SC5 : editScale → saveScale met à jour le nom de l\'échelle', function (): void {
    $this->actingAs($this->owner);
    $scale = gmCA_makeScale($this->owner, 'Échelle avant');

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editScale', $scale->id)
        ->assertSet('editingScale', $scale->id)
        ->set('editScaleName', 'Échelle après')
        ->call('saveScale');

    expect($scale->fresh()?->name)->toBe('Échelle après');
});

it('SC6 : cancelScaleEdit annule l\'édition de l\'échelle', function (): void {
    $this->actingAs($this->owner);
    $scale = gmCA_makeScale($this->owner, 'Échelle x');

    $comp = Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('editScale', $scale->id)
        ->call('cancelScaleEdit');

    $comp->assertSet('editingScale', null);
    $comp->assertSet('editScaleName', '');
    $comp->assertSet('editScaleItems', '');
});

it('SC7 : confirmScaleRemoval → deleteScale supprime l\'échelle', function (): void {
    $this->actingAs($this->owner);
    $scale = gmCA_makeScale($this->owner, 'À supprimer');

    Livewire::test(CourseAssignments::class, ['course' => $this->course])
        ->call('confirmScaleRemoval', $scale->id)
        ->assertSet('confirmingScaleRemoval', $scale->id)
        ->call('deleteScale', $scale->id);

    expect(Scale::find($scale->id))->toBeNull();
});

it('SC8 : editScale anti-IDOR — éditer l\'échelle d\'un autre owner → ModelNotFound', function (): void {
    $this->actingAs($this->owner);
    $other    = gmCA_makeOwner($this->courseB);
    $foreign  = gmCA_makeScale($other, 'Étrangère');

    // CARACTÉRISATION : ModelNotFoundException (firstOrFail owner-scopé) — non converti en 404.
    expect(fn () =>
        Livewire::test(CourseAssignments::class, ['course' => $this->course])
            ->call('editScale', $foreign->id)
    )->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('SC9 : selectableScales renvoie les échelles de l\'owner + les échelles système', function (): void {
    $this->actingAs($this->owner);

    // Échelle de cet owner.
    $mine = gmCA_makeScale($this->owner, 'À moi');
    // Échelle système (owner_id null).
    Scale::create([
        'owner_id' => null,
        'name'     => 'Système',
        'slug'     => 'systeme-sc9',
        'items'    => [['label' => 'N1', 'value' => 1.0], ['label' => 'N2', 'value' => 2.0]],
    ]);
    // Échelle d'un autre owner (NE DOIT PAS apparaître).
    $other   = gmCA_makeOwner($this->courseB);
    gmCA_makeScale($other, 'Pas à moi');

    $comp   = Livewire::test(CourseAssignments::class, ['course' => $this->course]);
    $scales = $comp->get('selectableScales');

    expect($scales)->toHaveCount(2);
    $names = collect($scales)->pluck('name')->all();
    expect($names)->toContain('À moi');
    expect($names)->toContain('Système');
    expect($names)->not->toContain('Pas à moi');
});
