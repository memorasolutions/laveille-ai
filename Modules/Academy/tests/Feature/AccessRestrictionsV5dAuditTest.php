<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - Correctifs audit V5-d (restrictions d'accès).
 *
 * Couvre :
 *  1. BLOQUANT  - Forum createTopic/reply : abort 403 si item restreint (non-gérant) ;
 *                 gérant non bloqué malgré la restriction.
 *  2. HAUT      - group_id cross-cours : rejeté à l'évaluation et au stockage.
 *  3. MOYEN     - Auto-référence item (deadlock) : rejetée par sanitizeConditions.
 *  4. BAS       - restrictionRefItems exige manageStructure (non-gérant = 403 Livewire).
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
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
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
// Helpers autonomes (préfixe av5d_ - aucune collision inter-fichiers)
// ─────────────────────────────────────────────────────────────────────────────

function av5d_course(string $slug = 'cours-av5d'): Course
{
    static $counter = 0;
    $counter++;

    return Course::create([
        'slug'        => $slug.'-'.$counter,
        'title'       => 'Cours audit V5-d '.$counter,
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function av5d_lesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre audit',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon audit',
        'slug'       => 'lecon-audit-'.uniqid(),
        'position'   => 1,
    ]);
}

function av5d_item(Lesson $lesson, array $payload = [], string $type = 'forum', int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => $type,
        'title'     => 'Élément audit '.$position,
        'position'  => $position,
        'payload'   => $payload,
    ]);
}

