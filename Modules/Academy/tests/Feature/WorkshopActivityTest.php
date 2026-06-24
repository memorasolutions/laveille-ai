<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F21 ATELIER (Workshop) : nouvelle ACTIVITÉ de leçon « workshop » (évaluation
 * par les pairs ; type Moodle « Workshop »). Prouve, de façon AUTONOME (helpers préfixés v21) :
 *
 *  - création d'un item workshop via l'éditeur (réglages + synchro de la grille) ;
 *  - gestion de la grille réservée au gérant (éditeur interdit au non-gérant) ;
 *  - remettre un travail : gaté inscription (403 non inscrit) + gaté phase (403 hors submission) ;
 *  - 1 travail par étudiant (mise à jour, pas de doublon) ;
 *  - allocation déterministe : jamais sa propre copie + respecte reviews_per_student (équité) ;
 *  - évaluer SEULEMENT ce qui est attribué (403 sinon) + anti auto-évaluation ;
 *  - scores bornés 0..max_score ; note finale = moyenne pondérée correcte ;
 *  - anonymat serveur : l'auteur n'est pas exposé en phase assessment ;
 *  - l'étudiant ne voit que SA note ; changement de phase + grille réservés au gérant ;
 *  - anti-IDOR (travail / évaluation d'un autre cours) ; route throttlée ; rétrocompat.
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\WorkshopAssessment;
use Modules\Academy\Models\WorkshopCriterion;
use Modules\Academy\Models\WorkshopSubmission;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\WorkshopService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe v21 - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v21Course(string $slug = 'cours-v21'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V21',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v21Lesson(Course $course): Lesson
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

function v21Item(Lesson $lesson, array $payload = [], int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'workshop',
        'title'       => 'Atelier '.$position,
        'position'    => $position,
        'payload'     => array_merge([
            'intro'               => 'Évaluons-nous entre pairs.',
            'phase'               => 'submission',
            'reviews_per_student' => 2,
            'anonymous'           => true,
        ], $payload),
        'is_required' => false,
    ]);
}

/**
 * Crée une grille simple : Clarté (max 10, poids 1) + Originalité (max 20, poids 1).
 *
 * @return array{clarte: WorkshopCriterion, orig: WorkshopCriterion}
 */
function v21Grid(LessonItem $item): array
{
    WorkshopService::syncCriteria($item, [
        ['label' => 'Clarté',      'max_score' => 10, 'weight' => 1],
        ['label' => 'Originalité', 'max_score' => 20, 'weight' => 1],
    ]);

    $criteria = WorkshopService::criteria($item);

    return ['clarte' => $criteria[0], 'orig' => $criteria[1]];
}

function v21Student(string $name = 'Étudiant Test'): User
{
    $u = User::factory()->create(['name' => $name]);
    $u->assignRole('student');

    return $u;
}

function v21Owner(Course $course): User
{
    $u = User::factory()->create(['name' => 'Formateur Test']);
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function v21Enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v21SubmitUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/submit";
}

