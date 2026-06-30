<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - V2-c ACHÈVEMENT D'ACTIVITÉ CONFIGURABLE (parité Moodle « activity
 * completion »). Prouve, de façon AUTONOME (helpers préfixés v2c) :
 *
 *  - le CRITÈRE est lu depuis payload['completion'] avec un défaut PAR TYPE
 *    (quiz → min_grade, vidéo/document → manual) ; rétrocompat stricte ;
 *  - « manual » : item non complété tant que pas de clic ; clic → complété ;
 *  - « view »   : la consultation par un inscrit auto-complète l'item (idempotent) ;
 *                 un non-inscrit / une prévisualisation ne crée AUCUNE complétion ;
 *  - « min_grade » (quiz) : réussi → complété ; échoué → NON complété ;
 *  - la progression (ProgressService) reflète les complétions ;
 *  - la configuration est gâtée manageStructure (non-gérant 403, anti-IDOR cours B) ;
 *  - aucune dépendance nouvelle, aucune table : tout vit dans payload (JSON).
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Question;
use Modules\Academy\Models\QuestionCategory;
use Modules\Academy\Services\ActivityCompletionService;
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
// Helpers (préfixe v2c - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v2cCourse(string $slug = 'cours-v2c'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V2-c',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v2cLesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.$chapter->id,
        'position'   => 1,
    ]);
}

function v2cItem(Lesson $lesson, string $type = 'document', array $payload = [], bool $required = false, int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => $type,
        'title'       => 'Élément '.$position,
        'position'    => $position,
        'payload'     => $payload,
        'is_required' => $required,
    ]);
}

function v2cStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function v2cOwner(Course $course): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function v2cEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v2cShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

function v2cIsCompleted(User $user, LessonItem $item): bool
{
    return Completion::where('user_id', $user->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE - critère effectif + défauts par type + rétrocompat
// ─────────────────────────────────────────────────────────────────────────────

test('défaut par type : quiz → min_grade, vidéo/document → manual (item sans clé)', function (): void {
    $lesson = v2cLesson(v2cCourse());
    $video  = v2cItem($lesson, 'video');
    $doc    = v2cItem($lesson, 'document', [], false, 2);
    $quiz   = v2cItem($lesson, 'quiz', [], false, 3);

    expect(ActivityCompletionService::criterionFor($video))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($doc))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($quiz))->toBe('min_grade');
});

test('valeur stockée valide est respectée ; valeur interdite pour le type retombe sur le défaut', function (): void {
    $lesson = v2cLesson(v2cCourse());

    $viewDoc = v2cItem($lesson, 'document', ['completion' => 'view']);
    expect(ActivityCompletionService::criterionFor($viewDoc))->toBe('view');

    // min_grade n'est PAS autorisé sur une vidéo → retombe sur le défaut du type (manual).
    $badVideo = v2cItem($lesson, 'video', ['completion' => 'min_grade'], false, 2);
    expect(ActivityCompletionService::criterionFor($badVideo))->toBe('manual');

    // Valeur inconnue → défaut.
    $junk = v2cItem($lesson, 'document', ['completion' => 'whatever'], false, 3);
    expect(ActivityCompletionService::criterionFor($junk))->toBe('manual');
});

