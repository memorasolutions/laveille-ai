<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - FEEDBACK : nouvelle ACTIVITÉ de leçon « sondage / questionnaire de
 * rétroaction » (multi-questions, NON noté, optionnellement anonyme ; type Moodle
 * « Feedback »). Prouve, de façon AUTONOME (helpers préfixés v4b) :
 *
 *  - création d'un item feedback via l'éditeur (>= 1 question ; rating + choice + text) ;
 *  - soumission valide enregistrée ; question obligatoire non remplie => rejet ;
 *  - rating hors échelle / choix hors options => rejet (bornage serveur) ;
 *  - UNE réponse par étudiant NOMMÉ (re-soumettre MET À JOUR, ne duplique pas) ;
 *  - réponse ANONYME : user_id null + aucune fuite d'identité dans les résultats ;
 *  - résultats RÉSERVÉS au formateur (l'étudiant ne les voit jamais) ;
 *  - achèvement : répondre complète l'item (critère submit par défaut) ;
 *  - sécurité : non-inscrit/anonyme rejeté, anti-IDOR, route throttlée ;
 *  - rétrocompat : items video/document/quiz/choice inchangés.
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
use Modules\Academy\Models\FeedbackParticipant;
use Modules\Academy\Models\FeedbackResponse;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\FeedbackService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe v4b - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v4bCourse(string $slug = 'cours-v4b'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V4-b',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v4bLesson(Course $course): Lesson
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

/**
 * Item feedback. $questions = liste normalisable ; $payload fusionne des réglages.
 */
function v4bFeedbackItem(Lesson $lesson, array $questions = [], array $payload = [], bool $required = false, int $position = 1): LessonItem
{
    if ($questions === []) {
        $questions = [
            ['type' => 'rating', 'label' => 'Votre satisfaction', 'scale' => 5, 'required' => true],
            ['type' => 'choice', 'label' => 'Format préféré', 'options' => ['Présentiel', 'En ligne'], 'required' => false],
            ['type' => 'text', 'label' => 'Commentaires', 'required' => false],
        ];
    }

    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'feedback',
        'title'       => 'Rétroaction '.$position,
        'position'    => $position,
        'payload'     => array_merge([
            'intro'     => 'Aidez-nous à améliorer ce cours.',
            'questions' => $questions,
            'anonymous' => false,
        ], $payload),
        'is_required' => $required,
    ]);
}

function v4bStudent(string $name = 'Étudiant Test'): User
{
    $u = User::factory()->create(['name' => $name]);
    $u->assignRole('student');

    return $u;
}

function v4bOwner(Course $course): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function v4bEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v4bShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

function v4bSubmitUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/feedback/submit";
}