/** Crée un travail directement (raccourci de fabrique). */
function v21Submission(LessonItem $item, ?User $user, string $title = 'Mon travail', string $body = 'Contenu'): WorkshopSubmission
{
    return WorkshopSubmission::create([
        'lesson_item_id' => $item->id,
        'user_id'        => $user?->id,
        'title'          => $title,
        'body'           => $body,
        'status'         => 'submitted',
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE + défauts
// ─────────────────────────────────────────────────────────────────────────────

test('WorkshopService lit la configuration avec ses défauts', function (): void {
    $item = LessonItem::create([
        'lesson_id' => v21Lesson(v21Course())->id,
        'type'      => 'workshop',
        'title'     => 'W',
        'position'  => 1,
        'payload'   => [],
    ]);
    expect(WorkshopService::phase($item))->toBe('submission');
    expect(WorkshopService::reviewsPerStudent($item))->toBe(2);
    expect(WorkshopService::isAnonymous($item))->toBeTrue();
    expect(WorkshopService::intro($item))->toBe('');
});

test('une phase forgée retombe sur le défaut « submission »', function (): void {
    $item = v21Item(v21Lesson(v21Course('cours-ws-forge')), ['phase' => 'pirate']);
    expect(WorkshopService::phase($item))->toBe('submission');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CRÉATION DE L'ITEM + GRILLE via l'éditeur
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant crée un item workshop ; payload + grille synchronisés', function (): void {
    $course = v21Course('cours-ws-create');
    $lesson = v21Lesson($course);
    $owner  = v21Owner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Atelier de rédaction')
        ->set("newItem.{$lesson->id}.workshop_intro", 'Rédigez puis évaluez.')
        ->set("newItem.{$lesson->id}.reviews_per_student", 3)
        ->set("newItem.{$lesson->id}.workshop_anonymous", true)
        ->set("newItem.{$lesson->id}.workshop_criteria", [
            ['label' => 'Clarté', 'description' => '', 'max_score' => 10, 'weight' => 1],
            ['label' => 'Style', 'description' => '', 'max_score' => 5, 'weight' => 2],
        ])
        ->call('addItem', $lesson->id, 'workshop')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'workshop')->first();
    expect($item)->not->toBeNull();
    expect($item->payload['intro'])->toBe('Rédigez puis évaluez.');
    expect($item->payload['reviews_per_student'])->toBe(3);
    expect(WorkshopService::phase($item))->toBe('submission'); // défaut à la création

    $criteria = WorkshopCriterion::forItem($item->id)->get();
    expect($criteria)->toHaveCount(2);
    expect($criteria[0]->label)->toBe('Clarté');
    expect($criteria[1]->max_score)->toBe(5);
    expect($criteria[1]->weight)->toBe(2.0);
});

test('GESTION DE LA GRILLE réservée au gérant : l\'éditeur est interdit au non-gérant', function (): void {
    $course  = v21Course('cours-ws-grid-403');
    $lesson  = v21Lesson($course);
    v21Item($lesson);
    $student = v21Student();

    Livewire::actingAs($student)
        ->test(CourseEditor::class, ['course' => $course])
        ->assertForbidden();
});

test('syncCriteria met à jour et soft-supprime les critères retirés', function (): void {
    $item = v21Item(v21Lesson(v21Course('cours-ws-sync')));
    $grid = v21Grid($item);
    expect(WorkshopCriterion::forItem($item->id)->count())->toBe(2);

    // On garde le 1er (modifié) et on ajoute un nouveau ; le 2e est retiré.
    WorkshopService::syncCriteria($item, [
        ['id' => $grid['clarte']->id, 'label' => 'Clarté (modifiée)', 'max_score' => 12, 'weight' => 2],
        ['label' => 'Profondeur', 'max_score' => 8, 'weight' => 1],
    ]);

    $criteria = WorkshopCriterion::forItem($item->id)->get();
    expect($criteria)->toHaveCount(2);
    expect($criteria[0]->label)->toBe('Clarté (modifiée)');
    expect($criteria[0]->max_score)->toBe(12);
    expect($criteria[1]->label)->toBe('Profondeur');
    expect(WorkshopCriterion::withTrashed()->where('lesson_item_id', $item->id)->count())->toBe(3);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. SOUMETTRE UN TRAVAIL : gating inscription + phase
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit remet son travail (phase submission)', function (): void {
    $course  = v21Course('cours-ws-submit');
    $lesson  = v21Lesson($course);
    $item    = v21Item($lesson);
    v21Grid($item);
    $student = v21Student();
    v21Enroll($course, $student);

    $this->actingAs($student)
        ->post(v21SubmitUrl($course, $lesson, $item), ['title' => 'Essai 1', 'body' => 'Mon contenu'])
        ->assertRedirect();

    $sub = WorkshopSubmission::forItem($item->id)->where('user_id', $student->id)->first();
    expect($sub)->not->toBeNull();
    expect($sub->title)->toBe('Essai 1');
});

test('un non-inscrit ne peut pas remettre de travail (403)', function (): void {
    $course = v21Course('cours-ws-gate');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson);
    $user   = v21Student();

    $this->actingAs($user)
        ->post(v21SubmitUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])
        ->assertForbidden();
    expect(WorkshopSubmission::forItem($item->id)->count())->toBe(0);
});

test('remettre HORS phase submission est refusé (403)', function (): void {
    $course  = v21Course('cours-ws-phase');
    $lesson  = v21Lesson($course);
    $item    = v21Item($lesson, ['phase' => 'assessment']);
    $student = v21Student();
    v21Enroll($course, $student);

    $this->actingAs($student)
        ->post(v21SubmitUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])
        ->assertForbidden();
    expect(WorkshopSubmission::forItem($item->id)->count())->toBe(0);
});

