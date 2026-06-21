<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Devoirs (assignments) + remises + correction + carnet de notes
 * (Phase E / E2, évaluation).
 *
 * Prouve, côté SERVEUR (OWASP A01) :
 *  - un gérant crée un devoir PUBLIÉ → un inscrit ACTIF le voit et le soumet →
 *    le gérant le corrige (note + feedback) → l'étudiant voit sa note ;
 *  - un BROUILLON n'est JAMAIS visible d'un étudiant ;
 *  - un non-gérant ne crée pas / ne corrige pas (403) ;
 *  - un étudiant ne soumet QUE pour LUI (user_id forcé = auth) ;
 *  - ANTI-IDOR : agir sur le devoir / la remise d'un AUTRE cours est refusé ;
 *  - une note hors bornes [0..max_points] est REJETÉE ;
 *  - ANTI-XSS : consignes/feedback avec <script> neutralisés ;
 *  - migration additive (la table existe après migration).
 *
 * Helpers PRÉFIXÉS « e2 » pour éviter toute redéclaration avec les autres suites.
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Livewire\StudentAssignments;
use Modules\Academy\Models\Assignment;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Submission;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->courseA = e2MakeCourse('devoir-a', 'Cours A');
    $this->courseB = e2MakeCourse('devoir-b', 'Cours B');
});

/** Helper : crée un cours gratuit publié minimal. */
function e2MakeCourse(string $slug, string $title): Course
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

/** Helper : owner (formateur) d'un cours donné. */
function e2MakeOwner(Course $course): User
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

/** Helper : étudiant inscrit ACTIF à un cours donné. */
function e2MakeEnrolledStudent(Course $course): User
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

/** Helper : devoir d'un cours (publié par défaut). */
function e2MakeAssignment(Course $course, array $overrides = []): Assignment
{
    return Assignment::create(array_merge([
        'course_id'    => $course->id,
        'title'        => 'Devoir 1',
        'instructions' => 'Faites le travail.',
        'max_points'   => 100,
        'is_published' => true,
        'position'     => 1,
    ], $overrides));
}

// ─────────────────────────────────────────────────────────────────────────────
// Parcours complet : créer → voir → soumettre → corriger → voir sa note
// ─────────────────────────────────────────────────────────────────────────────

it('déroule le cycle complet devoir → remise → correction → note', function (): void {
    $owner   = e2MakeOwner($this->courseA);
    $student = e2MakeEnrolledStudent($this->courseA);

    // 1) Le gérant crée un devoir PUBLIÉ.
    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->set('title', 'Analyse de cas')
        ->set('instructions', 'Analysez le cas fourni.')
        ->set('maxPoints', 20)
        ->call('saveAssignment', true);

    $assignment = Assignment::where('course_id', $this->courseA->id)->first();
    expect($assignment)->not->toBeNull();
    expect($assignment->is_published)->toBeTrue();
    expect($assignment->max_points)->toBe(20);

    // 2) L'inscrit actif le voit dans son espace et soumet sa remise.
    Livewire::actingAs($student)
        ->test(StudentAssignments::class)
        ->assertSee('Analyse de cas')
        ->call('openSubmission', $assignment->id)
        ->set('body', 'Voici mon analyse.')
        ->call('submit');

    $submission = Submission::where('assignment_id', $assignment->id)
        ->where('user_id', $student->id)
        ->first();
    expect($submission)->not->toBeNull();
    expect($submission->body)->toBe('Voici mon analyse.');
    expect($submission->score)->toBeNull();

    // 3) Le gérant corrige (note + feedback).
    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('reviewAssignment', $assignment->id)
        ->call('startGrading', $submission->id)
        ->set('gradeScore', '18')
        ->set('gradeFeedback', 'Très bien.')
        ->call('gradeSubmission');

    $submission->refresh();
    expect($submission->score)->toBe(18);
    expect($submission->graded_at)->not->toBeNull();
    expect($submission->graded_by)->toBe($owner->id);

    // 4) L'étudiant voit sa note + feedback.
    Livewire::actingAs($student)
        ->test(StudentAssignments::class)
        ->assertSee('18')
        ->assertSee('Très bien.');
});