function v4bIsCompleted(User $user, LessonItem $item): bool
{
    return Completion::where('user_id', $user->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE + défaut d'achèvement « submit »
// ─────────────────────────────────────────────────────────────────────────────

test('le critère d\'achèvement par défaut d\'un feedback est « submit »', function (): void {
    $item = v4bFeedbackItem(v4bLesson(v4bCourse()));
    expect(ActivityCompletionService::criterionFor($item))->toBe('submit');
    expect(ActivityCompletionService::allowedForType('feedback'))->toContain('submit');
});

test('FeedbackService normalise questions, intro et anonyme avec défauts', function (): void {
    $item = v4bFeedbackItem(v4bLesson(v4bCourse()));
    $questions = FeedbackService::questions($item);
    expect($questions)->toHaveCount(3);
    expect($questions[0]['type'])->toBe('rating');
    expect($questions[0]['scale'])->toBe(5);
    expect($questions[1]['type'])->toBe('choice');
    expect($questions[1]['options'])->toBe(['Présentiel', 'En ligne']);
    expect($questions[2]['type'])->toBe('text');
    expect(FeedbackService::isAnonymous($item))->toBeFalse();
    expect(FeedbackService::intro($item))->not->toBe('');
});

test('une question choice avec moins de 2 options est écartée à la normalisation', function (): void {
    $questions = FeedbackService::normalizeQuestions([
        ['type' => 'choice', 'label' => 'Q1', 'options' => ['Seule']],
        ['type' => 'text', 'label' => 'Q2'],
        ['type' => 'inconnu', 'label' => 'Q3'],
        ['type' => 'text', 'label' => ''],
    ]);
    expect($questions)->toHaveCount(1);
    expect($questions[0]['type'])->toBe('text');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CRÉATION via l'éditeur (validation >= 1 question)
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant crée un item feedback (rating + choice + text) ; payload bien construit', function (): void {
    $course = v4bCourse('cours-fb-create');
    $lesson = v4bLesson($course);
    $owner  = v4bOwner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Mon sondage de fin')
        ->set("newItem.{$lesson->id}.feedback_intro", 'Merci de répondre.')
        ->set("newItem.{$lesson->id}.anonymous", true)
        ->set("newItem.{$lesson->id}.feedback_questions", [
            ['type' => 'rating', 'label' => 'Note globale', 'scale' => 5, 'required' => true],
            ['type' => 'choice', 'label' => 'Rythme', 'options' => "Trop lent\nBon\nTrop rapide", 'required' => false],
            ['type' => 'text', 'label' => 'Suggestions', 'required' => false],
        ])
        ->call('addItem', $lesson->id, 'feedback')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'feedback')->first();
    expect($item)->not->toBeNull();
    expect($item->payload['intro'])->toBe('Merci de répondre.');
    expect($item->payload['anonymous'])->toBeTrue();
    expect($item->payload['questions'])->toHaveCount(3);
    expect($item->payload['questions'][0])->toMatchArray(['type' => 'rating', 'label' => 'Note globale', 'scale' => 5, 'required' => true]);
    expect($item->payload['questions'][1]['options'])->toBe(['Trop lent', 'Bon', 'Trop rapide']);
    expect($item->payload['questions'][2]['type'])->toBe('text');
});

test('créer un feedback sans question valide est refusé (rien écrit)', function (): void {
    $course = v4bCourse('cours-fb-create-bad');
    $lesson = v4bLesson($course);
    $owner  = v4bOwner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Sondage vide')
        ->set("newItem.{$lesson->id}.feedback_questions", [
            ['type' => 'inconnu', 'label' => 'X'],
            ['type' => 'text', 'label' => ''],
        ])
        ->call('addItem', $lesson->id, 'feedback')
        ->assertHasErrors('feedback_questions');

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'feedback')->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. SOUMISSION + validation/bornage serveur
// ─────────────────────────────────────────────────────────────────────────────

test('soumission valide enregistrée (réponses bornées)', function (): void {
    $course  = v4bCourse('cours-fb-submit');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 4, 1 => 1, 2 => 'Très utile']])
        ->assertRedirect();

    $resp = FeedbackResponse::where('lesson_item_id', $item->id)->where('user_id', $student->id)->first();
    expect($resp)->not->toBeNull();
    expect($resp->answers[0])->toBe(4);
    expect($resp->answers[1])->toBe(1);
    expect($resp->answers[2])->toBe('Très utile');
});

test('question obligatoire non remplie => rejet (aucune réponse)', function (): void {
    $course  = v4bCourse('cours-fb-required');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson); // Q0 (rating) obligatoire
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [1 => 0, 2 => 'texte']])
        ->assertSessionHas('error');

    expect(FeedbackResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('rating hors échelle => rejet', function (): void {
    $course  = v4bCourse('cours-fb-rating-oob');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson); // rating scale 5
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 99]])
        ->assertSessionHas('error');

    expect(FeedbackResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('choix hors options => rejet', function (): void {
    $course  = v4bCourse('cours-fb-choice-oob');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson); // choice Q1 : 2 options (index 0..1)
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 3, 1 => 7]])
        ->assertSessionHas('error');

    expect(FeedbackResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('UNE réponse par étudiant nommé : re-soumettre MET À JOUR (ne duplique pas)', function (): void {
    $course  = v4bCourse('cours-fb-upsert');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 2]])->assertRedirect();
    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 5]])->assertRedirect();

    expect(FeedbackResponse::where('lesson_item_id', $item->id)->where('user_id', $student->id)->count())->toBe(1);
    $resp = FeedbackResponse::where('lesson_item_id', $item->id)->first();
    expect($resp->answers[0])->toBe(5);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ANONYMAT