test('1 travail par étudiant : une 2e remise met à jour (pas de doublon)', function (): void {
    $course  = v21Course('cours-ws-one');
    $lesson  = v21Lesson($course);
    $item    = v21Item($lesson);
    $student = v21Student();
    v21Enroll($course, $student);

    $this->actingAs($student)->post(v21SubmitUrl($course, $lesson, $item), ['title' => 'V1', 'body' => 'A'])->assertRedirect();
    $this->actingAs($student)->post(v21SubmitUrl($course, $lesson, $item), ['title' => 'V2', 'body' => 'B'])->assertRedirect();

    expect(WorkshopSubmission::forItem($item->id)->where('user_id', $student->id)->count())->toBe(1);
    expect(WorkshopSubmission::forItem($item->id)->where('user_id', $student->id)->first()->title)->toBe('V2');
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ALLOCATION déterministe (jamais sa propre copie + équité)
// ─────────────────────────────────────────────────────────────────────────────

test('allocation : jamais sa propre copie + respecte reviews_per_student (équité)', function (): void {
    $course = v21Course('cours-ws-alloc');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['reviews_per_student' => 2]);

    $a = v21Student('A');
    $b = v21Student('B');
    $c = v21Student('C');
    foreach ([$a, $b, $c] as $u) {
        v21Enroll($course, $u);
        v21Submission($item, $u);
    }

    $count = WorkshopService::allocate($item);
    expect($count)->toBe(6); // 3 travaux x 2 évaluations

    // Chaque évaluateur fait exactement 2 évaluations.
    foreach ([$a, $b, $c] as $u) {
        $mine = WorkshopAssessment::forAssessor($u->id)->count();
        expect($mine)->toBe(2);
    }

    // JAMAIS sa propre copie : aucun assesseur n'évalue le travail dont il est l'auteur.
    foreach ([$a, $b, $c] as $u) {
        $ownSub = WorkshopSubmission::forItem($item->id)->where('user_id', $u->id)->first();
        $selfAssess = WorkshopAssessment::where('submission_id', $ownSub->id)->where('assessor_id', $u->id)->count();
        expect($selfAssess)->toBe(0);
    }

    // Idempotence : rejouer ne crée pas de doublon.
    WorkshopService::allocate($item);
    expect(WorkshopAssessment::whereHas('submission', fn ($q) => $q->where('lesson_item_id', $item->id))->count())->toBe(6);
});

test('allocation impossible avec moins de 2 travaux', function (): void {
    $course = v21Course('cours-ws-alloc-1');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson);
    $a      = v21Student('A');
    v21Submission($item, $a);

    expect(WorkshopService::allocate($item))->toBe(0);
});

test('reviews_per_student est plafonné à n-1 travaux', function (): void {
    $course = v21Course('cours-ws-alloc-cap');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['reviews_per_student' => 9]); // bien plus que de travaux

    $a = v21Student('A');
    $b = v21Student('B');
    v21Submission($item, $a);
    v21Submission($item, $b);

    // 2 travaux => chacun évalue au plus 1 pair.
    expect(WorkshopService::allocate($item))->toBe(2);
    expect(WorkshopAssessment::forAssessor($a->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. ÉVALUER (seulement ce qui est attribué + anti auto-évaluation)
// ─────────────────────────────────────────────────────────────────────────────

test('un pair évalue un travail qui lui est attribué (scores + feedback enregistrés)', function (): void {
    $course = v21Course('cours-ws-assess');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'assessment', 'reviews_per_student' => 1]);
    $grid   = v21Grid($item);

    $a = v21Student('A');
    $b = v21Student('B');
    v21Enroll($course, $a);
    v21Enroll($course, $b);
    v21Submission($item, $a);
    v21Submission($item, $b);
    WorkshopService::allocate($item);

    $assessment = WorkshopAssessment::forAssessor($a->id)->first();
    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/assessments/{$assessment->id}/assess";

    $this->actingAs($a)->post($url, [
        'scores'   => [$grid['clarte']->id => 10, $grid['orig']->id => 10],
        'feedback' => 'Bon travail.',
    ])->assertRedirect();

    $assessment->refresh();
    expect($assessment->submitted_at)->not->toBeNull();
    // (10/10)*1 + (10/20)*1 = 1.5 ; /2 *100 = 75
    expect((float) $assessment->computed_score)->toBe(75.0);
    expect($assessment->feedback)->toBe('Bon travail.');
});

test('évaluer une évaluation NON attribuée est refusé (403)', function (): void {
    $course = v21Course('cours-ws-assess-idor');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'assessment', 'reviews_per_student' => 1]);
    $grid   = v21Grid($item);

    $a = v21Student('A');
    $b = v21Student('B');
    $c = v21Student('C');
    foreach ([$a, $b, $c] as $u) {
        v21Enroll($course, $u);
        v21Submission($item, $u);
    }
    WorkshopService::allocate($item);

    // Une évaluation attribuée à B (pas à C).
    $assessmentForB = WorkshopAssessment::forAssessor($b->id)->first();
    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/assessments/{$assessmentForB->id}/assess";

    $this->actingAs($c)->post($url, ['scores' => [$grid['clarte']->id => 5]])->assertForbidden();
    expect($assessmentForB->fresh()->submitted_at)->toBeNull();
});