it('garde un brouillon de devoir invisible des étudiants', function (): void {
    e2MakeOwner($this->courseA);
    $student = e2MakeEnrolledStudent($this->courseA);

    e2MakeAssignment($this->courseA, [
        'title'        => 'Devoir secret',
        'is_published' => false,
    ]);

    Livewire::actingAs($student)
        ->test(StudentAssignments::class)
        ->assertDontSee('Devoir secret');
});

// ─────────────────────────────────────────────────────────────────────────────
// Sécurité : gates manageStructure / manageEnrollments
// ─────────────────────────────────────────────────────────────────────────────

it('interdit le montage du gérant à un non-gérant (403)', function (): void {
    $student = e2MakeEnrolledStudent($this->courseA);

    Livewire::actingAs($student)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->assertForbidden();
});

it('empêche un étudiant de soumettre pour un devoir d\'un cours non suivi (anti-IDOR)', function (): void {
    e2MakeOwner($this->courseB);
    $foreign = e2MakeAssignment($this->courseB, ['title' => 'Devoir B']);

    // Étudiant inscrit au cours A uniquement.
    $studentA = e2MakeEnrolledStudent($this->courseA);

    expect(function () use ($studentA, $foreign): void {
        Livewire::actingAs($studentA)
            ->test(StudentAssignments::class)
            ->call('openSubmission', $foreign->id);
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Submission::where('assignment_id', $foreign->id)->count())->toBe(0);
});

it('force user_id = auth sur la remise (un étudiant ne soumet que pour lui)', function (): void {
    e2MakeOwner($this->courseA);
    $assignment = e2MakeAssignment($this->courseA);

    $studentX = e2MakeEnrolledStudent($this->courseA);
    $studentY = e2MakeEnrolledStudent($this->courseA);

    Livewire::actingAs($studentX)
        ->test(StudentAssignments::class)
        ->call('openSubmission', $assignment->id)
        ->set('body', 'Réponse de X.')
        ->call('submit');

    $submission = Submission::where('assignment_id', $assignment->id)->first();
    // La remise appartient à X (auth), jamais à Y.
    expect($submission->user_id)->toBe($studentX->id);
    expect($submission->user_id)->not->toBe($studentY->id);
});

it('empêche un gérant d\'agir sur le devoir d\'un autre cours (anti-IDOR)', function (): void {
    $ownerA  = e2MakeOwner($this->courseA);
    e2MakeOwner($this->courseB);
    $foreign = e2MakeAssignment($this->courseB, ['title' => 'Devoir B']);

    expect(function () use ($ownerA, $foreign): void {
        Livewire::actingAs($ownerA)
            ->test(CourseAssignments::class, ['course' => $this->courseA])
            ->call('deleteAssignment', $foreign->id);
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(Assignment::find($foreign->id))->not->toBeNull();
});

it('empêche un gérant de corriger une remise d\'un autre cours (anti-IDOR)', function (): void {
    $ownerA  = e2MakeOwner($this->courseA);
    e2MakeOwner($this->courseB);
    $foreignAssignment = e2MakeAssignment($this->courseB);
    $studentB          = e2MakeEnrolledStudent($this->courseB);

    $foreignSubmission = Submission::create([
        'assignment_id' => $foreignAssignment->id,
        'user_id'       => $studentB->id,
        'body'          => 'Remise B.',
        'submitted_at'  => now(),
    ]);

    expect(function () use ($ownerA, $foreignSubmission): void {
        Livewire::actingAs($ownerA)
            ->test(CourseAssignments::class, ['course' => $this->courseA])
            ->call('startGrading', $foreignSubmission->id);
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Note hors bornes rejetée
// ─────────────────────────────────────────────────────────────────────────────

it('rejette une note supérieure au maximum du devoir', function (): void {
    $owner   = e2MakeOwner($this->courseA);
    $student = e2MakeEnrolledStudent($this->courseA);
    $assignment = e2MakeAssignment($this->courseA, ['max_points' => 10]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'Réponse.',
        'submitted_at'  => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('reviewAssignment', $assignment->id)
        ->call('startGrading', $submission->id)
        ->set('gradeScore', '50') // > max_points (10)
        ->call('gradeSubmission')
        ->assertHasErrors('gradeScore');

    $submission->refresh();
    // Rien écrit : la remise reste non corrigée.
    expect($submission->score)->toBeNull();
    expect($submission->graded_at)->toBeNull();
});

it('rejette une note négative', function (): void {
    $owner   = e2MakeOwner($this->courseA);
    $student = e2MakeEnrolledStudent($this->courseA);
    $assignment = e2MakeAssignment($this->courseA, ['max_points' => 10]);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'Réponse.',
        'submitted_at'  => now(),
    ]);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('reviewAssignment', $assignment->id)
        ->call('startGrading', $submission->id)
        ->set('gradeScore', '-5')
        ->call('gradeSubmission')
        ->assertHasErrors('gradeScore');

    $submission->refresh();
    expect($submission->score)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// Une remise corrigée n'est plus éditable par l'étudiant (verrou serveur)
// ─────────────────────────────────────────────────────────────────────────────

it('empêche l\'étudiant de modifier une remise déjà corrigée', function (): void {
    e2MakeOwner($this->courseA);
    $student = e2MakeEnrolledStudent($this->courseA);
    $assignment = e2MakeAssignment($this->courseA);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'Originale.',
        'submitted_at'  => now(),
        'score'         => 15,
        'feedback'      => 'OK.',
        'graded_at'     => now(),
        'graded_by'     => $student->id, // valeur quelconque, le verrou se base sur graded_at+score
    ]);

    Livewire::actingAs($student)
        ->test(StudentAssignments::class)
        ->call('openSubmission', $assignment->id)
        ->set('body', 'Tentative de triche.')
        ->call('submit')
        ->assertHasErrors('body');

    $submission->refresh();
    expect($submission->body)->toBe('Originale.');
});

// ─────────────────────────────────────────────────────────────────────────────
// Carnet de notes (gradebook)
// ─────────────────────────────────────────────────────────────────────────────

it('affiche le carnet de notes scopé au cours', function (): void {
    $owner   = e2MakeOwner($this->courseA);
    $student = e2MakeEnrolledStudent($this->courseA);
    $assignment = e2MakeAssignment($this->courseA, ['title' => 'Devoir noté', 'max_points' => 20]);

    Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'Réponse.',
        'submitted_at'  => now(),
        'score'         => 17,
        'graded_at'     => now(),
        'graded_by'     => $owner->id,
    ]);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('toggleGradebook')
        ->assertSee('Devoir noté')
        ->assertSee('17')
        ->assertSee($student->name);
});

// ─────────────────────────────────────────────────────────────────────────────
// Anti-XSS : consignes / feedback contenant <script> neutralisés
// ─────────────────────────────────────────────────────────────────────────────

it('neutralise le HTML brut des consignes (anti-XSS)', function (): void {
    $assignment = e2MakeAssignment($this->courseA, [
        'instructions' => "Faites <script>alert('xss')</script> le **travail**",
    ]);

    $html = $assignment->renderedInstructions();

    expect($html)->not->toContain('<script>');
    expect($html)->not->toContain('</script>');
    expect($html)->toContain('<strong>travail</strong>');
});

it('neutralise le HTML brut du feedback (anti-XSS)', function (): void {
    $student = e2MakeEnrolledStudent($this->courseA);
    $assignment = e2MakeAssignment($this->courseA);

    $submission = Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'Réponse.',
        'submitted_at'  => now(),
        'feedback'      => "Bien <script>alert('xss')</script> **joué**",
        'score'         => 10,
        'graded_at'     => now(),
    ]);

    $html = $submission->renderedFeedback();

    expect($html)->not->toContain('<script>');
    expect($html)->toContain('<strong>joué</strong>');
});

// ─────────────────────────────────────────────────────────────────────────────
// Migrations additives (les tables existent après migration)
// ─────────────────────────────────────────────────────────────────────────────

it('a créé les tables additives academy_assignments et academy_submissions', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasTable('academy_assignments'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('academy_submissions'))->toBeTrue();
});
