<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - CORRECTIFS V5a (audit completion configurable).
 *
 * Prouve que :
 *  A. Le certificat est emis ET l'affichage ($courseCompleted) refletent le seuil
 *     configure (percent < 100 %), jamais code en dur a 100 %.
 *  B. Pour le critere min_grade avec 0 items requis, le certificat est emis quand
 *     la note finale du carnet atteint le seuil.
 *  C. final_score du certificat = note du carnet pour min_grade (pas 0 ni 100 par defaut).
 *  D. Retrocompat stricte : all_required exige toujours 100 % des items requis.
 *  E. CertificateService::issueFor est idempotent (double appel = 1 seul certificat).
 *
 * Autonome : helpers prefixes vx5a (aucune redeclaration). SKIPPED si Academy off.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\CertificateIssued;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\Progress;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\CertificateService;
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

// Helpers prefixes vx5a (autonomes, aucune redeclaration)

function vx5aCourse(?array $criteria = null, string $slug = 'vx5a-cours'): Course
{
    return Course::create([
        'slug'                => $slug,
        'title'               => 'VX5a Cours',
        'language'            => 'fr-CA',
        'level'               => 'intro',
        'visibility'          => 'public',
        'access_type'         => 'free',
        'status'              => 'published',
        'currency'            => 'CAD',
        'completion_criteria' => $criteria,
    ]);
}

/** Cree $count items requis dans 1 chapitre, retourne les LessonItem. */
function vx5aRequiredItems(Course $course, int $count, string $suffix = ''): array
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre VX5a',
        'position'  => 1,
    ]);

    $items = [];
    for ($i = 1; $i <= $count; $i++) {
        $lesson = Lesson::create([
            'chapter_id' => $chapter->id,
            'title'      => "Lecon VX5a $i",
            'slug'       => "vx5a-lecon-$i-{$course->id}{$suffix}",
            'position'   => $i,
        ]);
        $items[] = LessonItem::create([
            'lesson_id'   => $lesson->id,
            'type'        => 'document',
            'title'       => "Item VX5a $i",
            'position'    => 1,
            'is_required' => true,
        ]);
    }

    return $items;
}

/** Cree un item quiz non-requis (pour le carnet de notes). */
function vx5aQuizItem(Course $course, string $suffix = ''): LessonItem
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre quiz VX5a',
        'position'  => 2,
    ]);
    $lesson = Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Lecon quiz VX5a',
        'slug'       => "vx5a-quiz-{$course->id}{$suffix}",
        'position'   => 1,
    ]);

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz final VX5a',
        'position'    => 1,
        'is_required' => false,
        'payload'     => ['questions' => [['q' => 'x']]],
    ]);
}

/** Enregistre une tentative de quiz finalisee a $percent %. */
function vx5aAttempt(User $user, Course $course, LessonItem $item, int $percent): QuizAttempt
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
// A. Certificat emis au seuil percent (< 100 %), jamais code en dur a 100 %
// ─────────────────────────────────────────────────────────────────────────────

test('V5a-A : certificat emis a 80 % (seuil percent) avant 100 % des items', function (): void {
    $course = vx5aCourse(['type' => 'percent', 'value' => 80], 'vx5a-percent');
    $items  = vx5aRequiredItems($course, 5);
    $user   = User::factory()->create();

    // 4/5 = 80 % : seuil atteint mais PAS 100 %
    foreach (array_slice($items, 0, 4) as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);

    $cert = CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->first();
    expect($cert)->not->toBeNull('Le certificat doit etre emis au seuil 80 %, pas seulement a 100 %');

    // CourseCompletionService confirme la completion au seuil
    expect((new CourseCompletionService())->isComplete($user, $course))->toBeTrue();

    // Le 5e item NON complete ne bloque pas : comportement voulu (percent, pas all_required)
    $progress = Progress::where('user_id', $user->id)->where('course_id', $course->id)->first();
    expect($progress->percent)->toBe(80);
});