test('anti auto-évaluation : impossible de noter une évaluation de son propre travail', function (): void {
    $course = v21Course('cours-ws-self');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'assessment']);
    $grid   = v21Grid($item);

    $a = v21Student('A');
    v21Enroll($course, $a);
    $ownSub = v21Submission($item, $a);

    // On force (cas dégénéré) une évaluation de son propre travail par lui-même.
    $assessment = WorkshopAssessment::create(['submission_id' => $ownSub->id, 'assessor_id' => $a->id]);
    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/assessments/{$assessment->id}/assess";

    $this->actingAs($a)->post($url, ['scores' => [$grid['clarte']->id => 10]])->assertForbidden();
    expect($assessment->fresh()->submitted_at)->toBeNull();
});

test('scores bornés : une note > max_score est rejetée', function (): void {
    $course = v21Course('cours-ws-bound');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'assessment', 'reviews_per_student' => 1]);
    $grid   = v21Grid($item);

    $a = v21Student('A');
    $b = v21Student('B');
    v21Enroll($course, $a);
    v21Enroll($course, $b);
    v21Submission($item, $a);
    v21Submission($item, $b);
    WorkshopService::allocate($item);

    $assessment = WorkshopAssessment::forAssessor($a->id)->first();
    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/assessments/{$assessment->id}/assess";

    // Clarté a max 10 ; 99 doit être rejeté.
    $this->actingAs($a)->post($url, ['scores' => [$grid['clarte']->id => 99]])
        ->assertSessionHasErrors('scores.'.$grid['clarte']->id);
    expect($assessment->fresh()->submitted_at)->toBeNull();
});

test('évaluer HORS phase assessment est refusé (403)', function (): void {
    $course = v21Course('cours-ws-assess-phase');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'submission']);
    $grid   = v21Grid($item);

    $a = v21Student('A');
    v21Enroll($course, $a);
    $sub = v21Submission($item, $a);
    $assessment = WorkshopAssessment::create(['submission_id' => $sub->id, 'assessor_id' => $a->id]);
    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/assessments/{$assessment->id}/assess";

    $this->actingAs($a)->post($url, ['scores' => [$grid['clarte']->id => 5]])->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. CALCUL DE NOTE (pondéré) + carnet
// ─────────────────────────────────────────────────────────────────────────────

