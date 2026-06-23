<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - FORUM : nouvelle ACTIVITÉ de leçon « forum de discussion » (sujets +
 * réponses attachés à une leçon ; type Moodle « Forum »). Prouve, de façon AUTONOME
 * (helpers préfixés v4c) :
 *
 *  - création d'un item forum via l'éditeur (intro / allow_student_topics / locked) ;
 *  - un inscrit ouvre un sujet + répond ; achèvement « post » par défaut ;
 *  - honeypot rempli => rejet SILENCIEUX (aucune écriture) ;
 *  - locked => réponse/sujet refusés (sauf gérant) ; allow_student_topics=false =>
 *    l'étudiant ne crée pas de sujet mais peut répondre ;
 *  - modération gérant : épingler / verrouiller (bascule) + supprimer (soft-delete) ;
 *    non-gérant 403 ; anti-IDOR (sujet d'un autre cours => 404) ;
 *  - anti-XSS : un body avec <script> est neutralisé au rendu (renderRichText) ;
 *  - sécurité : non-inscrit/anonyme rejeté, anti-IDOR item, route throttlée, bornes ;
 *  - rétrocompat : items video/document/quiz/choice/feedback inchangés.
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
use Modules\Academy\Models\ForumPost;
use Modules\Academy\Models\ForumTopic;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\ForumService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe v4c - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v4cCourse(string $slug = 'cours-v4c'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V4-c',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v4cLesson(Course $course): Lesson
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

function v4cForumItem(Lesson $lesson, array $payload = [], int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'forum',
        'title'       => 'Forum '.$position,
        'position'    => $position,
        'payload'     => array_merge([
            'intro'                => 'Discutons de cette leçon.',
            'allow_student_topics' => true,
            'locked'               => false,
        ], $payload),
        'is_required' => false,
    ]);
}

function v4cStudent(string $name = 'Étudiant Test'): User
{
    $u = User::factory()->create(['name' => $name]);
    $u->assignRole('student');

    return $u;
}

function v4cOwner(Course $course): User
{
    $u = User::factory()->create(['name' => 'Formateur Test']);
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function v4cEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v4cShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

function v4cCreateUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/topics";
}

function v4cReplyUrl(Course $course, Lesson $lesson, LessonItem $item, ForumTopic $topic): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/topics/{$topic->id}/reply";
}

function v4cTopic(LessonItem $item, ?User $user = null, array $attrs = []): ForumTopic
{
    return ForumTopic::create(array_merge([
        'lesson_item_id' => $item->id,
        'user_id'        => $user?->id,
        'title'          => 'Sujet existant',
        'body'           => 'Corps du sujet',
    ], $attrs));
}

function v4cIsCompleted(User $user, LessonItem $item): bool
{
    return Completion::where('user_id', $user->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists();
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE + défaut d'achèvement « post »
// ─────────────────────────────────────────────────────────────────────────────

test('le critère d\'achèvement par défaut d\'un forum est « post »', function (): void {
    $item = v4cForumItem(v4cLesson(v4cCourse()));
    expect(ActivityCompletionService::criterionFor($item))->toBe('post');
    expect(ActivityCompletionService::allowedForType('forum'))->toContain('post');
});

test('ForumService lit la configuration avec ses défauts', function (): void {
    $item = LessonItem::create([
        'lesson_id' => v4cLesson(v4cCourse())->id,
        'type'      => 'forum',
        'title'     => 'F',
        'position'  => 1,
        'payload'   => [], // payload minimal
    ]);
    expect(ForumService::allowsStudentTopics($item))->toBeTrue();  // défaut true
    expect(ForumService::isLocked($item))->toBeFalse();             // défaut false
    expect(ForumService::intro($item))->toBe('');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CRÉATION via l'éditeur
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant crée un item forum ; payload bien construit', function (): void {
    $course = v4cCourse('cours-forum-create');
    $lesson = v4cLesson($course);
    $owner  = v4cOwner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Forum de la leçon')
        ->set("newItem.{$lesson->id}.forum_intro", 'Posez vos questions ici.')
        ->set("newItem.{$lesson->id}.allow_student_topics", false)
        ->set("newItem.{$lesson->id}.locked", true)
        ->call('addItem', $lesson->id, 'forum')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'forum')->first();
    expect($item)->not->toBeNull();
    expect($item->payload['intro'])->toBe('Posez vos questions ici.');
    expect($item->payload['allow_student_topics'])->toBeFalse();
    expect($item->payload['locked'])->toBeTrue();
});

test('un item forum créé sans réglage d\'ouverture autorise les sujets étudiants par défaut', function (): void {
    $course = v4cCourse('cours-forum-default');
    $lesson = v4cLesson($course);
    $owner  = v4cOwner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Forum libre')
        ->call('addItem', $lesson->id, 'forum')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'forum')->first();
    expect(ForumService::allowsStudentTopics($item))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. OUVRIR UN SUJET + RÉPONDRE (inscrit)
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit ouvre un sujet puis répond', function (): void {
    $course  = v4cCourse('cours-forum-flow');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4cCreateUrl($course, $lesson, $item), ['title' => 'Ma question', 'body' => 'Comment fait-on ?'])
        ->assertRedirect();

    $topic = ForumTopic::where('lesson_item_id', $item->id)->first();
    expect($topic)->not->toBeNull();
    expect($topic->title)->toBe('Ma question');
    expect($topic->user_id)->toBe($student->id);

    $this->actingAs($student)
        ->post(v4cReplyUrl($course, $lesson, $item, $topic), ['body' => 'Voici un complément.'])
        ->assertRedirect();

    expect(ForumPost::where('topic_id', $topic->id)->count())->toBe(1);
});