test('V5a-A : aucun certificat en dessous du seuil percent', function (): void {
    $course = vx5aCourse(['type' => 'percent', 'value' => 80], 'vx5a-percent-sub');
    $items  = vx5aRequiredItems($course, 5);
    $user   = User::factory()->create();

    // 3/5 = 60 % : sous le seuil 80 %
    foreach (array_slice($items, 0, 3) as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);

    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// B. Certificat emis pour min_grade avec 0 items requis
// ─────────────────────────────────────────────────────────────────────────────

test('V5a-B : min_grade avec 0 items requis - certificat emis quand note >= seuil', function (): void {
    $course = vx5aCourse(['type' => 'min_grade', 'value' => 70], 'vx5a-mingrade-zero');
    $quiz   = vx5aQuizItem($course, '-zero'); // is_required = false

    $user = User::factory()->create();

    // Aucun item requis : required_total = 0, percent = 0
    vx5aAttempt($user, $course, $quiz, 75);
    ProgressService::recalculate($user, $course);

    $progress = Progress::where('user_id', $user->id)->where('course_id', $course->id)->first();
    expect($progress->required_total)->toBe(0, 'Aucun item requis dans ce cours');
    expect($progress->percent)->toBe(0, 'Percent = 0 car aucun item requis');

    // Malgre percent = 0, isComplete = true car note 75 >= seuil 70
    expect((new CourseCompletionService())->isComplete($user, $course))->toBeTrue();
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeTrue(
        'Le certificat doit etre emis meme avec 0 items requis si la note est suffisante'
    );
});

test('V5a-B : min_grade avec 0 items requis - aucun certificat si note insuffisante', function (): void {
    $course = vx5aCourse(['type' => 'min_grade', 'value' => 70], 'vx5a-mingrade-fail');
    $quiz   = vx5aQuizItem($course, '-fail');
    $user   = User::factory()->create();

    vx5aAttempt($user, $course, $quiz, 60); // 60 < 70 : seuil non atteint
    ProgressService::recalculate($user, $course);

    expect((new CourseCompletionService())->isComplete($user, $course))->toBeFalse();
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// C. final_score du certificat = note du carnet pour min_grade (pas 0 ni 100)
// ─────────────────────────────────────────────────────────────────────────────

test('V5a-C : final_score = note du carnet pour min_grade (pas 0 ni 100 par defaut)', function (): void {
    $course = vx5aCourse(['type' => 'min_grade', 'value' => 70], 'vx5a-finalscore');
    $quiz   = vx5aQuizItem($course, '-score');
    $user   = User::factory()->create();

    // Note a 85 % : le certificat doit refleter cette note, pas 0 (items/total) ni 100
    vx5aAttempt($user, $course, $quiz, 85);
    ProgressService::recalculate($user, $course);

    $cert = CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->first();
    expect($cert)->not->toBeNull();
    expect($cert->final_score)->toBe(85, 'final_score doit etre la note du carnet (85), pas 0 (aucun item) ni 100 (defaut)');
});

test('V5a-C : final_score = 100 pour all_required quand tous les items sont completes', function (): void {
    $course = vx5aCourse(null, 'vx5a-allreq-score');
    $items  = vx5aRequiredItems($course, 3, '-score');
    $user   = User::factory()->create();

    foreach ($items as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);

    $cert = CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->first();
    expect($cert)->not->toBeNull();
    expect($cert->final_score)->toBe(100, 'Tous les items requis completes = final_score 100 (comportement historique)');
});

// ─────────────────────────────────────────────────────────────────────────────
// D. Retrocompat stricte : all_required exige 100 % des items requis
// ─────────────────────────────────────────────────────────────────────────────

test('V5a-D : all_required - aucun certificat avant que tous les items requis soient completes', function (): void {
    $course = vx5aCourse(null, 'vx5a-retro'); // all_required par defaut
    $items  = vx5aRequiredItems($course, 3, '-retro');
    $user   = User::factory()->create();

    // 2/3 : pas encore complete
    foreach (array_slice($items, 0, 2) as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);

    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeFalse(
        'all_required : pas de cert a 2/3 items'
    );
    expect((new CourseCompletionService())->isComplete($user, $course))->toBeFalse();

    // 3/3 : complete (100 %)
    Completion::create([
        'user_id' => $user->id, 'course_id' => $course->id,
        'lesson_item_id' => $items[2]->id, 'status' => 'completed', 'completed_at' => now(),
    ]);
    ProgressService::recalculate($user, $course);

    expect((new CourseCompletionService())->isComplete($user, $course))->toBeTrue();
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->exists())->toBeTrue(
        'all_required : cert emis a 100 %'
    );
});

// ─────────────────────────────────────────────────────────────────────────────
// E. Idempotence : double appel a issueFor = 1 seul certificat
// ─────────────────────────────────────────────────────────────────────────────

test('V5a-E : CertificateService::issueFor est idempotent (double appel = 1 certificat)', function (): void {
    $course = vx5aCourse(null, 'vx5a-idem');
    $items  = vx5aRequiredItems($course, 2, '-idem');
    $user   = User::factory()->create();

    foreach ($items as $item) {
        Completion::create([
            'user_id' => $user->id, 'course_id' => $course->id,
            'lesson_item_id' => $item->id, 'status' => 'completed', 'completed_at' => now(),
        ]);
    }
    ProgressService::recalculate($user, $course);

    // Double appel explicite
    $svc   = new CertificateService();
    $cert1 = $svc->issueFor($user, $course);
    $cert2 = $svc->issueFor($user, $course);

    expect($cert1)->not->toBeNull();
    expect($cert2)->not->toBeNull();
    expect($cert1->id)->toBe($cert2->id, 'Le meme certificat doit etre retourne (pas de doublon)');
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->count())
        ->toBe(1, 'Un seul certificat en base, meme apres double appel');
});

// ─────────────────────────────────────────────────────────────────────────────
// F. Index unique certificates_issued(user_id, course_id) present
// ─────────────────────────────────────────────────────────────────────────────

test('V5a-F : index unique user_course presente sur certificates_issued', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasTable('certificates_issued'))->toBeTrue();

    // Verifier via tentative d'insertion doublon qu'une exception est levee
    $course = vx5aCourse(null, 'vx5a-uniq');
    $user   = User::factory()->create();
    $items  = vx5aRequiredItems($course, 1, '-uniq');

    Completion::create([
        'user_id' => $user->id, 'course_id' => $course->id,
        'lesson_item_id' => $items[0]->id, 'status' => 'completed', 'completed_at' => now(),
    ]);
    ProgressService::recalculate($user, $course);

    $cert = CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->first();
    expect($cert)->not->toBeNull();

    // Insertion manuelle d'un doublon : doit lever une exception de contrainte unique
    $threw = false;
    try {
        CertificateIssued::create([
            'user_id'           => $user->id,
            'course_id'         => $course->id,
            'serial'            => 'ACAD-DOUBLON001',
            'verification_hash' => sha1('doublon'),
            'public_url_slug'   => 'cert-doublon-' . time(),
            'issued_at'         => now(),
        ]);
    } catch (\Throwable) {
        $threw = true;
    }

    expect($threw)->toBeTrue('L\'index unique doit empecher l\'insertion d\'un doublon');
    expect(CertificateIssued::where('user_id', $user->id)->where('course_id', $course->id)->count())->toBe(1);
});