test('note finale d\'un travail = moyenne pondérée des évaluations reçues', function (): void {
    $course = v21Course('cours-ws-grade');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'assessment']);
    $grid   = v21Grid($item);

    $author = v21Student('Auteur');
    $sub    = v21Submission($item, $author);

    $r1 = v21Student('R1');
    $r2 = v21Student('R2');

    // Évaluation 1 : 10/10 + 10/20 => 75 % ; Évaluation 2 : 5/10 + 20/20 => (0.5 + 1)/2*100 = 75 %.
    $a1 = WorkshopAssessment::create(['submission_id' => $sub->id, 'assessor_id' => $r1->id]);
    WorkshopService::recordAssessment($a1, $grid === [] ? collect() : WorkshopService::criteria($item), [$grid['clarte']->id => 10, $grid['orig']->id => 10], 'ok');

    $a2 = WorkshopAssessment::create(['submission_id' => $sub->id, 'assessor_id' => $r2->id]);
    WorkshopService::recordAssessment($a2, WorkshopService::criteria($item), [$grid['clarte']->id => 5, $grid['orig']->id => 20], 'ok2');

    expect((float) $a1->fresh()->computed_score)->toBe(75.0);
    expect((float) $a2->fresh()->computed_score)->toBe(75.0);

    // Moyenne reçue = 75.
    expect(WorkshopService::submissionFinalScore($sub))->toBe(75.0);
    expect(WorkshopService::finalGradeForStudent($item, $author->id))->toBe(75.0);
});

test('computeScore pondère correctement (poids inégaux)', function (): void {
    $item = v21Item(v21Lesson(v21Course('cours-ws-weight')));
    WorkshopService::syncCriteria($item, [
        ['label' => 'A', 'max_score' => 10, 'weight' => 1],
        ['label' => 'B', 'max_score' => 10, 'weight' => 3],
    ]);
    $criteria = WorkshopService::criteria($item);
    [$ca, $cb] = [$criteria[0], $criteria[1]];

    // A=10/10 (poids 1) ; B=0/10 (poids 3) => (1*1 + 0*3)/4 *100 = 25 %.
    expect(WorkshopService::computeScore($criteria, [$ca->id => 10, $cb->id => 0]))->toBe(25.0);
});

test('la note finale ignore les évaluations non rendues (note vide exclue)', function (): void {
    $item = v21Item(v21Lesson(v21Course('cours-ws-empty')));
    $grid = v21Grid($item);
    $author = v21Student('Auteur');
    $sub    = v21Submission($item, $author);

    // Une évaluation attribuée mais NON rendue (submitted_at null).
    WorkshopAssessment::create(['submission_id' => $sub->id, 'assessor_id' => v21Student('R')->id]);

    expect(WorkshopService::submissionFinalScore($sub))->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. ANONYMAT SERVEUR
// ─────────────────────────────────────────────────────────────────────────────

test('anonymat : en phase assessment l\'auteur n\'est PAS exposé dans la charge utile', function (): void {
    $course = v21Course('cours-ws-anon');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'assessment', 'anonymous' => true, 'reviews_per_student' => 1]);
    v21Grid($item);

    $a = v21Student('Alice');
    $b = v21Student('Bob');
    v21Enroll($course, $a);
    v21Enroll($course, $b);
    v21Submission($item, $a, 'Travail Alice');
    v21Submission($item, $b, 'Travail Bob');
    WorkshopService::allocate($item);

    $assignments = WorkshopService::assignmentsFor($item, (int) $a->id);
    expect($assignments)->toHaveCount(1);
    // user_id NON chargé (jamais sélectionné) => l'auteur ne fuit pas.
    expect($assignments->first()->submission->user_id)->toBeNull();
});

test('anonymat désactivé : l\'auteur est chargé pour l\'évaluateur', function (): void {
    $course = v21Course('cours-ws-named');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'assessment', 'anonymous' => false, 'reviews_per_student' => 1]);
    v21Grid($item);

    $a = v21Student('Alice');
    $b = v21Student('Bob');
    v21Enroll($course, $a);
    v21Enroll($course, $b);
    v21Submission($item, $a);
    v21Submission($item, $b);
    WorkshopService::allocate($item);

    $assignments = WorkshopService::assignmentsFor($item, (int) $a->id);
    expect($assignments->first()->submission->user_id)->not->toBeNull();
});

test('anonymat bout en bout : la page d\'évaluation ne montre pas le nom de l\'auteur', function (): void {
    $course = v21Course('cours-ws-anon-e2e');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'assessment', 'anonymous' => true, 'reviews_per_student' => 1]);
    v21Grid($item);

    $alice = v21Student('AliceUnique');
    $bob   = v21Student('BobUnique');
    v21Enroll($course, $alice);
    v21Enroll($course, $bob);
    v21Submission($item, $alice, 'Travail anonyme A', 'Texte A');
    v21Submission($item, $bob, 'Travail anonyme B', 'Texte B');
    WorkshopService::allocate($item);

    // Alice évalue le travail de Bob : son nom ne doit pas apparaître.
    $this->actingAs($alice)
        ->get("/academie/courses/{$course->slug}/lessons/{$lesson->id}")
        ->assertOk()
        ->assertDontSee('BobUnique');
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. NOTE DE L'ÉTUDIANT : ne voit que la sienne
// ─────────────────────────────────────────────────────────────────────────────

