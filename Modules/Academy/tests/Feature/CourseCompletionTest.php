<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - ACHÈVEMENT DE COURS CONFIGURABLE (« course completion » façon Moodle).
 *
 * Prouve que :
 *  - DÉFAUT « all_required » : un cours SANS config se complète comme avant (tous les
 *    items requis → 100 %) et déclenche le certificat (rétrocompatibilité stricte) ;
 *  - critère « percent » (ex. 80 %) : complété à 80 % des items requis, pas avant ;
 *  - critère « min_grade » (ex. 70 %) : complété quand la note finale du carnet ≥ 70 % ;
 *  - le CERTIFICAT et les BADGES se débloquent SELON le critère (pas à 100 % codé en dur) ;
 *  - la config est gâtée manageStructure (non-gérant → 403, anti-IDOR sur un autre cours) ;
 *  - un étudiant ne voit que SA progression (progressToward scopé serveur) ;
 *  - la migration est additive (colonne nullable) et le défaut reste inchangé.
 *
 * Autonome : helpers préfixés « v5a », aucune redéclaration d'un autre fichier.
 * Garde-fou : si le module Academy est désactivé, tous les tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Badge;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\BadgeService;
use Modules\Academy\Services\CourseCompletionService;
use Modules\Academy\Services\ProgressService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers v5a (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Cours gratuit publié minimal, avec critère d'achèvement optionnel. */
function v5aCourse(?array $criteria = null, string $slug = 'v5a-cours'): Course
{
    return Course::create([
        'slug'                => $slug,
        'title'               => 'V5a Cours',
        'language'            => 'fr-CA',
        'level'               => 'intro',
        'visibility'          => 'public',
        'access_type'         => 'free',
        'status'              => 'published',
        'currency'            => 'CAD',
        'completion_criteria' => $criteria,
    ]);
}

/** Admin academy.manage (gère tous les cours). */
function v5aAdmin(): User
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

/** Formateur owner d'un cours donné (manageStructure sur CE cours uniquement). */
function v5aOwner(Course $course): User
{
    $owner = User::factory()->create();
    $owner->assignRole('instructor');
    CourseRole::create([
        'course_id' => $course->id,
        'user_id'   => $owner->id,
        'role'      => 'owner',
    ]);

    return $owner;
}

/**
 * Crée $count items requis (1 chapitre, N leçons), retourne la liste des LessonItem.
 *
 * @return array<int, LessonItem>
 */
function v5aRequiredItems(Course $course, int $count): array
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    $items = [];
    for ($i = 1; $i <= $count; $i++) {
        $lesson = Lesson::create([
            'chapter_id' => $chapter->id,
            'title'      => "Leçon $i",
            'slug'       => "v5a-lecon-$i-{$course->id}",
            'position'   => $i,
        ]);

        $items[] = LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => 'document',
            'title'       => "Item $i",
            'position'    => 1,
            'is_required' => true,
        ]);
    }

    return $items;
}

/** Crée un item quiz (notable au carnet) dans le cours et retourne le LessonItem. */
function v5aQuizItem(Course $course): LessonItem
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre quiz',
        'position'  => 2,
    ]);
    $lesson = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon quiz',
        'slug'       => "v5a-quiz-{$course->id}",
        'position'   => 1,
    ]);

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz final',
        'position'    => 1,
        'is_required' => false,
        'payload'     => ['questions' => [['q' => 'x']]],
    ]);
}

