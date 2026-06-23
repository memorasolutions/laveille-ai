<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - CHOICE : nouvelle ACTIVITÉ de leçon « sondage / vote simple » (non noté,
 * type Moodle « Choice »). Prouve, de façon AUTONOME (helpers préfixés v4a) :
 *
 *  - création d'un item choice via l'éditeur (validation : >= 2 options) ;
 *  - vote à choix unique ET à choix multiple, choix bornés aux options du payload ;
 *  - UN vote par étudiant (re-voter MET À JOUR, ne duplique pas) ;
 *  - choix hors options rejeté (anti-forge) ;
 *  - visibilité des résultats : after_vote (cachés avant vote, visibles après),
 *    never (cachés à l'étudiant, visibles au formateur), anonymous (pas de fuite d'id) ;
 *  - achèvement V2-c : voter complète l'item ;
 *  - sécurité : non-inscrit/anonyme rejeté, anti-IDOR (item d'un autre cours) ;
 *  - rétrocompat : items video/document/quiz inchangés.
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\CourseEditor;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\ChoiceResponse;
use Modules\Academy\Models\Completion;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\ChoiceService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe v4a - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v4aCourse(string $slug = 'cours-v4a'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V4-a',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v4aLesson(Course $course): Lesson
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

function v4aChoiceItem(Lesson $lesson, array $payload = [], bool $required = false, int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'choice',
        'title'       => 'Sondage '.$position,
        'position'    => $position,
        'payload'     => array_merge([
            'question' => 'Quelle est votre préférence ?',
            'options'  => ['Option A', 'Option B', 'Option C'],
        ], $payload),
        'is_required' => $required,
    ]);
}

function v4aStudent(string $name = 'Étudiant Test'): User
{
    $u = User::factory()->create(['name' => $name]);
    $u->assignRole('student');

    return $u;
}

function v4aOwner(Course $course): User
{
    $u = User::factory()->create();
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function v4aEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v4aShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

function v4aVoteUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/choice/vote";
}

function v4aIsCompleted(User $user, LessonItem $item): bool
{
    return Completion::where('user_id', $user->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE + défaut d'achèvement « vote »
// ─────────────────────────────────────────────────────────────────────────────

test('le critère d\'achèvement par défaut d\'un choice est « vote »', function (): void {
    $item = v4aChoiceItem(v4aLesson(v4aCourse()));
    expect(ActivityCompletionService::criterionFor($item))->toBe('vote');
    expect(ActivityCompletionService::allowedForType('choice'))->toContain('vote');
});

test('ChoiceService lit options, multiple, anonyme et visibilité avec défauts', function (): void {
    $item = v4aChoiceItem(v4aLesson(v4aCourse()), ['allow_multiple' => true]);
    expect(ChoiceService::options($item))->toBe(['Option A', 'Option B', 'Option C']);
    expect(ChoiceService::allowsMultiple($item))->toBeTrue();
    expect(ChoiceService::isAnonymous($item))->toBeFalse();
    expect(ChoiceService::visibility($item))->toBe('after_vote'); // défaut
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CRÉATION via l'éditeur (validation >= 2 options)
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant crée un item choice (>= 2 options) ; le payload est bien construit', function (): void {
    $course = v4aCourse('cours-create');
    $lesson = v4aLesson($course);
    $owner  = v4aOwner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Mon sondage')
        ->set("newItem.{$lesson->id}.choice_question", 'Café ou thé ?')
        ->set("newItem.{$lesson->id}.choice_options", "Café\nThé\nNi l'un ni l'autre")
        ->set("newItem.{$lesson->id}.allow_multiple", true)
        ->set("newItem.{$lesson->id}.results_visibility", 'always')
        ->call('addItem', $lesson->id, 'choice')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'choice')->first();
    expect($item)->not->toBeNull();
    expect($item->payload['question'])->toBe('Café ou thé ?');
    expect($item->payload['options'])->toBe(['Café', 'Thé', "Ni l'un ni l'autre"]);
    expect($item->payload['allow_multiple'])->toBeTrue();
    expect($item->payload['results_visibility'])->toBe('always');
});

test('créer un choice avec moins de 2 options est refusé (rien écrit)', function (): void {
    $course = v4aCourse('cours-create-bad');
    $lesson = v4aLesson($course);
    $owner  = v4aOwner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Sondage incomplet')
        ->set("newItem.{$lesson->id}.choice_question", 'Une seule option ?')
        ->set("newItem.{$lesson->id}.choice_options", 'Seule option')
        ->call('addItem', $lesson->id, 'choice')
        ->assertHasErrors('choice_options');

    expect(LessonItem::where('lesson_id', $lesson->id)->where('type', 'choice')->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. VOTE (choix unique + multiple) + bornes + 1 vote/user
// ─────────────────────────────────────────────────────────────────────────────

test('vote à choix unique : un seul choix conservé', function (): void {
    $course  = v4aCourse('cours-vote-single');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson);
    $student = v4aStudent();
    v4aEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 1])
        ->assertRedirect();

    $resp = ChoiceResponse::where('lesson_item_id', $item->id)->where('user_id', $student->id)->first();
    expect($resp)->not->toBeNull();
    expect($resp->choices)->toBe([1]);
});

test('vote à choix multiple : plusieurs choix conservés', function (): void {
    $course  = v4aCourse('cours-vote-multi');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson, ['allow_multiple' => true]);
    $student = v4aStudent();
    v4aEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4aVoteUrl($course, $lesson, $item), ['choices' => [0, 2]])
        ->assertRedirect();

    $resp = ChoiceResponse::where('lesson_item_id', $item->id)->where('user_id', $student->id)->first();
    expect($resp->choices)->toBe([0, 2]);
});