test('un étudiant ne voit QUE sa propre note finale (scopée au user)', function (): void {
    $item = v21Item(v21Lesson(v21Course('cours-ws-mygrade')), ['phase' => 'closed']);
    $grid = v21Grid($item);

    $a = v21Student('A');
    $b = v21Student('B');
    $subA = v21Submission($item, $a);
    $subB = v21Submission($item, $b);

    // A reçoit 75 %, B reçoit 50 %.
    $ra = WorkshopAssessment::create(['submission_id' => $subA->id, 'assessor_id' => $b->id]);
    WorkshopService::recordAssessment($ra, WorkshopService::criteria($item), [$grid['clarte']->id => 10, $grid['orig']->id => 10], null);
    $rb = WorkshopAssessment::create(['submission_id' => $subB->id, 'assessor_id' => $a->id]);
    WorkshopService::recordAssessment($rb, WorkshopService::criteria($item), [$grid['clarte']->id => 5, $grid['orig']->id => 10], null);

    expect(WorkshopService::finalGradeForStudent($item, $a->id))->toBe(75.0);
    expect(WorkshopService::finalGradeForStudent($item, $b->id))->toBe(50.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 9. PHASE + GRILLE réservées au gérant
// ─────────────────────────────────────────────────────────────────────────────

test('changement de phase réservé au gérant ; un non-gérant est refusé (403)', function (): void {
    $course  = v21Course('cours-ws-phase-403');
    $lesson  = v21Lesson($course);
    $item    = v21Item($lesson, ['phase' => 'submission']);
    $owner   = v21Owner($course);
    $student = v21Student();
    v21Enroll($course, $student);

    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/phase";

    $this->actingAs($student)->post($url, ['phase' => 'assessment'])->assertForbidden();
    expect(WorkshopService::phase($item->fresh()))->toBe('submission');

    $this->actingAs($owner)->post($url, ['phase' => 'assessment'])->assertRedirect();
    expect(WorkshopService::phase($item->fresh()))->toBe('assessment');
});

test('entrer en phase assessment via le gérant attribue les évaluations', function (): void {
    $course = v21Course('cours-ws-phase-alloc');
    $lesson = v21Lesson($course);
    $item   = v21Item($lesson, ['phase' => 'submission', 'reviews_per_student' => 1]);
    $owner  = v21Owner($course);

    $a = v21Student('A');
    $b = v21Student('B');
    foreach ([$a, $b] as $u) {
        v21Enroll($course, $u);
        v21Submission($item, $u);
    }

    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/phase";
    $this->actingAs($owner)->post($url, ['phase' => 'assessment'])->assertRedirect();

    expect(WorkshopAssessment::whereHas('submission', fn ($q) => $q->where('lesson_item_id', $item->id))->count())->toBe(2);
});

test('allocation manuelle réservée au gérant (403 pour un étudiant)', function (): void {
    $course  = v21Course('cours-ws-alloc-403');
    $lesson  = v21Lesson($course);
    $item    = v21Item($lesson);
    $student = v21Student();
    v21Enroll($course, $student);

    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/workshop/allocate";
    $this->actingAs($student)->post($url)->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 10. HONEYPOT + SÉCURITÉ (anti-IDOR, throttle, anti-XSS)
// ─────────────────────────────────────────────────────────────────────────────

test('honeypot rempli => travail rejeté SILENCIEUSEMENT (aucune écriture)', function (): void {
    $course  = v21Course('cours-ws-hp');
    $lesson  = v21Lesson($course);
    $item    = v21Item($lesson);
    $student = v21Student();
    v21Enroll($course, $student);

    $this->actingAs($student)
        ->post(v21SubmitUrl($course, $lesson, $item), [
            'title'                   => 'Spam',
            'body'                    => 'Spam',
            WorkshopService::HONEYPOT => 'http://spam.example',
        ])
        ->assertRedirect();

    expect(WorkshopSubmission::forItem($item->id)->count())->toBe(0);
});

test('ANTI-IDOR : remettre un travail sur un item d\'un AUTRE cours est refusé (404)', function (): void {
    $courseA = v21Course('cours-ws-idor-a');
    $lessonA = v21Lesson($courseA);

    $courseB = v21Course('cours-ws-idor-b');
    $lessonB = v21Lesson($courseB);
    $itemB   = v21Item($lessonB);

    $student = v21Student();
    v21Enroll($courseA, $student);

    $this->actingAs($student)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/workshop/submit", ['title' => 'X', 'body' => 'Y'])
        ->assertNotFound();
    expect(WorkshopSubmission::forItem($itemB->id)->count())->toBe(0);
});

test('ANTI-IDOR : évaluer une évaluation d\'un AUTRE cours via sa propre route est refusé (404)', function (): void {
    $courseA = v21Course('cours-ws-idor2-a');
    $lessonA = v21Lesson($courseA);
    $itemA   = v21Item($lessonA, ['phase' => 'assessment']);
    $studentA = v21Student('A');
    v21Enroll($courseA, $studentA);

    $courseB = v21Course('cours-ws-idor2-b');
    $lessonB = v21Lesson($courseB);
    $itemB   = v21Item($lessonB, ['phase' => 'assessment']);
    $subB    = v21Submission($itemB, v21Student('B'));
    $assessmentB = WorkshopAssessment::create(['submission_id' => $subB->id, 'assessor_id' => $studentA->id]);

    $this->actingAs($studentA)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemA->id}/workshop/assessments/{$assessmentB->id}/assess", ['scores' => []])
        ->assertNotFound();
});

test('anti-XSS : le corps d\'un travail avec <script> est neutralisé au rendu', function (): void {
    expect(WorkshopService::renderText('Bonjour <script>alert(9)</script> fin'))
        ->not->toContain('<script>alert(9)');
});

test('la route de remise de travail est throttlée (429 après le quota)', function (): void {
    $course  = v21Course('cours-ws-throttle');
    $lesson  = v21Lesson($course);
    $item    = v21Item($lesson);
    $student = v21Student();
    v21Enroll($course, $student);

    $statuses = [];
    for ($i = 0; $i < 25; $i++) {
        $statuses[] = $this->actingAs($student)
            ->post(v21SubmitUrl($course, $lesson, $item), ['title' => 'T'.$i, 'body' => 'B'])
            ->getStatusCode();
    }

    expect($statuses)->toContain(429);
});

// ─────────────────────────────────────────────────────────────────────────────
// 11. RÉTROCOMPAT
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : remettre sur un item NON-workshop (document) est refusé (404)', function (): void {
    $course  = v21Course('cours-ws-retro');
    $lesson  = v21Lesson($course);
    $doc     = LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'document',
        'title'     => 'Doc',
        'position'  => 1,
        'payload'   => ['rich_text' => 'Notes'],
    ]);
    $student = v21Student();
    v21Enroll($course, $student);

    $this->actingAs($student)
        ->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$doc->id}/workshop/submit", ['title' => 'X', 'body' => 'Y'])
        ->assertNotFound();
});

test('rétrocompat : les défauts d\'achèvement des autres types sont inchangés ; workshop => manual', function (): void {
    $lesson = v21Lesson(v21Course('cours-ws-retro-defaults'));
    $video  = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'V', 'position' => 1, 'payload' => []]);
    $quiz   = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Q', 'position' => 2, 'payload' => []]);
    $ws     = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'workshop', 'title' => 'W', 'position' => 3, 'payload' => []]);

    expect(ActivityCompletionService::criterionFor($video))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($quiz))->toBe('min_grade');
    expect(ActivityCompletionService::criterionFor($ws))->toBe('manual'); // type non spécialisé => défaut manual
});

// C3 [règle 10] : aucun tiret cadratin dans les vues touchées.
test('aucun tiret cadratin dans les vues workshop/éditeur touchées', function (): void {
    foreach ([
        base_path('Modules/Academy/resources/views/public/lesson.blade.php'),
        base_path('Modules/Academy/resources/views/livewire/course-editor.blade.php'),
    ] as $path) {
        expect(file_get_contents($path))->not->toContain('—');
    }
});