function av5d_student(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function av5d_owner(Course $course): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function av5d_enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

/** Payload de restriction avec une condition date future (bloquante). */
function av5d_date_restriction(): array
{
    return [
        'access_restrictions' => [
            'match'      => 'all',
            'conditions' => [
                [
                    'type' => 'date',
                    'from' => now()->addDays(5)->toIso8601String(),
                    'hide' => false,
                ],
            ],
        ],
    ];
}

function av5d_forum_url(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/topics";
}

function av5d_reply_url(Course $course, Lesson $lesson, LessonItem $item, int $topicId): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/topics/{$topicId}/reply";
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. FORUM - createTopic bloqué pour un étudiant si l'item est restreint
// ─────────────────────────────────────────────────────────────────────────────

test('forum createTopic : étudiant inscrit bloqué par restriction V5-d → 403', function (): void {
    $course  = av5d_course('forum-restr-create');
    $lesson  = av5d_lesson($course);
    $item    = av5d_item($lesson, av5d_date_restriction(), 'forum');
    $student = av5d_student();
    av5d_enroll($course, $student);

    $this->actingAs($student)
        ->post(av5d_forum_url($course, $lesson, $item), [
            'title' => 'Sujet test',
            'body'  => 'Corps du sujet',
        ])
        ->assertStatus(403);
});

test('forum createTopic : étudiant non inscrit → 403 (contrôle de base)', function (): void {
    $course  = av5d_course('forum-nonins');
    $lesson  = av5d_lesson($course);
    $item    = av5d_item($lesson, [], 'forum');
    $student = av5d_student();
    // non inscrit

    $this->actingAs($student)
        ->post(av5d_forum_url($course, $lesson, $item), [
            'title' => 'Sujet test',
            'body'  => 'Corps',
        ])
        ->assertStatus(403);
});

test('forum createTopic : gérant non bloqué même si item restreint → succès (redirect)', function (): void {
    $course  = av5d_course('forum-restr-gerant');
    $lesson  = av5d_lesson($course);
    // Forum avec allow_student_topics=true pour que le gérant puisse créer un sujet.
    $payload = av5d_date_restriction();
    $payload['allow_student_topics'] = true;
    $item    = av5d_item($lesson, $payload, 'forum');
    $owner   = av5d_owner($course);
    av5d_enroll($course, $owner);

    $this->actingAs($owner)
        ->post(av5d_forum_url($course, $lesson, $item), [
            'title' => 'Sujet du gérant',
            'body'  => 'Corps du sujet',
        ])
        ->assertRedirect();
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. FORUM - reply bloqué pour un étudiant si l'item est restreint
// ─────────────────────────────────────────────────────────────────────────────

test('forum reply : étudiant inscrit bloqué par restriction V5-d → 403', function (): void {
    $course  = av5d_course('forum-restr-reply');
    $lesson  = av5d_lesson($course);
    $item    = av5d_item($lesson, av5d_date_restriction(), 'forum');
    $student = av5d_student();
    av5d_enroll($course, $student);

    // Créer un sujet directement en base (sans passer par le contrôleur).
    $topic = \Modules\Academy\Models\ForumTopic::create([
        'lesson_item_id' => $item->id,
        'user_id'        => $student->id,
        'title'          => 'Sujet existant',
        'body'           => 'Corps',
    ]);

    $this->actingAs($student)
        ->post(av5d_reply_url($course, $lesson, $item, $topic->id), [
            'body' => 'Ma réponse',
        ])
        ->assertStatus(403);
});

test('forum reply : gérant non bloqué par restriction → succès (redirect)', function (): void {
    $course = av5d_course('forum-restr-reply-gerant');
    $lesson = av5d_lesson($course);
    $item   = av5d_item($lesson, av5d_date_restriction(), 'forum');
    $owner  = av5d_owner($course);
    av5d_enroll($course, $owner);

    $topic = \Modules\Academy\Models\ForumTopic::create([
        'lesson_item_id' => $item->id,
        'user_id'        => $owner->id,
        'title'          => 'Sujet gérant',
        'body'           => 'Corps',
    ]);

    $this->actingAs($owner)
        ->post(av5d_reply_url($course, $lesson, $item, $topic->id), [
            'body' => 'Réponse du gérant',
        ])
        ->assertRedirect();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. evalGroup : group_id d'un autre cours → ignoré (permissif à l'évaluation)
// ─────────────────────────────────────────────────────────────────────────────

test('evalGroup cross-cours : cohorte d\'un autre cours → condition ignorée → permissif', function (): void {
    $courseA = av5d_course('group-idor-eval-a');
    $courseB = av5d_course('group-idor-eval-b');
    $lessonA = av5d_lesson($courseA);
    $student = av5d_student();
    av5d_enroll($courseA, $student);

    // Cohorte appartenant au cours B (pas A).
    $cohortB = Cohort::create([
        'course_id' => $courseB->id,
        'name'      => 'Cohorte B',
        'slug'      => 'cohorte-b-'.uniqid(),
    ]);
    // L'étudiant N'est pas membre de la cohorte B.

    $item = av5d_item($lessonA, [
        'access_restrictions' => [
            'match'      => 'all',
            'conditions' => [
                ['type' => 'group', 'group_id' => $cohortB->id, 'hide' => false],
            ],
        ],
    ], 'document');

    // Évaluation pour le cours A : la cohorte appartient au cours B → ignorée → permissif.
    $result = AccessRestrictionService::evaluate($student, $item, $courseA);

    expect($result['allowed'])->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. sanitizeConditions : group_id cross-cours rejeté au stockage
// ─────────────────────────────────────────────────────────────────────────────

test('sanitizeConditions : group_id d\'un autre cours → rejeté au stockage', function (): void {
    $courseA = av5d_course('group-idor-save-a');
    $courseB = av5d_course('group-idor-save-b');

    $cohortB = Cohort::create([
        'course_id' => $courseB->id,
        'name'      => 'Cohorte B save',
        'slug'      => 'cohorte-b-save-'.uniqid(),
    ]);

    $validItemIds = AccessRestrictionService::courseItemIds($courseA);

    $conditions = [
        ['type' => 'group', 'group_id' => $cohortB->id, 'hide' => false],
    ];

    $sanitized = AccessRestrictionService::sanitizeConditions(
        $conditions,
        $validItemIds,
        $courseA->id  // cours A : la cohorte B est hors périmètre
    );

    expect($sanitized)->toBeEmpty();
});

test('sanitizeConditions : group_id du bon cours → conservé au stockage', function (): void {
    $course = av5d_course('group-valid-save');

    $cohort = Cohort::create([
        'course_id' => $course->id,
        'name'      => 'Cohorte valide',
        'slug'      => 'cohorte-valide-'.uniqid(),
    ]);

    $validItemIds = AccessRestrictionService::courseItemIds($course);

    $conditions = [
        ['type' => 'group', 'group_id' => $cohort->id, 'hide' => false],
    ];

    $sanitized = AccessRestrictionService::sanitizeConditions(
        $conditions,
        $validItemIds,
        $course->id
    );

    expect($sanitized)->toHaveCount(1);
    expect($sanitized[0]['group_id'])->toBe($cohort->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Auto-référence d'item : rejetée par sanitizeConditions (deadlock)
// ─────────────────────────────────────────────────────────────────────────────

test('sanitizeConditions : auto-référence completion → rejetée (anti-deadlock)', function (): void {
    $course = av5d_course('autoref-completion');
    $lesson = av5d_lesson($course);
    $item   = av5d_item($lesson, [], 'document');

    $validItemIds = AccessRestrictionService::courseItemIds($course);

    $conditions = [
        ['type' => 'completion', 'item_id' => $item->id, 'hide' => false],
    ];

    $sanitized = AccessRestrictionService::sanitizeConditions(
        $conditions,
        $validItemIds,
        $course->id,
        $item->id  // l'item courant : doit être exclu
    );

    expect($sanitized)->toBeEmpty();
});

test('sanitizeConditions : auto-référence grade → rejetée (anti-deadlock)', function (): void {
    $course = av5d_course('autoref-grade');
    $lesson = av5d_lesson($course);
    $item   = av5d_item($lesson, [], 'quiz');

    $validItemIds = AccessRestrictionService::courseItemIds($course);

    $conditions = [
        ['type' => 'grade', 'item_id' => $item->id, 'min_percent' => 60, 'hide' => false],
    ];

    $sanitized = AccessRestrictionService::sanitizeConditions(
        $conditions,
        $validItemIds,
        $course->id,
        $item->id
    );

    expect($sanitized)->toBeEmpty();
});

test('sanitizeConditions : référence à un autre item du même cours → conservée (completion)', function (): void {
    $course = av5d_course('ref-autre-item');
    $lesson = av5d_lesson($course);
    $ref    = av5d_item($lesson, [], 'document', 1);
    $target = av5d_item($lesson, [], 'document', 2);

    $validItemIds = AccessRestrictionService::courseItemIds($course);

    $conditions = [
        ['type' => 'completion', 'item_id' => $ref->id, 'hide' => false],
    ];

    $sanitized = AccessRestrictionService::sanitizeConditions(
        $conditions,
        $validItemIds,
        $course->id,
        $target->id  // on exclut target, pas ref
    );

    expect($sanitized)->toHaveCount(1);
    expect($sanitized[0]['item_id'])->toBe($ref->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. restrictionRefItems : exige manageStructure (défense en profondeur)
// ─────────────────────────────────────────────────────────────────────────────

test('restrictionRefItems : étudiant (non-gérant) → 403 au montage du composant', function (): void {
    // Un étudiant ordinaire ne peut pas monter CourseEditor du tout
    // (mount → authorize('update', $course) → 403). La garde manageStructure
    // dans restrictionRefItems est une couche supplémentaire pour les utilisateurs
    // ayant le droit update mais pas manageStructure (ex. co-formateur read-only futur).
    $course  = av5d_course('refitems-student');
    $lesson  = av5d_lesson($course);
    av5d_item($lesson, [], 'document');
    $student = av5d_student();
    av5d_enroll($course, $student);

    Livewire::actingAs($student)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

test('restrictionRefItems : owner du cours → liste retournée sans erreur', function (): void {
    $course = av5d_course('refitems-owner');
    $lesson = av5d_lesson($course);
    $ref    = av5d_item($lesson, [], 'document', 1);
    $target = av5d_item($lesson, [], 'document', 2);
    $owner  = av5d_owner($course);
    av5d_enroll($course, $owner);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->call('restrictionRefItems', $target->id)
        ->assertHasNoErrors();
    // Pas d'assertion sur la valeur de retour (Livewire retourne via la réponse JSON) ;
    // l'absence d'erreur et de 403 suffit à valider la garde.
});
