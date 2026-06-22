<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - V2-b CARNET DE NOTES PONDÉRÉ (gradebook Moodle : catégories
 * pondérées, note finale, lettres configurables, export CSV, vue étudiant).
 *
 * Prouve, côté SERVEUR (OWASP A01) :
 *  - catégories pondérées (Quiz 40 % + Devoirs 60 %) → note finale = moyenne
 *    pondérée correcte ; normalisation des poids si Σ ≠ 100 ;
 *  - lettres : percent → bonne lettre (barème par défaut + barème personnalisé) ;
 *  - RÉTROCOMPAT : cours SANS catégorie → carnet en agrégation simple (weighted=false) ;
 *  - export CSV : inscrits + items + note finale + lettre ; gâté manageEnrollments
 *    (un editor manageStructure mais non-manageEnrollments → 403) ; anti-IDOR ;
 *  - l'étudiant voit SA note finale, jamais celle d'un autre (anti-IDOR) ;
 *  - gates : config catégories = manageStructure ; anti-IDOR cours B ;
 *  - migrations additives (tables/colonne présentes).
 *
 * Helpers PRÉFIXÉS « v2b » pour éviter toute redéclaration. SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseAssignments;
use Modules\Academy\Livewire\StudentGrades;
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
use Modules\Academy\Models\Submission;
use Modules\Academy\Services\GradebookService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    $this->courseA = v2bCourse('pond-a');
    $this->courseB = v2bCourse('pond-b');
});

function v2bCourse(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours pondéré '.$slug,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v2bOwner(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'owner']);

    return $user;
}

/** Editor : manageStructure OUI, manageEnrollments NON (gate distincte pour l'export). */
function v2bEditor(Course $course): User
{
    $user = User::factory()->create();
    $user->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $user->id, 'role' => 'editor']);

    return $user;
}

function v2bStudent(Course $course): User
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

function v2bAssignment(Course $course, array $overrides = []): Assignment
{
    return Assignment::create(array_merge([
        'course_id'    => $course->id,
        'title'        => 'Devoir pondéré',
        'instructions' => 'Faites le travail.',
        'max_points'   => 100,
        'is_published' => true,
        'position'     => 1,
    ], $overrides));
}

function v2bGradedSubmission(Assignment $assignment, User $student, int $score, User $grader): Submission
{
    return Submission::create([
        'assignment_id' => $assignment->id,
        'user_id'       => $student->id,
        'body'          => 'Réponse.',
        'submitted_at'  => now(),
        'score'         => $score,
        'graded_at'     => now(),
        'graded_by'     => $grader->id,
    ]);
}

function v2bQuizItem(Course $course): LessonItem
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    $lesson  = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz pondéré',
        'position'    => 1,
        'payload'     => ['grading_method' => 'highest', 'passing_score' => 60],
        'is_required' => false,
    ]);
}

function v2bQuizAttempt(User $student, LessonItem $item, Course $course, int $percent): void
{
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
}

function v2bCategory(Course $course, string $name, float $weight): GradeCategory
{
    return GradeCategory::create([
        'course_id' => $course->id,
        'name'      => $name,
        'weight'    => $weight,
        'position'  => GradeCategory::where('course_id', $course->id)->count() + 1,
    ]);
}

