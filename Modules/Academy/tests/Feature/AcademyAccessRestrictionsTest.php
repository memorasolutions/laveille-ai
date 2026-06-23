<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - V5-d RESTRICTIONS D'ACCES PAR ITEM (parité Moodle « Restrict access »).
 *
 * Prouve, de façon AUTONOME (helpers préfixés v5d_) :
 *
 *  - condition DATE : avant from (bloqué), après from (autorisé), après until (bloqué) ;
 *  - condition GRADE : note insuffisante (bloqué), note suffisante (autorisé) ;
 *  - condition COMPLETION : item de référence non complété (bloqué), complété (autorisé) ;
 *  - hide=true : item absent de la liste ; hide=false : item grisé avec raison ;
 *  - match=all (ET) : UNE condition non remplie → bloqué ;
 *  - match=any (OU) : AU MOINS UNE condition remplie → autorisé ;
 *  - ANTI-IDOR : item_id d'un autre cours → rejeté (condition ignorée → permissive) ;
 *  - accès direct URL bloqué 403 sur POST /complete ;
 *  - rétrocompat : item sans la clé access_restrictions → toujours accessible ;
 *  - LessonController passe $itemRestrictions à la vue (inscrit, pas preview) ;
 *  - saveItemRestrictions Livewire : ANTI-IDOR item_id autre cours rejeté.
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Cohort;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\AccessRestrictionService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers autonomes (préfixe v5d_ - aucune collision inter-fichiers)
// ─────────────────────────────────────────────────────────────────────────────

function v5d_course(string $slug = 'cours-v5d'): Course
{
    static $counter = 0;
    $counter++;

    return Course::create([
        'slug'        => $slug.'-'.$counter,
        'title'       => 'Cours V5-d '.$counter,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v5d_lesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => 'lecon-'.uniqid(),
        'position'   => 1,
    ]);
}

function v5d_item(Lesson $lesson, array $payload = [], string $type = 'document', int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => $type,
        'title'     => 'Élément '.$position,
        'position'  => $position,
        'payload'   => $payload,
    ]);
}

function v5d_student(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function v5d_owner(Course $course): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function v5d_enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v5d_complete(User $user, LessonItem $item, Course $course): void
{
    Completion::firstOrCreate(
        ['user_id' => $user->id, 'lesson_item_id' => $item->id],
        ['course_id' => $course->id, 'status' => 'completed', 'completed_at' => now()]
    );
}

/** Restriction d'accès basique autour d'une condition unique. */
function v5d_payload(array $condition, string $match = 'all'): array
{
    return [
        'access_restrictions' => [
            'match'      => $match,
            'conditions' => [$condition],
        ],
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. RETROCOMPAT : item sans restriction = toujours accessible
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : item sans access_restrictions → allowed=true, hidden=false, reasons=[]', function (): void {
    $course  = v5d_course('retrocompat');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, []);
    $student = v5d_student();

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeTrue();
    expect($result['hidden'])->toBeFalse();
    expect($result['reasons'])->toBeEmpty();
});

test('rétrocompat : access_restrictions présent mais conditions vide → toujours accessible', function (): void {
    $course = v5d_course('retrocompat-vide');
    $lesson = v5d_lesson($course);
    $item   = v5d_item($lesson, [
        'access_restrictions' => ['match' => 'all', 'conditions' => []],
    ]);
    $student = v5d_student();

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CONDITION DATE
// ─────────────────────────────────────────────────────────────────────────────

test('date : avant la date from → bloqué', function (): void {
    $course  = v5d_course('date-avant');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, v5d_payload([
        'type' => 'date',
        'from' => now()->addDays(5)->toIso8601String(),
        'hide' => false,
    ]));
    $student = v5d_student();

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeFalse();
    expect($result['hidden'])->toBeFalse();
    expect($result['reasons'])->not->toBeEmpty();
});

test('date : après la date from → autorisé', function (): void {
    $course  = v5d_course('date-apres');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, v5d_payload([
        'type' => 'date',
        'from' => now()->subDay()->toIso8601String(),
        'hide' => false,
    ]));
    $student = v5d_student();

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeTrue();
});