// ─────────────────────────────────────────────────────────────────────────────

test('réponse anonyme : user_id null + aucune fuite d\'identité dans les résultats', function (): void {
    $course  = v4bCourse('cours-fb-anon');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson, [], ['anonymous' => true]);
    $student = v4bStudent('Jean Tremblay Unique');
    v4bEnroll($course, $student);
    $owner = v4bOwner($course);

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 4, 1 => 0]])->assertRedirect();

    $resp = FeedbackResponse::where('lesson_item_id', $item->id)->first();
    expect($resp)->not->toBeNull();
    expect($resp->user_id)->toBeNull();

    // Le formateur voit les résultats agrégés mais JAMAIS l'identité.
    $this->actingAs($owner)->get(v4bShowUrl($course, $lesson).'?preview=1')
        ->assertOk()
        ->assertSee('academy-feedback-results')
        ->assertDontSee('Jean Tremblay Unique');
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. RÉSULTATS RÉSERVÉS AU FORMATEUR
// ─────────────────────────────────────────────────────────────────────────────

test('résultats visibles au formateur, jamais à l\'étudiant', function (): void {
    $course  = v4bCourse('cours-fb-results');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson);
    $student = v4bStudent();
    v4bEnroll($course, $student);
    $owner = v4bOwner($course);

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 3]])->assertRedirect();

    // Étudiant : jamais de bloc de résultats.
    $this->actingAs($student)->get(v4bShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee('academy-feedback-results');

    // Formateur (prévisualisation) : voit les résultats.
    $this->actingAs($owner)->get(v4bShowUrl($course, $lesson).'?preview=1')
        ->assertOk()
        ->assertSee('academy-feedback-results');
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. ACHÈVEMENT
// ─────────────────────────────────────────────────────────────────────────────

test('répondre complète l\'item feedback (critère submit par défaut)', function (): void {
    $course  = v4bCourse('cours-fb-complete');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson, [], [], true);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    expect(v4bIsCompleted($student, $item))->toBeFalse();

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 4]])->assertRedirect();

    expect(v4bIsCompleted($student, $item))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. SÉCURITÉ
// ─────────────────────────────────────────────────────────────────────────────

test('un visiteur anonyme ne peut pas répondre (redirigé vers la connexion)', function (): void {
    $course = v4bCourse('cours-fb-sec-anon');
    $lesson = v4bLesson($course);
    $item   = v4bFeedbackItem($lesson);

    $this->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 1]])->assertRedirect();
    expect(FeedbackResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('un utilisateur NON inscrit ne peut pas répondre (403)', function (): void {
    $course = v4bCourse('cours-fb-sec-noenroll');
    $lesson = v4bLesson($course);
    $item   = v4bFeedbackItem($lesson);
    $user   = v4bStudent(); // existe mais N'EST PAS inscrit

    $this->actingAs($user)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 1]])->assertForbidden();
    expect(FeedbackResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('ANTI-IDOR : répondre sur un item d\'un AUTRE cours est refusé (404)', function (): void {
    $courseA = v4bCourse('cours-fb-idor-a');
    $lessonA = v4bLesson($courseA);

    $courseB = v4bCourse('cours-fb-idor-b');
    $lessonB = v4bLesson($courseB);
    $itemB   = v4bFeedbackItem($lessonB);

    $student = v4bStudent();
    v4bEnroll($courseA, $student); // inscrit à A seulement

    $this->actingAs($student)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/feedback/submit", ['answers' => [0 => 1]])
        ->assertNotFound();

    expect(FeedbackResponse::where('lesson_item_id', $itemB->id)->count())->toBe(0);
});

test('la route de soumission est throttlée (429 après le quota)', function (): void {
    $course  = v4bCourse('cours-fb-throttle');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $statuses = [];
    for ($i = 0; $i < 25; $i++) {
        $statuses[] = $this->actingAs($student)
            ->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 4]])
            ->getStatusCode();
    }

    expect($statuses)->toContain(429);
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. RÉTROCOMPAT
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : répondre sur un item NON-feedback (document) est refusé (404)', function (): void {
    $course  = v4bCourse('cours-fb-retro');
    $lesson  = v4bLesson($course);
    $doc     = LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'document',
        'title'     => 'Doc',
        'position'  => 1,
        'payload'   => ['rich_text' => 'Notes'],
    ]);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $doc), ['answers' => [0 => 1]])->assertNotFound();
    expect(FeedbackResponse::where('lesson_item_id', $doc->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 9. C1 - ANTI RE-SPAM ANONYME ROBUSTE (participation en base, anti-reconnexion)
// ─────────────────────────────────────────────────────────────────────────────

test('C1 : feedback anonyme - 1re soumission enregistre une réponse user_id NULL + une participation', function (): void {
    $course  = v4bCourse('cours-c1-anon-1');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson, [], ['anonymous' => true]);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 4, 1 => 0]])
        ->assertRedirect();

    // La réponse reste anonyme (user_id NULL) : aucune identité dans la réponse.
    $resp = FeedbackResponse::where('lesson_item_id', $item->id)->first();
    expect($resp)->not->toBeNull();
    expect($resp->user_id)->toBeNull();

    // La participation est tracée (le FAIT de répondre), liée à l'étudiant.
    expect(FeedbackParticipant::where('lesson_item_id', $item->id)->where('user_id', $student->id)->exists())->toBeTrue();
});