test('choix unique : un tableau de plusieurs choix est réduit à un seul', function (): void {
    $course  = v4aCourse('cours-vote-single-coerce');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson); // allow_multiple = false
    $student = v4aStudent();
    v4aEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4aVoteUrl($course, $lesson, $item), ['choices' => [0, 1, 2]])
        ->assertRedirect();

    $resp = ChoiceResponse::where('lesson_item_id', $item->id)->first();
    expect(count($resp->choices))->toBe(1);
});

test('un choix hors options est rejeté (aucune réponse enregistrée)', function (): void {
    $course  = v4aCourse('cours-vote-oob');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson); // 3 options (index 0..2)
    $student = v4aStudent();
    v4aEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 99])
        ->assertSessionHas('error');

    expect(ChoiceResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('UN vote par étudiant : re-voter MET À JOUR la même ligne (ne duplique pas)', function (): void {
    $course  = v4aCourse('cours-vote-unique');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson);
    $student = v4aStudent();
    v4aEnroll($course, $student);

    $this->actingAs($student)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0])->assertRedirect();
    $this->actingAs($student)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 2])->assertRedirect();

    expect(ChoiceResponse::where('lesson_item_id', $item->id)->where('user_id', $student->id)->count())->toBe(1);
    $resp = ChoiceResponse::where('lesson_item_id', $item->id)->first();
    expect($resp->choices)->toBe([2]);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ACHÈVEMENT V2-c : voter complète l'item
// ─────────────────────────────────────────────────────────────────────────────

test('voter complète l\'item choice (critère vote par défaut)', function (): void {
    $course  = v4aCourse('cours-vote-complete');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson, [], true);
    $student = v4aStudent();
    v4aEnroll($course, $student);

    expect(v4aIsCompleted($student, $item))->toBeFalse();

    $this->actingAs($student)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0])->assertRedirect();

    expect(v4aIsCompleted($student, $item))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. VISIBILITÉ DES RÉSULTATS
// ─────────────────────────────────────────────────────────────────────────────

test('after_vote : résultats cachés avant le vote, visibles après', function (): void {
    $course  = v4aCourse('cours-vis-aftervote');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson, ['results_visibility' => 'after_vote']);
    $student = v4aStudent();
    v4aEnroll($course, $student);

    // Avant le vote : pas de bloc de résultats.
    $this->actingAs($student)->get(v4aShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee('academy-choice-results');

    // Vote, puis les résultats apparaissent.
    $this->actingAs($student)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0]);

    $this->actingAs($student)->get(v4aShowUrl($course, $lesson))
        ->assertOk()
        ->assertSee('academy-choice-results');
});

test('never : résultats cachés à l\'étudiant (même après vote) mais visibles au formateur', function (): void {
    $course  = v4aCourse('cours-vis-never');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson, ['results_visibility' => 'never']);
    $student = v4aStudent();
    v4aEnroll($course, $student);
    $owner = v4aOwner($course);

    $this->actingAs($student)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0]);

    // Étudiant : jamais de résultats.
    $this->actingAs($student)->get(v4aShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee('academy-choice-results');

    // Formateur (prévisualisation) : voit toujours les résultats.
    $this->actingAs($owner)->get(v4aShowUrl($course, $lesson).'?preview=1')
        ->assertOk()
        ->assertSee('academy-choice-results');
});