test('date : après la date until → bloqué', function (): void {
    $course  = v5d_course('date-until');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, v5d_payload([
        'type'  => 'date',
        'until' => now()->subDay()->toIso8601String(),
        'hide'  => false,
    ]));
    $student = v5d_student();

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeFalse();
    expect($result['reasons'])->not->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. CONDITION GRADE
// ─────────────────────────────────────────────────────────────────────────────

test('grade : note insuffisante → bloqué', function (): void {
    $course  = v5d_course('grade-insuff');
    $lesson  = v5d_lesson($course);
    $quiz    = v5d_item($lesson, [], 'quiz', 1);
    $student = v5d_student();
    v5d_enroll($course, $student);

    // Tentative avec 50 % (min = 70 %)
    QuizAttempt::create([
        'user_id'        => $student->id,
        'lesson_item_id' => $quiz->id,
        'course_id'      => $course->id,
        'percent'        => 50,
        'passed'         => false,
        'needs_grading'  => false,
        'answers'        => [],
        'score'          => 5,
        'max_score'      => 10,
        'submitted_at'   => now(),
    ]);

    $target = v5d_item($lesson, v5d_payload([
        'type'        => 'grade',
        'item_id'     => $quiz->id,
        'min_percent' => 70,
        'hide'        => false,
    ]), 'document', 2);

    $result = AccessRestrictionService::evaluate($student, $target, $course);

    expect($result['allowed'])->toBeFalse();
    expect($result['reasons'][0])->toContain('70');
});

test('grade : note suffisante → autorisé', function (): void {
    $course  = v5d_course('grade-suff');
    $lesson  = v5d_lesson($course);
    $quiz    = v5d_item($lesson, [], 'quiz', 1);
    $student = v5d_student();
    v5d_enroll($course, $student);

    // Tentative avec 85 % (min = 70 %)
    QuizAttempt::create([
        'user_id'        => $student->id,
        'lesson_item_id' => $quiz->id,
        'course_id'      => $course->id,
        'percent'        => 85,
        'passed'         => true,
        'needs_grading'  => false,
        'answers'        => [],
        'score'          => 17,
        'max_score'      => 20,
        'submitted_at'   => now(),
    ]);

    $target = v5d_item($lesson, v5d_payload([
        'type'        => 'grade',
        'item_id'     => $quiz->id,
        'min_percent' => 70,
        'hide'        => false,
    ]), 'document', 2);

    $result = AccessRestrictionService::evaluate($student, $target, $course);

    expect($result['allowed'])->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. CONDITION COMPLETION
// ─────────────────────────────────────────────────────────────────────────────

test('completion : item de référence non complété → bloqué', function (): void {
    $course  = v5d_course('completion-non');
    $lesson  = v5d_lesson($course);
    $ref     = v5d_item($lesson, [], 'document', 1);
    $student = v5d_student();
    v5d_enroll($course, $student);

    $target = v5d_item($lesson, v5d_payload([
        'type'    => 'completion',
        'item_id' => $ref->id,
        'hide'    => false,
    ]), 'document', 2);

    $result = AccessRestrictionService::evaluate($student, $target, $course);

    expect($result['allowed'])->toBeFalse();
    expect($result['reasons'][0])->toContain($ref->title);
});

test('completion : item de référence complété → autorisé', function (): void {
    $course  = v5d_course('completion-oui');
    $lesson  = v5d_lesson($course);
    $ref     = v5d_item($lesson, [], 'document', 1);
    $student = v5d_student();
    v5d_enroll($course, $student);
    v5d_complete($student, $ref, $course);

    $target = v5d_item($lesson, v5d_payload([
        'type'    => 'completion',
        'item_id' => $ref->id,
        'hide'    => false,
    ]), 'document', 2);

    $result = AccessRestrictionService::evaluate($student, $target, $course);

    expect($result['allowed'])->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. hide=true vs hide=false
// ─────────────────────────────────────────────────────────────────────────────

test('hide=false : item bloqué → allowed=false, hidden=false (grisé avec raison)', function (): void {
    $course  = v5d_course('hide-false');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, v5d_payload([
        'type' => 'date',
        'from' => now()->addDays(10)->toIso8601String(),
        'hide' => false,
    ]));
    $student = v5d_student();

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeFalse();
    expect($result['hidden'])->toBeFalse();
    expect($result['reasons'])->not->toBeEmpty();
});

test('hide=true : item bloqué → allowed=false, hidden=true (absent de la liste)', function (): void {
    $course  = v5d_course('hide-true');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, v5d_payload([
        'type' => 'date',
        'from' => now()->addDays(10)->toIso8601String(),
        'hide' => true,
    ]));
    $student = v5d_student();

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeFalse();
    expect($result['hidden'])->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. match=all (ET) vs match=any (OU)
// ─────────────────────────────────────────────────────────────────────────────

test('match=all : une condition non remplie parmi deux → bloqué (ET)', function (): void {
    $course  = v5d_course('match-all');
    $lesson  = v5d_lesson($course);
    $ref     = v5d_item($lesson, [], 'document', 1);
    $student = v5d_student();
    v5d_enroll($course, $student);
    v5d_complete($student, $ref, $course);  // condition completion remplie

    // Deux conditions : completion OK + date future KO
    $item = v5d_item($lesson, [
        'access_restrictions' => [
            'match'      => 'all',
            'conditions' => [
                ['type' => 'completion', 'item_id' => $ref->id, 'hide' => false],
                ['type' => 'date', 'from' => now()->addDays(3)->toIso8601String(), 'hide' => false],
            ],
        ],
    ], 'document', 2);

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeFalse();
});

test('match=any : au moins une condition remplie → autorisé (OU)', function (): void {
    $course  = v5d_course('match-any');
    $lesson  = v5d_lesson($course);
    $ref     = v5d_item($lesson, [], 'document', 1);
    $student = v5d_student();
    v5d_enroll($course, $student);
    v5d_complete($student, $ref, $course);  // condition completion remplie

    // Deux conditions : completion OK + date future KO → OU = autorisé
    $item = v5d_item($lesson, [
        'access_restrictions' => [
            'match'      => 'any',
            'conditions' => [
                ['type' => 'completion', 'item_id' => $ref->id, 'hide' => false],
                ['type' => 'date', 'from' => now()->addDays(3)->toIso8601String(), 'hide' => false],
            ],
        ],
    ], 'document', 2);

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeTrue();
});

test('match=any : aucune condition remplie → bloqué (OU)', function (): void {
    $course  = v5d_course('match-any-aucune');
    $lesson  = v5d_lesson($course);
    $ref     = v5d_item($lesson, [], 'document', 1);
    $student = v5d_student();
    v5d_enroll($course, $student);
    // ref NON complété

    $item = v5d_item($lesson, [
        'access_restrictions' => [
            'match'      => 'any',
            'conditions' => [
                ['type' => 'completion', 'item_id' => $ref->id, 'hide' => false],
                ['type' => 'date', 'from' => now()->addDays(3)->toIso8601String(), 'hide' => false],
            ],
        ],
    ], 'document', 2);

    $result = AccessRestrictionService::evaluate($student, $item, $course);

    expect($result['allowed'])->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. ANTI-IDOR : item_id d'un autre cours → rejeté (condition ignorée, permissive)
// ─────────────────────────────────────────────────────────────────────────────

test('anti-idor : item_id appartenant à un autre cours → condition ignorée → permissive', function (): void {
    $courseA  = v5d_course('idor-cours-a');
    $courseB  = v5d_course('idor-cours-b');
    $lessonA  = v5d_lesson($courseA);
    $lessonB  = v5d_lesson($courseB);
    $itemExtB = v5d_item($lessonB, [], 'document', 1);  // appartient au cours B
    $student  = v5d_student();
    v5d_enroll($courseA, $student);

    // L'item du cours A référence un item du cours B → anti-IDOR : ignoré → permissif
    $target = v5d_item($lessonA, v5d_payload([
        'type'    => 'completion',
        'item_id' => $itemExtB->id,  // AUTRE cours
        'hide'    => false,
    ]), 'document', 2);

    $result = AccessRestrictionService::evaluate($student, $target, $courseA);

    expect($result['allowed'])->toBeTrue();  // permissive car item hors du cours
});

test('sanitizeConditions : item_id d\'un autre cours rejeté au stockage', function (): void {
    $courseA = v5d_course('sanitize-idor-a');
    $courseB = v5d_course('sanitize-idor-b');
    $lessonB = v5d_lesson($courseB);
    $itemB   = v5d_item($lessonB);

    $validIdsA = AccessRestrictionService::courseItemIds($courseA);  // cours A = vide

    $conditions = [
        ['type' => 'completion', 'item_id' => $itemB->id, 'hide' => false],
    ];

    $sanitized = AccessRestrictionService::sanitizeConditions($conditions, $validIdsA);

    // L'item du cours B n'appartient pas aux IDs du cours A → rejeté
    expect($sanitized)->toBeEmpty();
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. ACCES DIRECT URL BLOQUÉ (POST /complete avec item restreint → 403)
// ─────────────────────────────────────────────────────────────────────────────

test('acces direct bloqué : POST /complete sur item restreint par date future → 403', function (): void {
    $course  = v5d_course('post-403');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, v5d_payload([
        'type' => 'date',
        'from' => now()->addDays(5)->toIso8601String(),
        'hide' => false,
    ]), 'document');
    $student = v5d_student();
    v5d_enroll($course, $student);

    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/complete";

    $this->actingAs($student)
        ->post($url)
        ->assertStatus(403);
});

test('acces direct autorisé : POST /complete sur item sans restriction → succès (redirect)', function (): void {
    $course  = v5d_course('post-ok');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, ['rich_text' => 'Texte'], 'document');
    $student = v5d_student();
    v5d_enroll($course, $student);

    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/complete";

    $this->actingAs($student)
        ->post($url)
        ->assertRedirect();
});

// ─────────────────────────────────────────────────────────────────────────────
// 9. LessonController : $itemRestrictions passé à la vue
// ─────────────────────────────────────────────────────────────────────────────

test('lessonController passe itemRestrictions à la vue pour un inscrit (pas preview)', function (): void {
    $course  = v5d_course('ctrl-view');
    $lesson  = v5d_lesson($course);
    $item    = v5d_item($lesson, v5d_payload([
        'type' => 'date',
        'from' => now()->addDays(3)->toIso8601String(),
        'hide' => false,
    ]));
    $student = v5d_student();
    v5d_enroll($course, $student);

    $response = $this->actingAs($student)
        ->get("/academie/courses/{$course->slug}/lessons/{$lesson->id}")
        ->assertOk();

    // La vue doit recevoir itemRestrictions et indiquer le cadenas (🔒) pour l'item bloqué
    $response->assertSee('🔒');
    $response->assertSee('Disponible');
});

// ─────────────────────────────────────────────────────────────────────────────
// 10. Livewire CourseEditor : saveItemRestrictions (ANTI-IDOR + stockage)
// ─────────────────────────────────────────────────────────────────────────────

test('saveItemRestrictions : owner peut stocker une restriction valide dans le payload', function (): void {
    $course = v5d_course('editor-save');
    $lesson = v5d_lesson($course);
    $ref    = v5d_item($lesson, [], 'document', 1);
    $target = v5d_item($lesson, [], 'document', 2);
    $owner  = v5d_owner($course);
    v5d_enroll($course, $owner);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('loadItemRestrictions', $target->id)
        ->set("editRestrictions.{$target->id}.match", 'all')
        ->set("editRestrictions.{$target->id}.conditions.0", [
            'type'    => 'completion',
            'item_id' => $ref->id,
            'hide'    => false,
        ])
        ->call('saveItemRestrictions', $target->id)
        ->assertHasNoErrors();

    $payload = $target->fresh()->payload;
    expect($payload['access_restrictions']['conditions'][0]['item_id'])->toBe($ref->id);
});

test('saveItemRestrictions : item_id d\'un autre cours rejeté par sanitize (ANTI-IDOR)', function (): void {
    $courseA = v5d_course('editor-idor-a');
    $courseB = v5d_course('editor-idor-b');
    $lessonA = v5d_lesson($courseA);
    $lessonB = v5d_lesson($courseB);
    $itemB   = v5d_item($lessonB);
    $target  = v5d_item($lessonA);
    $owner   = v5d_owner($courseA);
    v5d_enroll($courseA, $owner);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $courseA])
        ->call('loadItemRestrictions', $target->id)
        ->set("editRestrictions.{$target->id}.conditions.0", [
            'type'    => 'completion',
            'item_id' => $itemB->id,  // item d'un autre cours
            'hide'    => false,
        ])
        ->call('saveItemRestrictions', $target->id);

    $payload = $target->fresh()->payload ?? [];

    // La condition a été rejetée au sanitize : pas de clé access_restrictions
    // ou conditions vide.
    $conditions = $payload['access_restrictions']['conditions'] ?? [];
    expect($conditions)->toBeEmpty();
});