test('C1 : feedback anonyme - 2e soumission du MÊME user refusée même après reconnexion (session régénérée)', function (): void {
    $course  = v4bCourse('cours-c1-anon-respam');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson, [], ['anonymous' => true]);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 4]])->assertRedirect();

    // Simule une déconnexion/reconnexion : la session (et son drapeau) est régénérée.
    $this->flushSession();

    $this->actingAs($student)
        ->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 1]])
        ->assertRedirect()
        ->assertSessionHas('info');

    // UNE seule réponse anonyme : le re-spam est borné par la participation en base.
    expect(FeedbackResponse::where('lesson_item_id', $item->id)->count())->toBe(1);
    expect(FeedbackParticipant::where('lesson_item_id', $item->id)->count())->toBe(1);
});

test('C1 : feedback anonyme - un AUTRE user peut répondre ; anonymat préservé dans l\'agrégat', function (): void {
    $course  = v4bCourse('cours-c1-anon-other');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson, [], ['anonymous' => true]);
    $alice   = v4bStudent('Alice Unique Nom');
    $bob     = v4bStudent('Bob Autre Nom');
    v4bEnroll($course, $alice);
    v4bEnroll($course, $bob);

    $this->actingAs($alice)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 5]])->assertRedirect();
    $this->flushSession();
    $this->actingAs($bob)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 2]])->assertRedirect();

    // Deux réponses anonymes (user_id NULL), deux participations distinctes.
    expect(FeedbackResponse::where('lesson_item_id', $item->id)->count())->toBe(2);
    expect(FeedbackResponse::where('lesson_item_id', $item->id)->whereNull('user_id')->count())->toBe(2);
    expect(FeedbackParticipant::where('lesson_item_id', $item->id)->count())->toBe(2);

    // L'agrégat ne contient AUCUNE identité.
    $results = FeedbackService::results($item);
    expect($results['total'])->toBe(2);
    $json = json_encode($results);
    expect($json)->not->toContain('Alice Unique Nom');
    expect($json)->not->toContain('Bob Autre Nom');
});