/** Enregistre une tentative de quiz FINALISÉE à $percent % pour l'utilisateur. */
function v5aAttempt(User $user, Course $course, LessonItem $item, int $percent): QuizAttempt
{
    return QuizAttempt::create([
        'user_id'        => $user->id,
        'lesson_item_id' => $item->id,
        'course_id'      => $course->id,
        'score'          => $percent,
        'max_score'      => 100,
        'percent'        => $percent,
        'passed'         => $percent >= 60,
        'needs_grading'  => false,
        'answers'        => [],
        'submitted_at'   => now(),
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. DÉFAUT all_required : rétrocompatibilité stricte
// ─────────────────────────────────────────────────────────────────────────────

test('un cours SANS config utilise le défaut all_required (criteriaFor)', function (): void {
    $course = v5aCourse(null);

    $criteria = CourseCompletionService::criteriaFor($course);

    expect($criteria['type'])->toBe('all_required');
    expect($course->completion_criteria)->toBeNull(); // colonne additive, défaut NULL
});

test('all_required : complété quand tous les items requis le sont, pas avant', function (): void {
    $course = v5aCourse(null);
    $items  = v5aRequiredItems($course, 4);
    $user   = User::factory()->create();
    $svc    = new CourseCompletionService();

    // 3/4 complétés → pas encore.
    foreach (array_slice($items, 0, 3) as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);
    expect($svc->isComplete($user, $course))->toBeFalse();
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeFalse();

    // 4/4 complétés → complété + certificat émis (comportement historique).
    Completion::create([
        'user_id' => $user->id, 'course_id' => $course->id,
        'lesson_item_id' => $items[3]->id, 'status' => 'completed', 'completed_at' => now(),
    ]);
    ProgressService::recalculate($user, $course);

    expect($svc->isComplete($user, $course))->toBeTrue();
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. percent (80 %) : complété à 80 % des items requis, pas avant
// ─────────────────────────────────────────────────────────────────────────────

test('percent 80 % : complété à 80 % des items requis, pas à 60 %', function (): void {
    $course = v5aCourse(['type' => 'percent', 'value' => 80]);
    $items  = v5aRequiredItems($course, 5);
    $user   = User::factory()->create();
    $svc    = new CourseCompletionService();

    // 3/5 = 60 % → pas complété, pas de certificat.
    foreach (array_slice($items, 0, 3) as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);
    expect($svc->isComplete($user, $course))->toBeFalse();
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeFalse();

    // 4/5 = 80 % → complété + certificat émis AVANT 100 % (critère moindre respecté).
    Completion::create([
        'user_id' => $user->id, 'course_id' => $course->id,
        'lesson_item_id' => $items[3]->id, 'status' => 'completed', 'completed_at' => now(),
    ]);
    ProgressService::recalculate($user, $course);

    expect($svc->isComplete($user, $course))->toBeTrue();
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. min_grade (70 %) : complété quand la note finale ≥ 70 %, pas avant
// ─────────────────────────────────────────────────────────────────────────────

test('min_grade 70 % : complété quand la note du carnet ≥ 70 %, pas à 60 %', function (): void {
    $course = v5aCourse(['type' => 'min_grade', 'value' => 70]);
    $quiz   = v5aQuizItem($course);
    $user   = User::factory()->create();
    $svc    = new CourseCompletionService();

    // Note 60 % → pas complété.
    v5aAttempt($user, $course, $quiz, 60);
    expect($svc->isComplete($user, $course))->toBeFalse();

    // Une 2e tentative à 75 % (méthode défaut = highest → 75) → complété.
    v5aAttempt($user, $course, $quiz, 75);
    expect($svc->isComplete($user, $course))->toBeTrue();

    // Le certificat se débloque sur le critère de note (recalcul de progression).
    ProgressService::recalculate($user, $course);
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeTrue();
});

test('min_grade : aucune note → jamais complété (note vide ≠ 0 forcé)', function (): void {
    $course = v5aCourse(['type' => 'min_grade', 'value' => 70]);
    v5aQuizItem($course);
    $user = User::factory()->create();

    expect((new CourseCompletionService())->isComplete($user, $course))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. selected_activities : tous les items désignés doivent être complétés
// ─────────────────────────────────────────────────────────────────────────────

test('selected_activities : complété quand les items désignés sont complétés', function (): void {
    $course = v5aCourse();
    $items  = v5aRequiredItems($course, 3);
    // Désigne les 2 premiers items.
    $course->update(['completion_criteria' => ['type' => 'selected_activities', 'items' => [$items[0]->id, $items[1]->id]]]);

    $user = User::factory()->create();
    $svc  = new CourseCompletionService();

    // Un seul des deux désignés → pas complété.
    Completion::create([
        'user_id' => $user->id, 'course_id' => $course->id,
        'lesson_item_id' => $items[0]->id, 'status' => 'completed', 'completed_at' => now(),
    ]);
    ProgressService::recalculate($user, $course);
    expect($svc->isComplete($user, $course))->toBeFalse();

    // Les deux désignés complétés → complété (le 3e non désigné n'a pas d'importance).
    Completion::create([
        'user_id' => $user->id, 'course_id' => $course->id,
        'lesson_item_id' => $items[1]->id, 'status' => 'completed', 'completed_at' => now(),
    ]);
    ProgressService::recalculate($user, $course);
    expect($svc->isComplete($user, $course))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. BADGE débloqué SELON le critère (pas à 100 % codé en dur)
// ─────────────────────────────────────────────────────────────────────────────

test('badge course_completed décerné quand le critère percent est atteint (80 %, pas 100 %)', function (): void {
    $course = v5aCourse(['type' => 'percent', 'value' => 80]);
    $items  = v5aRequiredItems($course, 5);
    $user   = User::factory()->create();

    // Badge custom lié à la complétion de cours.
    $badge = Badge::create([
        'key' => 'v5a_course_done', 'name' => 'Cours terminé',
        'criteria_type' => 'course_completed', 'is_active' => true,
    ]);

    // 4/5 = 80 % → critère atteint (mais PAS 100 %).
    foreach (array_slice($items, 0, 4) as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    // recalculate() évalue déjà les badges (step 7) → le badge doit être PERSISTÉ.
    ProgressService::recalculate($user, $course);

    expect(\Modules\Academy\Models\UserBadge::where('badge_id', $badge->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

test('badge course_completed NON décerné si le critère percent n\'est pas atteint', function (): void {
    $course = v5aCourse(['type' => 'percent', 'value' => 80]);
    $items  = v5aRequiredItems($course, 5);
    $user   = User::factory()->create();

    $badge = Badge::create([
        'key' => 'v5a_course_done2', 'name' => 'Cours terminé',
        'criteria_type' => 'course_completed', 'is_active' => true,
    ]);

    // 3/5 = 60 % → sous le seuil.
    foreach (array_slice($items, 0, 3) as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);

    expect(\Modules\Academy\Models\UserBadge::where('badge_id', $badge->id)->where('user_id', $user->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. CONFIG gâtée manageStructure + anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('un non-gérant ne peut pas ouvrir l\'éditeur (403)', function (): void {
    $course = v5aCourse();
    $intrus = User::factory()->create();

    Livewire::actingAs($intrus)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

test('un gérant configure l\'achèvement de SON cours (percent), pas celui d\'un autre', function (): void {
    $courseA = v5aCourse(null, 'v5a-a');
    $courseB = v5aCourse(null, 'v5a-b');
    $ownerA  = v5aOwner($courseA);

    // Anti-escalade : owner de A ne peut pas ouvrir l'éditeur de B.
    Livewire::actingAs($ownerA)
        ->test(CourseEditor::class, ['course' => $courseB])
        ->assertForbidden();

    // Sur SON cours, il enregistre un critère percent.
    Livewire::actingAs($ownerA)
        ->test(CourseEditor::class, ['course' => $courseA])
        ->set('completion_type', 'percent')
        ->set('completion_value', 75)
        ->call('saveCompletion')
        ->assertHasNoErrors();

    expect($courseA->fresh()->completionCriteria())->toMatchArray(['type' => 'percent', 'value' => 75]);
    // Le cours B n'a jamais été touché (reste au défaut all_required).
    expect($courseB->fresh()->completionCriteria()['type'])->toBe('all_required');
});

test('selected_activities : un item d\'un AUTRE cours est rejeté (anti-IDOR)', function (): void {
    $course   = v5aCourse(null, 'v5a-idor');
    $other    = v5aCourse(null, 'v5a-other');
    $mine     = v5aRequiredItems($course, 1)[0];
    $foreign  = v5aRequiredItems($other, 1)[0];

    Livewire::actingAs(v5aAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set('completion_type', 'selected_activities')
        ->set('completion_selected', [$mine->id, $foreign->id])
        ->call('saveCompletion')
        ->assertHasNoErrors();

    // Seul l'item du cours courant est conservé (l'item étranger est filtré).
    $stored = $course->fresh()->completionCriteria();
    expect($stored['type'])->toBe('selected_activities');
    expect($stored['items'])->toBe([$mine->id]);
});

test('percent : un seuil hors bornes est rejeté (validation serveur)', function (): void {
    $course = v5aCourse();

    Livewire::actingAs(v5aAdmin())
        ->test(CourseEditor::class, ['course' => $course])
        ->set('completion_type', 'percent')
        ->set('completion_value', 0)
        ->call('saveCompletion')
        ->assertHasErrors('completion_value');

    expect($course->fresh()->completionCriteria()['type'])->toBe('all_required');
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. Un étudiant ne voit que SA progression (progressToward scopé)
// ─────────────────────────────────────────────────────────────────────────────

test('progressToward est scopé à l\'utilisateur (chacun voit SA progression)', function (): void {
    $course = v5aCourse(['type' => 'percent', 'value' => 80]);
    $items  = v5aRequiredItems($course, 5);
    $svc    = new CourseCompletionService();

    $alice = User::factory()->create();
    $bob   = User::factory()->create();

    // Alice complète 4/5 (80 %), Bob 1/5 (20 %).
    foreach (array_slice($items, 0, 4) as $item) {
        Completion::create(['user_id' => $alice->id, 'course_id' => $course->id, 'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now()]);
    }
    Completion::create(['user_id' => $bob->id, 'course_id' => $course->id, 'lesson_item_id' => $items[0]->id, 'status' => 'completed', 'completed_at' => now()]);
    ProgressService::recalculate($alice, $course);
    ProgressService::recalculate($bob, $course);

    $aliceP = $svc->progressToward($alice, $course);
    $bobP   = $svc->progressToward($bob, $course);

    expect($aliceP['complete'])->toBeTrue();
    expect((int) $aliceP['current'])->toBe(80);
    expect($bobP['complete'])->toBeFalse();
    expect((int) $bobP['current'])->toBe(20);
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. Migration additive : la colonne existe et est nullable (défaut inchangé)
// ─────────────────────────────────────────────────────────────────────────────

test('la colonne completion_criteria est additive et nullable', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasColumn('courses', 'completion_criteria'))->toBeTrue();

    // Un cours créé sans la renseigner reste NULL → défaut all_required.
    $course = v5aCourse(null, 'v5a-additive');
    expect($course->completion_criteria)->toBeNull();
    expect($course->completionCriteria()['type'])->toBe('all_required');
});