test('publier un sujet complète l\'item forum (critère post par défaut)', function (): void {
    $course  = v4cCourse('cours-forum-complete');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    expect(v4cIsCompleted($student, $item))->toBeFalse();

    $this->actingAs($student)
        ->post(v4cCreateUrl($course, $lesson, $item), ['title' => 'Sujet', 'body' => 'Texte'])
        ->assertRedirect();

    expect(v4cIsCompleted($student, $item))->toBeTrue();
});

test('répondre complète aussi l\'item forum (critère post)', function (): void {
    $course  = v4cCourse('cours-forum-complete-reply');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $owner   = v4cOwner($course);
    $topic   = v4cTopic($item, $owner); // sujet ouvert par le formateur
    $student = v4cStudent();
    v4cEnroll($course, $student);

    expect(v4cIsCompleted($student, $item))->toBeFalse();

    $this->actingAs($student)
        ->post(v4cReplyUrl($course, $lesson, $item, $topic), ['body' => 'Ma réponse'])
        ->assertRedirect();

    expect(v4cIsCompleted($student, $item))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. HONEYPOT (anti-spam MAISON) - rejet silencieux
// ─────────────────────────────────────────────────────────────────────────────

test('honeypot rempli => sujet rejeté SILENCIEUSEMENT (aucune écriture)', function (): void {
    $course  = v4cCourse('cours-forum-hp');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4cCreateUrl($course, $lesson, $item), [
            'title'                  => 'Sujet spam',
            'body'                   => 'Contenu spam',
            ForumService::HONEYPOT   => 'http://spam.example',
        ])
        ->assertRedirect();

    expect(ForumTopic::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('honeypot rempli => réponse rejetée SILENCIEUSEMENT', function (): void {
    $course  = v4cCourse('cours-forum-hp-reply');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $student = v4cStudent();
    v4cEnroll($course, $student);
    $topic = v4cTopic($item, $student);

    $this->actingAs($student)
        ->post(v4cReplyUrl($course, $lesson, $item, $topic), [
            'body'                 => 'spam',
            ForumService::HONEYPOT => 'x',
        ])
        ->assertRedirect();

    expect(ForumPost::where('topic_id', $topic->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. VERROUILLAGE + allow_student_topics
// ─────────────────────────────────────────────────────────────────────────────

test('forum verrouillé : un étudiant ne peut pas répondre (403), un gérant oui', function (): void {
    $course  = v4cCourse('cours-forum-locked');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson, ['locked' => true]);
    $owner   = v4cOwner($course);
    $topic   = v4cTopic($item, $owner);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4cReplyUrl($course, $lesson, $item, $topic), ['body' => 'tentative'])
        ->assertForbidden();
    expect(ForumPost::where('topic_id', $topic->id)->count())->toBe(0);

    // Le gérant peut tout de même contribuer.
    $this->actingAs($owner)
        ->post(v4cReplyUrl($course, $lesson, $item, $topic), ['body' => 'réponse gérant'])
        ->assertRedirect();
    expect(ForumPost::where('topic_id', $topic->id)->count())->toBe(1);
});

test('sujet verrouillé : réponse refusée à l\'étudiant (403)', function (): void {
    $course  = v4cCourse('cours-forum-topic-locked');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $owner   = v4cOwner($course);
    $topic   = v4cTopic($item, $owner, ['is_locked' => true]);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4cReplyUrl($course, $lesson, $item, $topic), ['body' => 'tentative'])
        ->assertForbidden();
    expect(ForumPost::where('topic_id', $topic->id)->count())->toBe(0);
});

test('allow_student_topics=false : l\'étudiant ne crée pas de sujet (403) mais peut répondre', function (): void {
    $course  = v4cCourse('cours-forum-noopen');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson, ['allow_student_topics' => false]);
    $owner   = v4cOwner($course);
    $topic   = v4cTopic($item, $owner);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    // Pas de création de sujet.
    $this->actingAs($student)
        ->post(v4cCreateUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])
        ->assertForbidden();
    expect(ForumTopic::where('lesson_item_id', $item->id)->where('user_id', $student->id)->count())->toBe(0);

    // Mais la réponse aux sujets existants reste permise.
    $this->actingAs($student)
        ->post(v4cReplyUrl($course, $lesson, $item, $topic), ['body' => 'réponse permise'])
        ->assertRedirect();
    expect(ForumPost::where('topic_id', $topic->id)->count())->toBe(1);
});

test('allow_student_topics=false : un GÉRANT peut tout de même ouvrir un sujet', function (): void {
    $course = v4cCourse('cours-forum-noopen-mgr');
    $lesson = v4cLesson($course);
    $item   = v4cForumItem($lesson, ['allow_student_topics' => false]);
    $owner  = v4cOwner($course);

    $this->actingAs($owner)
        ->post(v4cCreateUrl($course, $lesson, $item), ['title' => 'Annonce', 'body' => 'Bienvenue'])
        ->assertRedirect();
    expect(ForumTopic::where('lesson_item_id', $item->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. MODÉRATION (gérant) + soft-delete + anti-IDOR
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant épingle, verrouille et supprime un sujet (soft-delete)', function (): void {
    $course = v4cCourse('cours-forum-mod');
    $lesson = v4cLesson($course);
    $item   = v4cForumItem($lesson);
    $owner  = v4cOwner($course);
    $topic  = v4cTopic($item, $owner);

    $base = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/topics/{$topic->id}";

    $this->actingAs($owner)->post($base.'/pin')->assertRedirect();
    expect($topic->fresh()->is_pinned)->toBeTrue();

    $this->actingAs($owner)->post($base.'/lock')->assertRedirect();
    expect($topic->fresh()->is_locked)->toBeTrue();

    $this->actingAs($owner)->post($base.'/delete')->assertRedirect();
    expect(ForumTopic::where('id', $topic->id)->count())->toBe(0);                 // exclu par défaut
    expect(ForumTopic::withTrashed()->where('id', $topic->id)->count())->toBe(1);  // conservé (audit)
});

test('un gérant supprime une réponse (soft-delete)', function (): void {
    $course = v4cCourse('cours-forum-delpost');
    $lesson = v4cLesson($course);
    $item   = v4cForumItem($lesson);
    $owner  = v4cOwner($course);
    $topic  = v4cTopic($item, $owner);
    $post   = ForumPost::create(['topic_id' => $topic->id, 'user_id' => $owner->id, 'body' => 'à supprimer']);

    $this->actingAs($owner)
        ->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/posts/{$post->id}/delete")
        ->assertRedirect();

    expect(ForumPost::where('id', $post->id)->count())->toBe(0);
    expect(ForumPost::withTrashed()->where('id', $post->id)->count())->toBe(1);
});

test('un NON-gérant ne peut pas modérer (403)', function (): void {
    $course  = v4cCourse('cours-forum-mod-403');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $owner   = v4cOwner($course);
    $topic   = v4cTopic($item, $owner);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    $this->actingAs($student)
        ->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/topics/{$topic->id}/pin")
        ->assertForbidden();
    expect($topic->fresh()->is_pinned)->toBeFalse();
});

test('ANTI-IDOR modération : un gérant ne peut pas modérer le sujet d\'un AUTRE cours (404)', function (): void {
    $courseA = v4cCourse('cours-forum-idor-a');
    $lessonA = v4cLesson($courseA);
    $itemA   = v4cForumItem($lessonA);
    $ownerA  = v4cOwner($courseA);

    $courseB = v4cCourse('cours-forum-idor-b');
    $lessonB = v4cLesson($courseB);
    $itemB   = v4cForumItem($lessonB);
    $topicB  = v4cTopic($itemB);

    // ownerA passe sa propre route (courseA/itemA) mais cible le topic de B.
    $this->actingAs($ownerA)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemA->id}/forum/topics/{$topicB->id}/pin")
        ->assertNotFound();
    expect($topicB->fresh()->is_pinned)->toBeFalse();
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. ANTI-XSS
// ─────────────────────────────────────────────────────────────────────────────

test('anti-XSS : un body avec <script> est neutralisé au rendu', function (): void {
    // Source unique de rendu (markdown html_input=strip) : aucune balise brute conservée.
    $rendered = LessonItem::renderRichText('Bonjour <script>alert(1)</script> fin');
    expect($rendered)->not->toContain('<script>');

    // Au niveau de la page : le corps stocké est rendu sans la balise dangereuse.
    $course  = v4cCourse('cours-forum-xss');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $student = v4cStudent();
    v4cEnroll($course, $student);
    v4cTopic($item, $student, ['title' => 'XSS', 'body' => 'Bonjour <script>alert(9)</script> fin']);

    $this->actingAs($student)->get(v4cShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee('<script>alert(9)', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. SÉCURITÉ : accès, anti-IDOR, throttle, bornes
// ─────────────────────────────────────────────────────────────────────────────

test('un visiteur anonyme ne peut pas ouvrir de sujet (redirigé vers la connexion)', function (): void {
    $course = v4cCourse('cours-forum-anon');
    $lesson = v4cLesson($course);
    $item   = v4cForumItem($lesson);

    $this->post(v4cCreateUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])->assertRedirect();
    expect(ForumTopic::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('un utilisateur NON inscrit ne peut pas ouvrir de sujet (403)', function (): void {
    $course = v4cCourse('cours-forum-noenroll');
    $lesson = v4cLesson($course);
    $item   = v4cForumItem($lesson);
    $user   = v4cStudent(); // existe mais N'EST PAS inscrit

    $this->actingAs($user)->post(v4cCreateUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])->assertForbidden();
    expect(ForumTopic::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('ANTI-IDOR : ouvrir un sujet sur un item d\'un AUTRE cours est refusé (404)', function (): void {
    $courseA = v4cCourse('cours-forum-idor2-a');
    $lessonA = v4cLesson($courseA);

    $courseB = v4cCourse('cours-forum-idor2-b');
    $lessonB = v4cLesson($courseB);
    $itemB   = v4cForumItem($lessonB);

    $student = v4cStudent();
    v4cEnroll($courseA, $student); // inscrit à A seulement

    $this->actingAs($student)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/forum/topics", ['title' => 'X', 'body' => 'Y'])
        ->assertNotFound();
    expect(ForumTopic::where('lesson_item_id', $itemB->id)->count())->toBe(0);
});

test('la route d\'ouverture de sujet est throttlée (429 après le quota)', function (): void {
    $course  = v4cCourse('cours-forum-throttle');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    $statuses = [];
    for ($i = 0; $i < 25; $i++) {
        $statuses[] = $this->actingAs($student)
            ->post(v4cCreateUrl($course, $lesson, $item), ['title' => 'T'.$i, 'body' => 'B'])
            ->getStatusCode();
    }

    expect($statuses)->toContain(429);
});

test('bornes de longueur : titre > 200 ou corps > 10000 => rejet (aucune écriture)', function (): void {
    $course  = v4cCourse('cours-forum-bornes');
    $lesson  = v4cLesson($course);
    $item    = v4cForumItem($lesson);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4cCreateUrl($course, $lesson, $item), ['title' => str_repeat('a', 201), 'body' => 'ok'])
        ->assertSessionHasErrors('title');

    $this->actingAs($student)
        ->post(v4cCreateUrl($course, $lesson, $item), ['title' => 'ok', 'body' => str_repeat('a', 10001)])
        ->assertSessionHasErrors('body');

    expect(ForumTopic::where('lesson_item_id', $item->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 9. RÉTROCOMPAT
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : ouvrir un sujet sur un item NON-forum (document) est refusé (404)', function (): void {
    $course  = v4cCourse('cours-forum-retro');
    $lesson  = v4cLesson($course);
    $doc     = LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'document',
        'title'     => 'Doc',
        'position'  => 1,
        'payload'   => ['rich_text' => 'Notes'],
    ]);
    $student = v4cStudent();
    v4cEnroll($course, $student);

    $this->actingAs($student)
        ->post(v4cCreateUrl($course, $lesson, $doc), ['title' => 'X', 'body' => 'Y'])
        ->assertNotFound();
    expect(ForumTopic::where('lesson_item_id', $doc->id)->count())->toBe(0);
});

test('rétrocompat : les défauts d\'achèvement des autres types sont inchangés', function (): void {
    $lesson = v4cLesson(v4cCourse('cours-forum-retro-defaults'));
    $video  = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'V', 'position' => 1, 'payload' => []]);
    $doc    = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'document', 'title' => 'D', 'position' => 2, 'payload' => []]);
    $quiz   = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Q', 'position' => 3, 'payload' => []]);
    $choice = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'choice', 'title' => 'C', 'position' => 4, 'payload' => ['options' => ['A', 'B']]]);
    $fb     = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'feedback', 'title' => 'F', 'position' => 5, 'payload' => ['questions' => [['type' => 'text', 'label' => 'Q']]]]);

    expect(ActivityCompletionService::criterionFor($video))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($doc))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($quiz))->toBe('min_grade');
    expect(ActivityCompletionService::criterionFor($choice))->toBe('vote');
    expect(ActivityCompletionService::criterionFor($fb))->toBe('submit');
});
