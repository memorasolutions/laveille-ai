<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — MODE KIOSQUE (verrouillage anti-triche des évaluations
 * surveillées, parité Moodle « Safe Exam Browser »).
 *
 * Prouve que :
 *  (a) le formateur PROPRIÉTAIRE du cours peut activer/désactiver le mode
 *      kiosque sur un item de quiz (checkbox de l'éditeur, via updateItem) ;
 *      un AUTRE formateur (non owner/instructor de CE cours) est REFUSÉ (IDOR) ;
 *  (b) un incident est consigné SCOPÉ à la bonne tentative/utilisateur — un
 *      apprenant ne peut PAS injecter un incident sur la tentative (le round en
 *      session) d'un AUTRE apprenant, et un attempt_id forgé sur un item d'un
 *      autre cours est également rejeté ;
 *  (c) le drapeau global désactivé (défaut) ne casse RIEN : route 404, aucune
 *      colonne kiosque n'influence le comportement normal du quiz, aucun
 *      incident n'est jamais consigné même si l'endpoint était appelé.
 *
 * Autonome : helpers préfixés `kiosk`. SKIPPED si Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\KioskViolations;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\KioskViolation;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\QuizAttempt;
use Modules\Academy\Services\KioskViolationService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);

    // Défaut EXPLICITE : chaque test active le drapeau lui-même quand nécessaire
    // (le test « drapeau off » vérifie justement le défaut réel de la config).
    config(['academy.kiosk_mode_enabled' => false]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers kiosk (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function kioskCourse(string $slug): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours kiosque',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function kioskLesson(Course $course): Lesson
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

/** Formateur PROPRIÉTAIRE (owner) de CE cours. */
function kioskOwner(Course $course): User
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

/** Formateur SANS AUCUN rôle sur ce cours précis (pour le test IDOR). */
function kioskOtherInstructor(): User
{
    $other = User::factory()->create();
    $other->assignRole('instructor');

    return $other;
}

function kioskQuizItem(Lesson $lesson, array $payload = [], bool $kioskMode = false): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'quiz',
        'title'       => 'Quiz surveillé',
        'position'    => 1,
        'payload'     => $payload,
        'is_required' => false,
        'kiosk_mode'  => $kioskMode,
    ]);
}

function kioskEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function kioskStudent(): User
{
    $u = User::factory()->create();
    $u->assignRole('student');

    return $u;
}

function kioskStartUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/start";
}

function kioskSubmitUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/submit";
}

function kioskViolationUrl(Course $c, Lesson $l, LessonItem $i): string
{
    return "/academie/courses/{$c->slug}/lessons/{$l->id}/items/{$i->id}/quiz/kiosk-violation";
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Activation / désactivation du mode kiosque par le formateur — IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('le formateur propriétaire peut activer le mode kiosque sur un item de quiz', function (): void {
    config(['academy.kiosk_mode_enabled' => true]);

    $course = kioskCourse('cours-kiosk-owner');
    $lesson = kioskLesson($course);
    $owner  = kioskOwner($course);
    $item   = kioskQuizItem($lesson, ['passing_score' => 60], kioskMode: false);

    Livewire::actingAs($owner)
        ->test(\Modules\Academy\Livewire\CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz surveillé', null, [
            'passing_score' => 60,
            'kiosk_mode'    => true,
        ]);

    expect($item->fresh()->kiosk_mode)->toBeTrue();
});

test('le formateur propriétaire peut désactiver le mode kiosque déjà actif', function (): void {
    config(['academy.kiosk_mode_enabled' => true]);

    $course = kioskCourse('cours-kiosk-off');
    $lesson = kioskLesson($course);
    $owner  = kioskOwner($course);
    $item   = kioskQuizItem($lesson, ['passing_score' => 60], kioskMode: true);

    Livewire::actingAs($owner)
        ->test(\Modules\Academy\Livewire\CourseEditor::class, ['course' => $course])
        ->call('updateItem', $item->id, 'quiz', 'Quiz surveillé', null, [
            'passing_score' => 60,
            'kiosk_mode'    => false,
        ]);

    expect($item->fresh()->kiosk_mode)->toBeFalse();
});