test('C1 : feedback NOMMÉ - upsert modifiable inchangé + participation tracée', function (): void {
    $course  = v4bCourse('cours-c1-named');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson); // non anonyme
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 2]])->assertRedirect();
    $this->flushSession();
    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 5]])->assertRedirect();

    // Toujours UNE réponse nommée, modifiable (dernière valeur), participation idempotente.
    expect(FeedbackResponse::where('lesson_item_id', $item->id)->where('user_id', $student->id)->count())->toBe(1);
    expect(FeedbackResponse::where('lesson_item_id', $item->id)->first()->answers[0])->toBe(5);
    expect(FeedbackParticipant::where('lesson_item_id', $item->id)->where('user_id', $student->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 10. C2 - AUCUNE requête dans la vue (pré-remplissage via le contrôleur)
// ─────────────────────────────────────────────────────────────────────────────

test('C2 : la vue lesson.blade ne contient AUCUNE requête FeedbackResponse', function (): void {
    $blade = file_get_contents(__DIR__.'/../../resources/views/public/lesson.blade.php');
    expect($blade)->not->toContain('FeedbackResponse::');
});

test('C2 : pré-remplissage NOMMÉ fonctionne (réponses précédentes affichées, via le contrôleur)', function (): void {
    $course  = v4bCourse('cours-c2-prefill');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson); // Q2 = text
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 4, 2 => 'Mon commentaire prerempli']])
        ->assertRedirect();

    $this->actingAs($student)->get(v4bShowUrl($course, $lesson))
        ->assertOk()
        ->assertSee('Mon commentaire prerempli');
});

test('C2 : previousAnswers retourne vide pour un sondage anonyme', function (): void {
    $course  = v4bCourse('cours-c2-anon-noprefill');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson, [], ['anonymous' => true]);
    $student = v4bStudent();

    expect(FeedbackService::previousAnswers($item, $student, null))->toBe([]);
});

// ─────────────────────────────────────────────────────────────────────────────
// 11. C3 - SoftDeletes (audit trail) sur les réponses
// ─────────────────────────────────────────────────────────────────────────────

test('C3 : FeedbackResponse est softdeletable (conservé withTrashed, exclu par défaut)', function (): void {
    $course  = v4bCourse('cours-c3-softdelete');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 3]])->assertRedirect();
    $resp = FeedbackResponse::where('lesson_item_id', $item->id)->first();
    $resp->delete();

    // Exclue des requêtes normales (donc des agrégats), conservée withTrashed.
    expect(FeedbackResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
    expect(FeedbackResponse::withTrashed()->where('lesson_item_id', $item->id)->count())->toBe(1);
    expect(FeedbackService::results($item)['total'])->toBe(0);
});

test('C3 : re-soumettre après soft-delete restaure/met à jour sans violer l\'UNIQUE(item, user)', function (): void {
    $course  = v4bCourse('cours-c3-resubmit');
    $lesson  = v4bLesson($course);
    $item    = v4bFeedbackItem($lesson);
    $student = v4bStudent();
    v4bEnroll($course, $student);

    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 2]])->assertRedirect();
    FeedbackResponse::where('lesson_item_id', $item->id)->first()->delete();

    // Nouvelle soumission : aucune erreur de contrainte unique, la ligne est restaurée+MAJ.
    $this->actingAs($student)->post(v4bSubmitUrl($course, $lesson, $item), ['answers' => [0 => 5]])->assertRedirect();

    expect(FeedbackResponse::withTrashed()->where('lesson_item_id', $item->id)->where('user_id', $student->id)->count())->toBe(1);
    $resp = FeedbackResponse::where('lesson_item_id', $item->id)->where('user_id', $student->id)->first();
    expect($resp)->not->toBeNull();
    expect($resp->trashed())->toBeFalse();
    expect($resp->answers[0])->toBe(5);
});

test('rétrocompat : les défauts d\'achèvement video/document/quiz/choice sont inchangés', function (): void {
    $lesson = v4bLesson(v4bCourse('cours-fb-retro-defaults'));
    $video  = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'V', 'position' => 1, 'payload' => []]);
    $doc    = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'document', 'title' => 'D', 'position' => 2, 'payload' => []]);
    $quiz   = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Q', 'position' => 3, 'payload' => []]);
    $choice = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'choice', 'title' => 'C', 'position' => 4, 'payload' => ['options' => ['A', 'B']]]);

    expect(ActivityCompletionService::criterionFor($video))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($doc))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($quiz))->toBe('min_grade');
    expect(ActivityCompletionService::criterionFor($choice))->toBe('vote');
});