test('anonymous : le formateur ne voit PAS l\'identité des votants', function (): void {
    $course  = v4aCourse('cours-anon');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson, ['anonymous' => true, 'results_visibility' => 'always']);
    $student = v4aStudent('Jean Tremblay Unique');
    v4aEnroll($course, $student);
    $owner = v4aOwner($course);

    $this->actingAs($student)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0]);

    $this->actingAs($owner)->get(v4aShowUrl($course, $lesson).'?preview=1')
        ->assertOk()
        ->assertSee('academy-choice-results')
        ->assertDontSee('Jean Tremblay Unique');
});

test('non anonyme : le formateur voit la liste des votants', function (): void {
    $course  = v4aCourse('cours-nonanon');
    $lesson  = v4aLesson($course);
    $item    = v4aChoiceItem($lesson, ['anonymous' => false, 'results_visibility' => 'always']);
    $student = v4aStudent('Marie Lambert Unique');
    v4aEnroll($course, $student);
    $owner = v4aOwner($course);

    $this->actingAs($student)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0]);

    $this->actingAs($owner)->get(v4aShowUrl($course, $lesson).'?preview=1')
        ->assertOk()
        ->assertSee('Marie Lambert Unique');
});

test('un étudiant ne voit jamais la liste des votants (même non anonyme)', function (): void {
    $course   = v4aCourse('cours-noleak');
    $lesson   = v4aLesson($course);
    $item     = v4aChoiceItem($lesson, ['anonymous' => false, 'results_visibility' => 'always']);
    $voter    = v4aStudent('Votant Secret Unique');
    $observer = v4aStudent('Observateur');
    v4aEnroll($course, $voter);
    v4aEnroll($course, $observer);

    $this->actingAs($voter)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0]);

    // Un autre étudiant voit les résultats (always) mais JAMAIS l'identité d'un votant.
    $this->actingAs($observer)->get(v4aShowUrl($course, $lesson))
        ->assertOk()
        ->assertSee('academy-choice-results')
        ->assertDontSee('Votant Secret Unique');
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. SÉCURITÉ : non-inscrit / anonyme rejeté, anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('un visiteur anonyme ne peut pas voter (redirigé vers la connexion)', function (): void {
    $course = v4aCourse('cours-sec-anon');
    $lesson = v4aLesson($course);
    $item   = v4aChoiceItem($lesson);

    // La route de vote est sous le middleware « auth » : un invité est redirigé (302),
    // jamais traité. Aucune réponse n'est enregistrée.
    $this->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0])->assertRedirect();
    expect(ChoiceResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('un utilisateur NON inscrit ne peut pas voter (403)', function (): void {
    $course = v4aCourse('cours-sec-noenroll');
    $lesson = v4aLesson($course);
    $item   = v4aChoiceItem($lesson);
    $user   = v4aStudent(); // existe mais N'EST PAS inscrit

    $this->actingAs($user)->post(v4aVoteUrl($course, $lesson, $item), ['choice' => 0])->assertForbidden();
    expect(ChoiceResponse::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('ANTI-IDOR : voter sur un item d\'un AUTRE cours est refusé (404)', function (): void {
    $courseA = v4aCourse('cours-idor-a');
    $lessonA = v4aLesson($courseA);

    $courseB = v4aCourse('cours-idor-b');
    $lessonB = v4aLesson($courseB);
    $itemB   = v4aChoiceItem($lessonB);

    $student = v4aStudent();
    v4aEnroll($courseA, $student); // inscrit à A seulement

    // Tente de voter l'item de B via les paramètres de cours/leçon de A.
    $this->actingAs($student)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/choice/vote", ['choice' => 0])
        ->assertNotFound();

    expect(ChoiceResponse::where('lesson_item_id', $itemB->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. RÉTROCOMPAT : les autres types restent inchangés
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : voter sur un item NON-choice (document) est refusé (404)', function (): void {
    $course  = v4aCourse('cours-retro');
    $lesson  = v4aLesson($course);
    $doc     = LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'document',
        'title'     => 'Doc',
        'position'  => 1,
        'payload'   => ['rich_text' => 'Notes'],
    ]);
    $student = v4aStudent();
    v4aEnroll($course, $student);

    $this->actingAs($student)->post(v4aVoteUrl($course, $lesson, $doc), ['choice' => 0])->assertNotFound();
    expect(ChoiceResponse::where('lesson_item_id', $doc->id)->count())->toBe(0);
});

test('rétrocompat : les défauts d\'achèvement video/document/quiz sont inchangés', function (): void {
    $lesson = v4aLesson(v4aCourse('cours-retro-defaults'));
    $video  = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'V', 'position' => 1, 'payload' => []]);
    $doc    = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'document', 'title' => 'D', 'position' => 2, 'payload' => []]);
    $quiz   = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Q', 'position' => 3, 'payload' => []]);

    expect(ActivityCompletionService::criterionFor($video))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($doc))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($quiz))->toBe('min_grade');
});