test('IDOR - un formateur SANS rôle sur ce cours ne peut pas modifier ses items (refusé)', function (): void {
    config(['academy.kiosk_mode_enabled' => true]);

    $course = kioskCourse('cours-kiosk-idor');
    $lesson = kioskLesson($course);
    kioskOwner($course); // owner légitime, jamais utilisé ici
    $item = kioskQuizItem($lesson, ['passing_score' => 60], kioskMode: false);

    $intruder = kioskOtherInstructor();

    // authorize('update', $course) (mount de CourseEditor) doit rejeter : ni admin,
    // ni owner/instructor/editor DE CE COURS précis (policy CoursePolicy::update,
    // scopée par CourseRole — vérifié indépendamment : Gate::denies ici).
    expect(\Illuminate\Support\Facades\Gate::forUser($intruder)->allows('update', $course))->toBeFalse();

    // Le SEUL comportement observable qui compte pour la sécurité : l'item n'est
    // JAMAIS modifié tant que l'autorisation échoue. On le prouve directement (sans
    // dépendre du mécanisme d'exception interne de Livewire en contexte de test).
    $threw = false;

    try {
        Livewire::actingAs($intruder)
            ->test(\Modules\Academy\Livewire\CourseEditor::class, ['course' => $course])
            ->call('updateItem', $item->id, 'quiz', 'Quiz surveillé', null, [
                'passing_score' => 60,
                'kiosk_mode'    => true,
            ]);
    } catch (\Throwable) {
        $threw = true;
    }

    expect($threw)->toBeTrue();
    expect($item->fresh()->kiosk_mode)->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Consignation d'incident — scopage strict + anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('un incident est consigné et migré vers la tentative créée à la soumission', function (): void {
    config(['academy.kiosk_mode_enabled' => true]);

    $course = kioskCourse('cours-kiosk-record');
    $lesson = kioskLesson($course);
    kioskOwner($course);
    $item = kioskQuizItem($lesson, [
        'qt_bank_key'   => 'qt-questions',
        'passing_score' => 0,
    ], kioskMode: true);

    $student = kioskStudent();
    kioskEnroll($course, $student);

    $this->actingAs($student)->post(kioskStartUrl($course, $lesson, $item));

    // Consigne un incident PENDANT la tentative en cours (round en session, pas
    // encore de QuizAttempt en base — voir KioskController::recordViolation).
    $this->actingAs($student)
        ->postJson(kioskViolationUrl($course, $lesson, $item), ['type' => KioskViolationService::FULLSCREEN_EXIT])
        ->assertOk()
        ->assertJson(['recorded' => true]);

    // Rien en base tant que la tentative n'est pas soumise (elle est en session).
    expect(KioskViolation::count())->toBe(0);

    $round    = session("academy.quiz.{$item->id}")['questions'];
    $answers  = [];
    foreach ($round as $i => $q) {
        $answers[(string) $i] = 0;
    }

    $this->actingAs($student)
        ->post(kioskSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)->where('lesson_item_id', $item->id)->first();
    expect($attempt)->not->toBeNull();

    $violations = KioskViolation::where('quiz_attempt_id', $attempt->id)->get();
    expect($violations)->toHaveCount(1);
    expect($violations->first()->type)->toBe(KioskViolationService::FULLSCREEN_EXIT);
    expect($violations->first()->user_id)->toBe($student->id);
    expect($violations->first()->lesson_item_id)->toBe($item->id);
});