test('normalizeForStorage : null si vide/invalide/égal au défaut, sinon la valeur', function (): void {
    // Égal au défaut du type → absent (rétrocompat).
    expect(ActivityCompletionService::normalizeForStorage('document', 'manual'))->toBeNull();
    expect(ActivityCompletionService::normalizeForStorage('quiz', 'min_grade'))->toBeNull();
    // Vide / invalide → null.
    expect(ActivityCompletionService::normalizeForStorage('document', ''))->toBeNull();
    expect(ActivityCompletionService::normalizeForStorage('video', 'min_grade'))->toBeNull();
    // Valeur non-défaut valide → conservée.
    expect(ActivityCompletionService::normalizeForStorage('document', 'view'))->toBe('view');
    expect(ActivityCompletionService::normalizeForStorage('quiz', 'manual'))->toBe('manual');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. MANUAL (défaut vidéo/document) - clic obligatoire (comportement actuel)
// ─────────────────────────────────────────────────────────────────────────────

test('manual : un document n\'est pas complété tant que l\'étudiant ne clique pas ; clic → complété', function (): void {
    $course  = v2cCourse('cours-manual');
    $lesson  = v2cLesson($course);
    $item    = v2cItem($lesson, 'document', ['rich_text' => 'Notes'], true);
    $student = v2cStudent();
    v2cEnroll($course, $student);

    // La simple consultation ne complète PAS un item manual.
    $this->actingAs($student)->get(v2cShowUrl($course, $lesson))->assertOk();
    expect(v2cIsCompleted($student, $item))->toBeFalse();

    // Le clic « Marquer comme terminé » complète.
    $this->actingAs($student)
        ->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/complete")
        ->assertRedirect();

    expect(v2cIsCompleted($student, $item))->toBeTrue();
});

test('manual : un POST manuel sur un item « view » est rejeté (achèvement automatique)', function (): void {
    $course  = v2cCourse('cours-manual-rejet');
    $lesson  = v2cLesson($course);
    $item    = v2cItem($lesson, 'document', ['completion' => 'view', 'rich_text' => 'x']);
    $student = v2cStudent();
    v2cEnroll($course, $student);

    $this->actingAs($student)
        ->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/complete")
        ->assertSessionHas('error');
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. VIEW - auto-complétion à la consultation (inscrit), sécurisée
// ─────────────────────────────────────────────────────────────────────────────

test('view : la consultation par un inscrit auto-complète l\'item (idempotent : 2 vues = 1 complétion)', function (): void {
    $course  = v2cCourse('cours-view');
    $lesson  = v2cLesson($course);
    $item    = v2cItem($lesson, 'document', ['completion' => 'view', 'rich_text' => 'Lis-moi'], true);
    $student = v2cStudent();
    v2cEnroll($course, $student);

    $this->actingAs($student)->get(v2cShowUrl($course, $lesson))->assertOk();
    expect(v2cIsCompleted($student, $item))->toBeTrue();

    // Deuxième vue : pas de doublon.
    $this->actingAs($student)->get(v2cShowUrl($course, $lesson))->assertOk();
    expect(Completion::where('user_id', $student->id)->where('lesson_item_id', $item->id)->count())->toBe(1);
});

test('view : un NON-inscrit ne déclenche AUCUNE complétion', function (): void {
    $course = v2cCourse('cours-view-anon');
    $lesson = v2cLesson($course);
    $item   = v2cItem($lesson, 'document', ['completion' => 'view', 'rich_text' => 'x']);
    $user   = v2cStudent(); // existe mais N'EST PAS inscrit

    $this->actingAs($user)->get(v2cShowUrl($course, $lesson))->assertOk();
    expect(Completion::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('view : la prévisualisation d\'un gérant ne crée AUCUNE complétion', function (): void {
    $course = v2cCourse('cours-view-preview');
    $lesson = v2cLesson($course);
    $item   = v2cItem($lesson, 'document', ['completion' => 'view', 'rich_text' => 'x']);
    $owner  = v2cOwner($course); // gérant, jamais inscrit

    $this->actingAs($owner)->get(v2cShowUrl($course, $lesson).'?preview=1')->assertOk();
    expect(Completion::where('lesson_item_id', $item->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. MIN_GRADE (quiz) - réussi → complété ; échoué → NON complété
// ─────────────────────────────────────────────────────────────────────────────

/** Remplit une catégorie de N questions truefalse dont la bonne réponse = Vrai (index 0). */
function v2cFillTrueFalse(QuestionCategory $cat, int $n): void
{
    for ($i = 0; $i < $n; $i++) {
        Question::create([
            'category_id' => $cat->id,
            'owner_id'    => $cat->owner_id,
            'type'        => 'truefalse',
            'prompt'      => "Affirmation #$i (vraie)",
            'payload'     => ['answer' => true],
            'difficulty'  => 'facile',
            'is_active'   => true,
        ]);
    }
}

test('min_grade : un quiz réussi est complété', function (): void {
    $course = v2cCourse('cours-mg-pass');
    $lesson = v2cLesson($course);
    $owner  = v2cOwner($course);
    $cat    = QuestionCategory::create(['owner_id' => $owner->id, 'parent_id' => null, 'name' => 'Banque', 'position' => 0]);
    v2cFillTrueFalse($cat, 4);

    $item = v2cItem($lesson, 'quiz', [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ], true);

    $student = v2cStudent();
    v2cEnroll($course, $student);

    $this->actingAs($student)->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start");
    $round   = session("academy.quiz.{$item->id}")['questions'] ?? [];
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 0; // « Vrai » = bonne réponse
    }

    $this->actingAs($student)
        ->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit", ['answers' => $answers])
        ->assertRedirect();

    expect(v2cIsCompleted($student, $item))->toBeTrue();
});

test('min_grade : un quiz échoué n\'est PAS complété (même après tentative)', function (): void {
    $course = v2cCourse('cours-mg-fail');
    $lesson = v2cLesson($course);
    $owner  = v2cOwner($course);
    $cat    = QuestionCategory::create(['owner_id' => $owner->id, 'parent_id' => null, 'name' => 'Banque', 'position' => 0]);
    v2cFillTrueFalse($cat, 4);

    $item = v2cItem($lesson, 'quiz', [
        'question_bank' => ['category_id' => $cat->id, 'draw_count' => 4],
        'passing_score' => 60,
    ], true);

    $student = v2cStudent();
    v2cEnroll($course, $student);

    $this->actingAs($student)->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/start");
    $round   = session("academy.quiz.{$item->id}")['questions'] ?? [];
    $answers = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 1; // « Faux » = mauvaise réponse
    }

    $this->actingAs($student)
        ->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/quiz/submit", ['answers' => $answers])
        ->assertRedirect();

    expect(v2cIsCompleted($student, $item))->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. PROGRESSION - reflète les complétions selon les critères
// ─────────────────────────────────────────────────────────────────────────────

test('la progression reflète une auto-complétion « view » d\'un item requis', function (): void {
    $course = v2cCourse('cours-progress');
    $lesson = v2cLesson($course);
    // 2 items requis : un « view » (auto), un « manual » (non complété).
    $viewItem   = v2cItem($lesson, 'document', ['completion' => 'view', 'rich_text' => 'a'], true, 1);
    $manualItem = v2cItem($lesson, 'document', ['rich_text' => 'b'], true, 2);

    $student = v2cStudent();
    v2cEnroll($course, $student);

    $this->actingAs($student)->get(v2cShowUrl($course, $lesson))->assertOk();

    $progress = ProgressService::recalculate($student, $course->fresh());
    expect($progress->required_total)->toBe(2);
    expect($progress->required_completed)->toBe(1); // seul le « view » est auto-complété
    expect($progress->percent)->toBe(50);
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. CONFIGURATION - éditeur (gâté manageStructure) + anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant peut configurer le critère « view » sur un item ; un défaut n\'écrit aucune clé', function (): void {
    $course = v2cCourse('cours-config');
    $lesson = v2cLesson($course);
    $item   = v2cItem($lesson, 'document', ['rich_text' => 'x']);
    $owner  = v2cOwner($course);

    // Choix « view » → stocké.
    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'document', 'Doc', null, ['completion' => 'view', 'rich_text' => 'x'])
        ->assertHasNoErrors();

    expect($item->fresh()->payload['completion'] ?? null)->toBe('view');

    // Retour au défaut « manual » (document) → la clé est RETIRÉE (rétrocompat).
    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'document', 'Doc', null, ['completion' => 'manual', 'rich_text' => 'x'])
        ->assertHasNoErrors();

    expect(array_key_exists('completion', $item->fresh()->payload))->toBeFalse();
});

test('un critère hors liste blanche est refusé à l\'édition', function (): void {
    $course = v2cCourse('cours-config-bad');
    $lesson = v2cLesson($course);
    $item   = v2cItem($lesson, 'document', ['rich_text' => 'x']);
    $owner  = v2cOwner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'document', 'Doc', null, ['completion' => 'malware', 'rich_text' => 'x'])
        ->assertHasErrors('completion');
});

test('ANTI-IDOR : configurer le critère d\'un item d\'un AUTRE cours est refusé', function (): void {
    $courseA = v2cCourse('cours-a');
    $courseB = v2cCourse('cours-b');
    $lessonB = v2cLesson($courseB);
    $itemB   = v2cItem($lessonB, 'document', ['rich_text' => 'x']);
    $ownerA  = v2cOwner($courseA);

    $component = Livewire::actingAs($ownerA)->test(CourseEditor::class, ['course' => $courseA]);

    expect(fn () => $component->call('updateItem', $itemB->id, 'document', 'Piraté', null, ['completion' => 'view', 'rich_text' => 'x']))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

    expect(array_key_exists('completion', $itemB->fresh()->payload))->toBeFalse();
});

test('non-gérant : impossible de configurer le critère (éditeur interdit)', function (): void {
    $course  = v2cCourse('cours-config-403');
    $lesson  = v2cLesson($course);
    v2cItem($lesson, 'document', ['rich_text' => 'x']);
    $student = v2cStudent();

    Livewire::actingAs($student)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// B02b — Journal d'activité idempotent : N markComplete → 1 seule entrée Spatie
//
// Prouve que CompletionService::markComplete n'écrit qu'UNE SEULE entrée dans
// activity_log quelle que soit la séquence d'appels (markStarted + markComplete ×N).
// Fix B02b : guard wasRecentlyCreated || wasChanged('status') dans markComplete.
// ─────────────────────────────────────────────────────────────────────────────

test('B02b — markComplete N fois sur le même item = 1 seule entrée activity_log', function (): void {
    $course  = v2cCourse('cours-b02b');
    $lesson  = v2cLesson($course);
    $item    = v2cItem($lesson, 'document', ['completion' => 'view', 'rich_text' => 'x'], true);
    $student = v2cStudent();
    v2cEnroll($course, $student);

    $item->loadMissing(['lesson.chapter.course']);

    // Appels multiples : markStarted puis markComplete × 3.
    \Modules\Academy\Services\CompletionService::markStarted($student, $item);
    \Modules\Academy\Services\CompletionService::markComplete($student, $item);
    \Modules\Academy\Services\CompletionService::markComplete($student, $item);
    \Modules\Academy\Services\CompletionService::markComplete($student, $item);

    // 1 seule entrée dans activity_log pour ce couple (user, item).
    $logCount = \Spatie\Activitylog\Models\Activity::query()
        ->where('causer_id', $student->id)
        ->where('causer_type', get_class($student))
        ->where('subject_id', $item->id)
        ->where('subject_type', get_class($item))
        ->where('description', 'academy.item.completed')
        ->count();

    expect($logCount)->toBe(1);

    // 1 seule complétion en base (idempotence DB déjà garantie par contrainte unique).
    expect(\Modules\Academy\Models\Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->count()
    )->toBe(1);
});