function v2bAssignItem(Course $course, string $type, int $itemId, GradeCategory $cat, float $weight = 1.0): GradeItem
{
    return GradeItem::create([
        'course_id'         => $course->id,
        'item_type'         => $type,
        'item_id'           => $itemId,
        'grade_category_id' => $cat->id,
        'weight'            => $weight,
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Note finale pondérée (Quiz 40 % + Devoirs 60 %)
// ─────────────────────────────────────────────────────────────────────────────

it('calcule la note finale = moyenne pondérée des catégories (40/60)', function (): void {
    $owner   = v2bOwner($this->courseA);
    $student = v2bStudent($this->courseA);

    $quiz = v2bQuizItem($this->courseA);
    v2bQuizAttempt($student, $quiz, $this->courseA, 80); // 80 %

    $devoir = v2bAssignment($this->courseA, ['max_points' => 100]);
    v2bGradedSubmission($devoir, $student, 60, $owner); // 60 %

    $catQuiz   = v2bCategory($this->courseA, 'Quiz', 40);
    $catDevoir = v2bCategory($this->courseA, 'Devoirs', 60);
    v2bAssignItem($this->courseA, 'quiz', $quiz->id, $catQuiz);
    v2bAssignItem($this->courseA, 'assignment', $devoir->id, $catDevoir);

    $g = GradebookService::finalGradeFor($student->fresh(), $this->courseA->fresh());

    // (80*40 + 60*60) / 100 = 68
    expect($g['hasWeighting'])->toBeTrue();
    expect($g['final'])->toBe(68.0);
    expect($g['letter'])->toBe('D'); // défaut : >=60
});

it('normalise les poids des catégories si leur somme diffère de 100', function (): void {
    $owner   = v2bOwner($this->courseA);
    $student = v2bStudent($this->courseA);

    $quiz = v2bQuizItem($this->courseA);
    v2bQuizAttempt($student, $quiz, $this->courseA, 80);

    $devoir = v2bAssignment($this->courseA, ['max_points' => 100]);
    v2bGradedSubmission($devoir, $student, 60, $owner);

    // Poids 30 + 30 (somme 60) → normalisés à 50/50.
    $catQuiz   = v2bCategory($this->courseA, 'Quiz', 30);
    $catDevoir = v2bCategory($this->courseA, 'Devoirs', 30);
    v2bAssignItem($this->courseA, 'quiz', $quiz->id, $catQuiz);
    v2bAssignItem($this->courseA, 'assignment', $devoir->id, $catDevoir);

    $g = GradebookService::finalGradeFor($student->fresh(), $this->courseA->fresh());

    // (80*30 + 60*30) / 60 = 70 (équivaut 50/50)
    expect($g['final'])->toBe(70.0);
    expect($g['letter'])->toBe('C'); // défaut : >=70
});

it('exclut une catégorie sans donnée et renormalise les poids restants', function (): void {
    $owner   = v2bOwner($this->courseA);
    $student = v2bStudent($this->courseA);

    $quiz = v2bQuizItem($this->courseA);
    v2bQuizAttempt($student, $quiz, $this->courseA, 90);

    // Devoir SANS remise → catégorie Devoirs vide → exclue.
    $devoir = v2bAssignment($this->courseA, ['max_points' => 100]);

    $catQuiz   = v2bCategory($this->courseA, 'Quiz', 40);
    $catDevoir = v2bCategory($this->courseA, 'Devoirs', 60);
    v2bAssignItem($this->courseA, 'quiz', $quiz->id, $catQuiz);
    v2bAssignItem($this->courseA, 'assignment', $devoir->id, $catDevoir);

    $g = GradebookService::finalGradeFor($student->fresh(), $this->courseA->fresh());

    // Seule la catégorie Quiz a une donnée → note finale = 90.
    expect($g['final'])->toBe(90.0);
    expect($g['letter'])->toBe('A');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Lettres configurables
// ─────────────────────────────────────────────────────────────────────────────

it('attribue la bonne lettre selon le barème par défaut', function (): void {
    expect(GradebookService::letterFor(95))->toBe('A');
    expect(GradebookService::letterFor(85))->toBe('B');
    expect(GradebookService::letterFor(72))->toBe('C');
    expect(GradebookService::letterFor(61))->toBe('D');
    expect(GradebookService::letterFor(40))->toBe('E');
});

it('respecte un barème de lettres personnalisé', function (): void {
    $scheme = [
        ['letter' => 'Réussite', 'min' => 50],
        ['letter' => 'Échec', 'min' => 0],
    ];

    expect(GradebookService::letterFor(60, $scheme))->toBe('Réussite');
    expect(GradebookService::letterFor(49, $scheme))->toBe('Échec');
});

it('utilise le barème stocké sur le cours, sinon le défaut', function (): void {
    $owner = v2bOwner($this->courseA);

    // Sans barème stocké → défaut.
    expect(GradebookService::letterSchemeFor($this->courseA->fresh()))
        ->toBe(GradebookService::defaultLetterScheme());

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->set('letterBands', [
            ['letter' => 'P', 'min' => '50'],
            ['letter' => 'F', 'min' => '0'],
        ])
        ->call('saveLetterScheme');

    $scheme = GradebookService::letterSchemeFor($this->courseA->fresh());
    expect($scheme[0]['letter'])->toBe('P');
    expect(GradebookService::letterFor(55, $scheme))->toBe('P');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Rétrocompat : cours sans catégorie → agrégation simple inchangée
// ─────────────────────────────────────────────────────────────────────────────

it('garde le carnet en agrégation simple quand aucune catégorie n\'existe', function (): void {
    $owner   = v2bOwner($this->courseA);
    $student = v2bStudent($this->courseA);
    $devoir  = v2bAssignment($this->courseA, ['title' => 'Devoir simple', 'max_points' => 20]);
    v2bGradedSubmission($devoir, $student, 17, $owner);

    $gb = Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('toggleGradebook')
        ->get('gradebook');

    expect($gb['weighted'])->toBeFalse();

    $row = collect($gb['students'])->firstWhere('user.id', $student->id);
    expect($row['final'])->toBeNull();
    expect($row['cells']->first())->toBe(17); // colonne devoir inchangée
});

it('finalGradeFor renvoie hasWeighting=false sans catégorie (rétrocompat)', function (): void {
    $student = v2bStudent($this->courseA);

    $g = GradebookService::finalGradeFor($student->fresh(), $this->courseA->fresh());
    expect($g['hasWeighting'])->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. Export CSV
// ─────────────────────────────────────────────────────────────────────────────

it('exporte un CSV avec inscrits, items, note finale et lettre', function (): void {
    $owner   = v2bOwner($this->courseA);
    $student = v2bStudent($this->courseA);

    $quiz = v2bQuizItem($this->courseA);
    v2bQuizAttempt($student, $quiz, $this->courseA, 80);
    $devoir = v2bAssignment($this->courseA, ['max_points' => 100]);
    v2bGradedSubmission($devoir, $student, 60, $owner);

    $catQuiz   = v2bCategory($this->courseA, 'Quiz', 40);
    $catDevoir = v2bCategory($this->courseA, 'Devoirs', 60);
    v2bAssignItem($this->courseA, 'quiz', $quiz->id, $catQuiz);
    v2bAssignItem($this->courseA, 'assignment', $devoir->id, $catDevoir);

    $csv = GradebookService::buildCsv($this->courseA->fresh());

    expect($csv)->toContain("\xEF\xBB\xBF");          // BOM UTF-8
    expect($csv)->toContain($student->name);
    expect($csv)->toContain('Note finale');
    expect($csv)->toContain('Lettre');
    expect($csv)->toContain('68');                    // note finale
    expect($csv)->toContain('D');                     // lettre

    // L'action Livewire renvoie bien un téléchargement (gâté manageEnrollments OK).
    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('exportGradebookCsv')
        ->assertFileDownloaded();
});

it('interdit l\'export à un editor (manageStructure mais non manageEnrollments)', function (): void {
    $editor = v2bEditor($this->courseA);

    Livewire::actingAs($editor)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->call('exportGradebookCsv')
        ->assertForbidden();
});

it('scope l\'export CSV au cours (anti-IDOR : pas de fuite d\'un autre cours)', function (): void {
    $ownerA   = v2bOwner($this->courseA);
    $studentA = v2bStudent($this->courseA);

    v2bOwner($this->courseB);
    $studentB = v2bStudent($this->courseB);

    $csv = GradebookService::buildCsv($this->courseA->fresh());

    expect($csv)->toContain($studentA->name);
    expect($csv)->not->toContain($studentB->name);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Vue étudiant : voit SA note, pas celle des autres
// ─────────────────────────────────────────────────────────────────────────────

it('montre à l\'étudiant SA note finale uniquement (anti-IDOR)', function (): void {
    v2bOwner($this->courseA);
    $studentX = v2bStudent($this->courseA);
    $studentY = v2bStudent($this->courseA);

    $quiz = v2bQuizItem($this->courseA);
    v2bQuizAttempt($studentX, $quiz, $this->courseA, 90);
    v2bQuizAttempt($studentY, $quiz, $this->courseA, 50);

    $cat = v2bCategory($this->courseA, 'Quiz', 100);
    v2bAssignItem($this->courseA, 'quiz', $quiz->id, $cat);

    // X voit 90 (A), pas la note de Y.
    $gradesX = Livewire::actingAs($studentX)
        ->test(StudentGrades::class)
        ->get('grades');
    expect($gradesX->first()['final'])->toBe(90.0);
    expect($gradesX->first()['letter'])->toBe('A');

    // Y voit 50 (E), indépendamment.
    $gradesY = Livewire::actingAs($studentY)
        ->test(StudentGrades::class)
        ->get('grades');
    expect($gradesY->first()['final'])->toBe(50.0);
});

it('n\'affiche pas de note finale étudiant pour un cours non pondéré', function (): void {
    v2bOwner($this->courseA);
    $student = v2bStudent($this->courseA);
    $devoir  = v2bAssignment($this->courseA);
    v2bGradedSubmission($devoir, $student, 80, $student);

    $grades = Livewire::actingAs($student)
        ->test(StudentGrades::class)
        ->get('grades');

    expect($grades)->toHaveCount(0); // aucun cours pondéré
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. Gates + anti-IDOR de configuration
// ─────────────────────────────────────────────────────────────────────────────

it('permet à un gérant (manageStructure) de créer une catégorie', function (): void {
    $owner = v2bOwner($this->courseA);

    Livewire::actingAs($owner)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->set('newCategoryName', 'Examens')
        ->set('newCategoryWeight', '50')
        ->call('addCategory')
        ->assertHasNoErrors();

    expect(GradeCategory::where('course_id', $this->courseA->id)->where('name', 'Examens')->exists())->toBeTrue();
});

it('interdit le montage de la config à un non-gérant (403)', function (): void {
    $student = v2bStudent($this->courseA);

    Livewire::actingAs($student)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->assertForbidden();
});

it('empêche d\'éditer une catégorie d\'un autre cours (anti-IDOR)', function (): void {
    $ownerA = v2bOwner($this->courseA);
    v2bOwner($this->courseB);
    $catB = v2bCategory($this->courseB, 'Cat B', 50);

    expect(function () use ($ownerA, $catB): void {
        Livewire::actingAs($ownerA)
            ->test(CourseAssignments::class, ['course' => $this->courseA])
            ->call('editCategory', $catB->id);
    })->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

it('ignore l\'affectation d\'un item d\'un autre cours (anti-IDOR)', function (): void {
    $ownerA = v2bOwner($this->courseA);
    $catA   = v2bCategory($this->courseA, 'Cat A', 100);

    v2bOwner($this->courseB);
    $foreignDevoir = v2bAssignment($this->courseB, ['title' => 'Devoir B']);

    Livewire::actingAs($ownerA)
        ->test(CourseAssignments::class, ['course' => $this->courseA])
        ->set('itemCategoryMap', ['assignment_'.$foreignDevoir->id => (string) $catA->id])
        ->set('itemWeightMap', ['assignment_'.$foreignDevoir->id => '1'])
        ->call('saveItemAssignments');

    // Aucun GradeItem créé pour l'item étranger.
    expect(GradeItem::where('item_type', 'assignment')->where('item_id', $foreignDevoir->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. Migrations additives
// ─────────────────────────────────────────────────────────────────────────────

it('a créé les tables/colonne additives V2-b', function (): void {
    expect(Schema::hasTable('academy_grade_categories'))->toBeTrue();
    expect(Schema::hasTable('academy_grade_items'))->toBeTrue();
    expect(Schema::hasColumn('courses', 'grade_letter_scheme'))->toBeTrue();
});