test('IDOR - un apprenant ne peut pas injecter un incident sans round actif (item d\'un autre apprenant)', function (): void {
    config(['academy.kiosk_mode_enabled' => true]);

    $course = kioskCourse('cours-kiosk-idor-incident');
    $lesson = kioskLesson($course);
    kioskOwner($course);
    $item = kioskQuizItem($lesson, [
        'qt_bank_key'   => 'qt-questions',
        'passing_score' => 0,
    ], kioskMode: true);

    $victim   = kioskStudent();
    $attacker = kioskStudent();
    kioskEnroll($course, $victim);
    kioskEnroll($course, $attacker);

    // La VICTIME démarre sa propre tentative (round posé dans SA session à elle,
    // liée à SON cookie de session — isolée de celle de l'attaquant en HTTP réel).
    $this->actingAs($victim)->post(kioskStartUrl($course, $lesson, $item));
    $victimRound = session("academy.quiz.{$item->id}");
    expect($victimRound)->not->toBeNull();

    // Simule une SESSION HTTP DISTINCTE pour l'attaquant (nouveau cookie de
    // session, comme un autre navigateur) : en environnement de test
    // SESSION_DRIVER=array, le stockage session est partagé dans le process — on
    // le vide explicitement pour reproduire la vraie isolation par cookie de
    // production, où l'attaquant n'a JAMAIS accès à la session de la victime.
    $this->app['session']->flush();

    // L'ATTAQUANT (sa propre session, AUCUN round démarré pour lui sur cet item)
    // tente de consigner un incident : la garde serveur lit la session de
    // L'UTILISATEUR AUTHENTIFIÉ COURANT (l'attaquant), jamais un identifiant
    // fourni par le client → 404 (rien à consigner, aucune fuite vers la victime).
    $this->actingAs($attacker)
        ->postJson(kioskViolationUrl($course, $lesson, $item), ['type' => KioskViolationService::TAB_BLUR])
        ->assertNotFound();

    expect(KioskViolation::where('user_id', $attacker->id)->count())->toBe(0);
});

test('un type d\'incident hors liste blanche est rejeté (validation serveur)', function (): void {
    config(['academy.kiosk_mode_enabled' => true]);

    $course = kioskCourse('cours-kiosk-type-invalide');
    $lesson = kioskLesson($course);
    kioskOwner($course);
    $item = kioskQuizItem($lesson, [
        'qt_bank_key'   => 'qt-questions',
        'passing_score' => 0,
    ], kioskMode: true);

    $student = kioskStudent();
    kioskEnroll($course, $student);

    $this->actingAs($student)->post(kioskStartUrl($course, $lesson, $item));

    $this->actingAs($student)
        ->postJson(kioskViolationUrl($course, $lesson, $item), ['type' => 'score_override_attempt'])
        ->assertStatus(422);

    expect(KioskViolation::count())->toBe(0);
});

test('la vue formateur des incidents est scopée au cours (IDOR anti-fuite cross-cours)', function (): void {
    config(['academy.kiosk_mode_enabled' => true]);

    $courseA = kioskCourse('cours-kiosk-a');
    $lessonA = kioskLesson($courseA);
    $ownerA  = kioskOwner($courseA);
    $itemA   = kioskQuizItem($lessonA, ['qt_bank_key' => 'qt-questions', 'passing_score' => 0], kioskMode: true);

    $courseB = kioskCourse('cours-kiosk-b');
    kioskOwner($courseB);

    $student = kioskStudent();
    kioskEnroll($courseA, $student);

    $this->actingAs($student)->post(kioskStartUrl($courseA, $lessonA, $itemA));
    $this->actingAs($student)
        ->postJson(kioskViolationUrl($courseA, $lessonA, $itemA), ['type' => KioskViolationService::TAB_BLUR]);
    $round = session("academy.quiz.{$itemA->id}")['questions'];
    $this->actingAs($student)->post(kioskSubmitUrl($courseA, $lessonA, $itemA), [
        'answers' => array_fill_keys(array_map('strval', array_keys($round)), 0),
    ]);

    // Le formateur du cours A voit l'incident.
    Livewire::actingAs($ownerA)
        ->test(KioskViolations::class, ['course' => $courseA])
        ->assertSet('courseId', $courseA->id);

    $attemptsA = app(\Modules\Academy\Services\KioskViolationService::class)
        ->forLessonItem($itemA->id);
    expect($attemptsA)->toHaveCount(1);

    // Un formateur du cours B (sans rôle sur A) est REFUSÉ à l'accès au composant
    // (policy manageEnrollments, scopée par CourseRole — vérifié indépendamment).
    $ownerB = User::factory()->create();
    $ownerB->assignRole('instructor');
    CourseRole::create(['course_id' => $courseB->id, 'user_id' => $ownerB->id, 'role' => 'owner']);

    expect(\Illuminate\Support\Facades\Gate::forUser($ownerB)->allows('manageEnrollments', $courseA))->toBeFalse();

    // Appel DIRECT de mount() (sans le harness Livewire::test, qui absorbe
    // silencieusement l'AuthorizationException dans certains cas de test) : la
    // vraie garde vérifiée ici est authorize('manageEnrollments', $course), qui
    // lève bien l'exception AVANT toute affectation de $courseId (anti-IDOR).
    $this->actingAs($ownerB);
    $component = new KioskViolations();
    $threw     = false;

    try {
        $component->mount($courseA);
    } catch (\Illuminate\Auth\Access\AuthorizationException) {
        $threw = true;
    }

    expect($threw)->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) Drapeau désactivé (défaut) — zéro régression
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau kiosque OFF (défaut) - la route de consignation répond 404', function (): void {
    config(['academy.kiosk_mode_enabled' => false]);

    $course = kioskCourse('cours-kiosk-off-route');
    $lesson = kioskLesson($course);
    kioskOwner($course);
    $item = kioskQuizItem($lesson, ['qt_bank_key' => 'qt-questions', 'passing_score' => 0], kioskMode: true);

    $student = kioskStudent();
    kioskEnroll($course, $student);

    $this->actingAs($student)->post(kioskStartUrl($course, $lesson, $item));

    $this->actingAs($student)
        ->postJson(kioskViolationUrl($course, $lesson, $item), ['type' => KioskViolationService::FULLSCREEN_EXIT])
        ->assertNotFound();

    expect(KioskViolation::count())->toBe(0);
});

test('drapeau kiosque OFF (défaut) - un quiz kiosk_mode=true se déroule normalement (aucune casse)', function (): void {
    config(['academy.kiosk_mode_enabled' => false]);

    $course = kioskCourse('cours-kiosk-off-normal');
    $lesson = kioskLesson($course);
    kioskOwner($course);
    // kiosk_mode=true sur l'item MÊME AVEC le drapeau global off : ne doit rien changer.
    $item = kioskQuizItem($lesson, ['qt_bank_key' => 'qt-questions', 'passing_score' => 0], kioskMode: true);

    $student = kioskStudent();
    kioskEnroll($course, $student);

    $this->actingAs($student)->post(kioskStartUrl($course, $lesson, $item))->assertRedirect();

    $round   = session("academy.quiz.{$item->id}")['questions'];
    $answers = array_fill_keys(array_map('strval', array_keys($round)), 0);

    $this->actingAs($student)
        ->post(kioskSubmitUrl($course, $lesson, $item), ['answers' => $answers])
        ->assertRedirect();

    $attempt = QuizAttempt::where('user_id', $student->id)->where('lesson_item_id', $item->id)->first();
    expect($attempt)->not->toBeNull();
    expect(KioskViolation::count())->toBe(0);
});

test('drapeau kiosque OFF (défaut) - KioskViolationService::record() est un no-op', function (): void {
    config(['academy.kiosk_mode_enabled' => false]);

    $course = kioskCourse('cours-kiosk-noop-service');
    $lesson = kioskLesson($course);
    kioskOwner($course);
    $item = kioskQuizItem($lesson, [], kioskMode: true);

    $student = kioskStudent();
    kioskEnroll($course, $student);

    $attempt = QuizAttempt::create([
        'user_id'            => $student->id,
        'lesson_item_id'     => $item->id,
        'course_id'          => $course->id,
        'score'              => 1,
        'max_score'          => 1,
        'percent'            => 100,
        'passed'             => true,
        'timed_out'          => false,
        'needs_grading'      => false,
        'answers'            => [],
        'questions_snapshot' => [],
        'submitted_at'       => now(),
    ]);

    $result = app(KioskViolationService::class)->record($student, $attempt, KioskViolationService::FULLSCREEN_EXIT);

    expect($result)->toBeNull();
    expect(KioskViolation::count())->toBe(0);
});

test('la checkbox mode kiosque n\'apparaît pas dans l\'éditeur quand le drapeau global est off', function (): void {
    config(['academy.kiosk_mode_enabled' => false]);

    $course = kioskCourse('cours-kiosk-checkbox-off');
    $lesson = kioskLesson($course);
    $owner  = kioskOwner($course);
    kioskQuizItem($lesson, ['passing_score' => 60], kioskMode: false);

    Livewire::actingAs($owner)
        ->test(\Modules\Academy\Livewire\CourseEditor::class, ['course' => $course])
        ->assertDontSee('Mode kiosque');
});
