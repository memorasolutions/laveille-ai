<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F19 WIKI : nouvelle ACTIVITÉ de leçon « wiki » (pages collaboratives +
 * historique ; type Moodle « Wiki »). Prouve, de façon AUTONOME (helpers préfixés v19) :
 *
 *  - création d'un item wiki via l'éditeur (intro / allow_student_edit) + page d'accueil ;
 *  - un inscrit crée une page, l'édite => une révision (état précédent) est snapshotée ;
 *  - restauration d'une révision (gérant ou auteur) remet le contenu et versionne ;
 *  - honeypot rempli => rejet SILENCIEUX (aucune écriture) ;
 *  - allow_student_edit=false => l'étudiant ne crée/édite pas (sauf gérant) ;
 *  - page verrouillée => édition refusée (sauf gérant) ;
 *  - modération gérant : verrouiller / supprimer (soft-delete) ; non-gérant 403 ;
 *  - anti-XSS : un body avec <script> est neutralisé au rendu (renderRichText) ;
 *  - sécurité : non-inscrit/anonyme rejeté, anti-IDOR item + page, route throttlée, bornes ;
 *  - rétrocompat : items video/document/quiz/choice/feedback inchangés ; wiki => défaut manual.
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
use Modules\Academy\Models\WikiPage;
use Modules\Academy\Models\WikiRevision;
use Modules\Academy\Services\ActivityCompletionService;
use Modules\Academy\Services\WikiService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe v19 - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function v19Course(string $slug = 'cours-v19'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours V19',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function v19Lesson(Course $course): Lesson
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

function v19WikiItem(Lesson $lesson, array $payload = [], int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'wiki',
        'title'       => 'Wiki '.$position,
        'position'    => $position,
        'payload'     => array_merge([
            'intro'              => 'Documentons cette leçon ensemble.',
            'allow_student_edit' => true,
        ], $payload),
        'is_required' => false,
    ]);
}

function v19Student(string $name = 'Étudiant Test'): User
{
    $u = User::factory()->create(['name' => $name]);
    $u->assignRole('student');

    return $u;
}

function v19Owner(Course $course): User
{
    $u = User::factory()->create(['name' => 'Formateur Test']);
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function v19Enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function v19ShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

function v19CreateUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/wiki/pages";
}

function v19UpdateUrl(Course $course, Lesson $lesson, LessonItem $item, WikiPage $page): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/wiki/pages/{$page->id}/update";
}

function v19Page(LessonItem $item, ?User $user = null, array $attrs = []): WikiPage
{
    return WikiService::createPage($item, $user?->id, $attrs['title'] ?? 'Page A', $attrs['body'] ?? 'Contenu initial');
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. SERVICE + défauts
// ─────────────────────────────────────────────────────────────────────────────

test('WikiService lit la configuration avec ses défauts', function (): void {
    $item = LessonItem::create([
        'lesson_id' => v19Lesson(v19Course())->id,
        'type'      => 'wiki',
        'title'     => 'W',
        'position'  => 1,
        'payload'   => [],
    ]);
    expect(WikiService::allowsStudentEdit($item))->toBeTrue();
    expect(WikiService::intro($item))->toBe('');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. CRÉATION DE L'ITEM via l'éditeur (+ page d'accueil garantie)
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant crée un item wiki ; payload bien construit + page d\'accueil', function (): void {
    $course = v19Course('cours-wiki-create');
    $lesson = v19Lesson($course);
    $owner  = v19Owner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Wiki de la leçon')
        ->set("newItem.{$lesson->id}.wiki_intro", 'Co-construisons les notes.')
        ->set("newItem.{$lesson->id}.allow_student_edit", false)
        ->call('addItem', $lesson->id, 'wiki')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'wiki')->first();
    expect($item)->not->toBeNull();
    expect($item->payload['intro'])->toBe('Co-construisons les notes.');
    expect($item->payload['allow_student_edit'])->toBeFalse();

    // Page d'accueil garantie à la création de l'item.
    $home = WikiPage::where('lesson_item_id', $item->id)->where('is_home', true)->first();
    expect($home)->not->toBeNull();
    expect($home->title)->toBe('Accueil');
});

test('un item wiki créé sans réglage autorise l\'édition étudiante par défaut', function (): void {
    $course = v19Course('cours-wiki-default');
    $lesson = v19Lesson($course);
    $owner  = v19Owner($course);

    Livewire::actingAs($owner)
        ->test(CourseEditor::class, ['course' => $course])
        ->set("newItem.{$lesson->id}.title", 'Wiki libre')
        ->call('addItem', $lesson->id, 'wiki')
        ->assertHasNoErrors();

    $item = LessonItem::where('lesson_id', $lesson->id)->where('type', 'wiki')->first();
    expect(WikiService::allowsStudentEdit($item))->toBeTrue();
});

test('ensureHomePage est idempotent', function (): void {
    $item = v19WikiItem(v19Lesson(v19Course('cours-wiki-idem')));
    $a = WikiService::ensureHomePage($item, null);
    $b = WikiService::ensureHomePage($item, null);
    expect($a->id)->toBe($b->id);
    expect(WikiPage::where('lesson_item_id', $item->id)->count())->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. CRÉER / ÉDITER UNE PAGE (inscrit) + VERSIONING
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit crée une page (la 1re devient l\'accueil)', function (): void {
    $course  = v19Course('cours-wiki-firstpage');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $student = v19Student();
    v19Enroll($course, $student);

    $this->actingAs($student)
        ->post(v19CreateUrl($course, $lesson, $item), ['title' => 'Ma première page', 'body' => 'Du contenu'])
        ->assertRedirect();

    $page = WikiPage::where('lesson_item_id', $item->id)->first();
    expect($page)->not->toBeNull();
    expect($page->title)->toBe('Ma première page');
    expect($page->is_home)->toBeTrue();          // 1re page = accueil
    expect($page->created_by)->toBe($student->id);
    expect($page->revision)->toBe(1);
});

test('éditer une page snapshote l\'état précédent en révision (versioning)', function (): void {
    $course  = v19Course('cours-wiki-edit');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $student = v19Student();
    v19Enroll($course, $student);

    $page = v19Page($item, $student, ['title' => 'Notes', 'body' => 'Version 1']);
    expect($page->revision)->toBe(1);

    $this->actingAs($student)
        ->post(v19UpdateUrl($course, $lesson, $item, $page), ['title' => 'Notes', 'body' => 'Version 2'])
        ->assertRedirect();

    $page->refresh();
    expect($page->body)->toBe('Version 2');
    expect($page->revision)->toBe(2);
    expect($page->edited_by)->toBe($student->id);

    // Une révision = l'état PRÉCÉDENT (Version 1).
    $rev = WikiRevision::where('wiki_page_id', $page->id)->first();
    expect($rev)->not->toBeNull();
    expect($rev->body)->toBe('Version 1');
    expect($rev->revision)->toBe(1);
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. RESTAURATION D'UNE RÉVISION
// ─────────────────────────────────────────────────────────────────────────────

test('restaurer une révision remet le contenu et crée une nouvelle révision', function (): void {
    $course  = v19Course('cours-wiki-restore');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $student = v19Student();
    v19Enroll($course, $student);

    $page = v19Page($item, $student, ['title' => 'P', 'body' => 'A']);
    WikiService::applyEdit($page->fresh(), $student->id, 'P', 'B'); // rev2, snapshot de A (rev1)

    $page->refresh();
    expect($page->body)->toBe('B');
    $revA = WikiRevision::where('wiki_page_id', $page->id)->where('revision', 1)->first();

    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/wiki/pages/{$page->id}/revisions/{$revA->id}/restore";
    $this->actingAs($student)->post($url)->assertRedirect();

    $page->refresh();
    expect($page->body)->toBe('A');     // contenu restauré
    expect($page->revision)->toBe(3);   // restauration = nouvelle version
    expect(WikiRevision::where('wiki_page_id', $page->id)->count())->toBe(2);
});

test('restaurer : un étudiant NON auteur ne peut pas (403)', function (): void {
    $course = v19Course('cours-wiki-restore-403');
    $lesson = v19Lesson($course);
    $item   = v19WikiItem($lesson);
    $author = v19Student('Auteur');
    $other  = v19Student('Autre');
    v19Enroll($course, $author);
    v19Enroll($course, $other);

    $page = v19Page($item, $author, ['title' => 'P', 'body' => 'A']);
    WikiService::applyEdit($page->fresh(), $author->id, 'P', 'B');
    $page->refresh();
    $revA = WikiRevision::where('wiki_page_id', $page->id)->where('revision', 1)->first();

    $url = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/wiki/pages/{$page->id}/revisions/{$revA->id}/restore";
    $this->actingAs($other)->post($url)->assertForbidden();
    expect($page->fresh()->body)->toBe('B'); // inchangé
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. HONEYPOT (rejet silencieux)
// ─────────────────────────────────────────────────────────────────────────────

test('honeypot rempli => page rejetée SILENCIEUSEMENT (aucune écriture)', function (): void {
    $course  = v19Course('cours-wiki-hp');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $student = v19Student();
    v19Enroll($course, $student);

    $this->actingAs($student)
        ->post(v19CreateUrl($course, $lesson, $item), [
            'title'                 => 'Spam',
            'body'                  => 'Spam',
            WikiService::HONEYPOT   => 'http://spam.example',
        ])
        ->assertRedirect();

    expect(WikiPage::where('lesson_item_id', $item->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 6. allow_student_edit + page verrouillée
// ─────────────────────────────────────────────────────────────────────────────

test('allow_student_edit=false : l\'étudiant ne crée pas (403), le gérant oui', function (): void {
    $course  = v19Course('cours-wiki-noedit');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson, ['allow_student_edit' => false]);
    $owner   = v19Owner($course);
    $student = v19Student();
    v19Enroll($course, $student);

    $this->actingAs($student)
        ->post(v19CreateUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])
        ->assertForbidden();
    expect(WikiPage::where('lesson_item_id', $item->id)->count())->toBe(0);

    $this->actingAs($owner)
        ->post(v19CreateUrl($course, $lesson, $item), ['title' => 'Page gérant', 'body' => 'OK'])
        ->assertRedirect();
    expect(WikiPage::where('lesson_item_id', $item->id)->count())->toBe(1);
});

test('allow_student_edit=false : l\'étudiant ne peut pas éditer une page existante (403)', function (): void {
    $course  = v19Course('cours-wiki-noedit2');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson, ['allow_student_edit' => false]);
    $owner   = v19Owner($course);
    $student = v19Student();
    v19Enroll($course, $student);
    $page = v19Page($item, $owner, ['title' => 'P', 'body' => 'A']);

    $this->actingAs($student)
        ->post(v19UpdateUrl($course, $lesson, $item, $page), ['title' => 'P', 'body' => 'Hack'])
        ->assertForbidden();
    expect($page->fresh()->body)->toBe('A');
});

test('page verrouillée : l\'étudiant ne peut pas éditer (403), le gérant oui', function (): void {
    $course  = v19Course('cours-wiki-pagelock');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $owner   = v19Owner($course);
    $student = v19Student();
    v19Enroll($course, $student);
    $page = v19Page($item, $student, ['title' => 'P', 'body' => 'A']);
    $page->update(['is_locked' => true]);

    $this->actingAs($student)
        ->post(v19UpdateUrl($course, $lesson, $item, $page), ['title' => 'P', 'body' => 'tentative'])
        ->assertForbidden();
    expect($page->fresh()->body)->toBe('A');

    $this->actingAs($owner)
        ->post(v19UpdateUrl($course, $lesson, $item, $page), ['title' => 'P', 'body' => 'corrigé'])
        ->assertRedirect();
    expect($page->fresh()->body)->toBe('corrigé');
});

// ─────────────────────────────────────────────────────────────────────────────
// 7. MODÉRATION (gérant) : verrouiller / supprimer
// ─────────────────────────────────────────────────────────────────────────────

test('un gérant verrouille puis supprime une page (soft-delete) ; un non-gérant 403', function (): void {
    $course  = v19Course('cours-wiki-mod');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $owner   = v19Owner($course);
    $student = v19Student();
    v19Enroll($course, $student);

    $home   = v19Page($item, $owner, ['title' => 'Accueil', 'body' => 'home']); // 1re = accueil
    $second = v19Page($item, $owner, ['title' => 'Seconde', 'body' => 'b']);

    $base = "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/wiki/pages/{$second->id}";

    // Non-gérant rejeté.
    $this->actingAs($student)->post($base.'/lock')->assertForbidden();
    expect($second->fresh()->is_locked)->toBeFalse();

    // Gérant verrouille.
    $this->actingAs($owner)->post($base.'/lock')->assertRedirect();
    expect($second->fresh()->is_locked)->toBeTrue();

    // Gérant supprime (soft-delete).
    $this->actingAs($owner)->post($base.'/delete')->assertRedirect();
    expect(WikiPage::where('id', $second->id)->count())->toBe(0);
    expect(WikiPage::withTrashed()->where('id', $second->id)->count())->toBe(1);
});

test('la page d\'accueil ne peut pas être supprimée', function (): void {
    $course = v19Course('cours-wiki-nohome-del');
    $lesson = v19Lesson($course);
    $item   = v19WikiItem($lesson);
    $owner  = v19Owner($course);
    $home   = v19Page($item, $owner, ['title' => 'Accueil', 'body' => 'home']);

    $this->actingAs($owner)
        ->post("/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/wiki/pages/{$home->id}/delete")
        ->assertRedirect();
    expect(WikiPage::where('id', $home->id)->count())->toBe(1); // toujours présente
});

// ─────────────────────────────────────────────────────────────────────────────
// 8. ANTI-XSS
// ─────────────────────────────────────────────────────────────────────────────

test('anti-XSS : un body avec <script> est neutralisé au rendu', function (): void {
    $course  = v19Course('cours-wiki-xss');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $student = v19Student();
    v19Enroll($course, $student);
    v19Page($item, $student, ['title' => 'XSS', 'body' => 'Bonjour <script>alert(9)</script> fin']);

    $this->actingAs($student)->get(v19ShowUrl($course, $lesson))
        ->assertOk()
        ->assertDontSee('<script>alert(9)', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 9. SÉCURITÉ : accès, anti-IDOR, throttle, bornes
// ─────────────────────────────────────────────────────────────────────────────

test('un visiteur anonyme ne peut pas créer de page (redirigé vers la connexion)', function (): void {
    $course = v19Course('cours-wiki-anon');
    $lesson = v19Lesson($course);
    $item   = v19WikiItem($lesson);

    $this->post(v19CreateUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])->assertRedirect();
    expect(WikiPage::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('un utilisateur NON inscrit ne peut pas créer de page (403)', function (): void {
    $course = v19Course('cours-wiki-noenroll');
    $lesson = v19Lesson($course);
    $item   = v19WikiItem($lesson);
    $user   = v19Student();

    $this->actingAs($user)->post(v19CreateUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])->assertForbidden();
    expect(WikiPage::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('ANTI-IDOR : créer une page sur un item d\'un AUTRE cours est refusé (404)', function (): void {
    $courseA = v19Course('cours-wiki-idor-a');
    $lessonA = v19Lesson($courseA);

    $courseB = v19Course('cours-wiki-idor-b');
    $lessonB = v19Lesson($courseB);
    $itemB   = v19WikiItem($lessonB);

    $student = v19Student();
    v19Enroll($courseA, $student);

    $this->actingAs($student)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/wiki/pages", ['title' => 'X', 'body' => 'Y'])
        ->assertNotFound();
    expect(WikiPage::where('lesson_item_id', $itemB->id)->count())->toBe(0);
});

test('ANTI-IDOR : éditer la page d\'un AUTRE cours via sa propre route est refusé (404)', function (): void {
    $courseA = v19Course('cours-wiki-idor2-a');
    $lessonA = v19Lesson($courseA);
    $itemA   = v19WikiItem($lessonA);
    $ownerA  = v19Owner($courseA);

    $courseB = v19Course('cours-wiki-idor2-b');
    $lessonB = v19Lesson($courseB);
    $itemB   = v19WikiItem($lessonB);
    $pageB   = v19Page($itemB, null, ['title' => 'P', 'body' => 'B']);

    $this->actingAs($ownerA)
        ->post("/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemA->id}/wiki/pages/{$pageB->id}/update", ['title' => 'P', 'body' => 'hack'])
        ->assertNotFound();
    expect($pageB->fresh()->body)->toBe('B');
});

test('la route de création de page est throttlée (429 après le quota)', function (): void {
    $course  = v19Course('cours-wiki-throttle');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $student = v19Student();
    v19Enroll($course, $student);

    $statuses = [];
    for ($i = 0; $i < 25; $i++) {
        $statuses[] = $this->actingAs($student)
            ->post(v19CreateUrl($course, $lesson, $item), ['title' => 'P'.$i, 'body' => 'B'])
            ->getStatusCode();
    }

    expect($statuses)->toContain(429);
});

test('bornes : titre > 200 => rejet (aucune écriture)', function (): void {
    $course  = v19Course('cours-wiki-bornes');
    $lesson  = v19Lesson($course);
    $item    = v19WikiItem($lesson);
    $student = v19Student();
    v19Enroll($course, $student);

    $this->actingAs($student)
        ->post(v19CreateUrl($course, $lesson, $item), ['title' => str_repeat('a', 201), 'body' => 'ok'])
        ->assertSessionHasErrors('title');

    expect(WikiPage::where('lesson_item_id', $item->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// 10. RÉTROCOMPAT
// ─────────────────────────────────────────────────────────────────────────────

test('rétrocompat : créer une page sur un item NON-wiki (document) est refusé (404)', function (): void {
    $course  = v19Course('cours-wiki-retro');
    $lesson  = v19Lesson($course);
    $doc     = LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'document',
        'title'     => 'Doc',
        'position'  => 1,
        'payload'   => ['rich_text' => 'Notes'],
    ]);
    $student = v19Student();
    v19Enroll($course, $student);

    $this->actingAs($student)
        ->post(v19CreateUrl($course, $lesson, $doc), ['title' => 'X', 'body' => 'Y'])
        ->assertNotFound();
    expect(WikiPage::where('lesson_item_id', $doc->id)->count())->toBe(0);
});

test('rétrocompat : les défauts d\'achèvement des autres types sont inchangés ; wiki => edit', function (): void {
    $lesson = v19Lesson(v19Course('cours-wiki-retro-defaults'));
    $video  = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'V', 'position' => 1, 'payload' => []]);
    $doc    = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'document', 'title' => 'D', 'position' => 2, 'payload' => []]);
    $quiz   = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'quiz', 'title' => 'Q', 'position' => 3, 'payload' => []]);
    $choice = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'choice', 'title' => 'C', 'position' => 4, 'payload' => ['options' => ['A', 'B']]]);
    $fb     = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'feedback', 'title' => 'F', 'position' => 5, 'payload' => ['questions' => [['type' => 'text', 'label' => 'Q']]]]);
    $wiki   = LessonItem::create(['lesson_id' => $lesson->id, 'type' => 'wiki', 'title' => 'W', 'position' => 6, 'payload' => []]);

    expect(ActivityCompletionService::criterionFor($video))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($doc))->toBe('manual');
    expect(ActivityCompletionService::criterionFor($quiz))->toBe('min_grade');
    expect(ActivityCompletionService::criterionFor($choice))->toBe('vote');
    expect(ActivityCompletionService::criterionFor($fb))->toBe('submit');
    expect(ActivityCompletionService::criterionFor($wiki))->toBe('edit'); // wiki => achèvement par contribution (édition)
});

// C3 [règle 10] : aucun tiret cadratin dans les vues touchées.
test('aucun tiret cadratin dans les vues wiki/éditeur touchées', function (): void {
    foreach ([
        base_path('Modules/Academy/resources/views/public/lesson.blade.php'),
        base_path('Modules/Academy/resources/views/livewire/course-editor.blade.php'),
    ] as $path) {
        expect(file_get_contents($path))->not->toContain('—');
    }
});

// BUG-1 : auto-complétion sur participation (comme le forum).
test('créer une page wiki auto-complète l\'item pour l\'étudiant (critère edit)', function (): void {
    $course = v19Course('cours-wiki-autocomplete');
    $lesson = v19Lesson($course);
    $item = v19WikiItem($lesson);
    $student = v19Student();
    v19Enroll($course, $student);

    expect(\Modules\Academy\Models\Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists())->toBeFalse();

    $this->actingAs($student)
        ->post(v19CreateUrl($course, $lesson, $item), ['title' => 'Ma page', 'body' => 'Contenu'])
        ->assertRedirect();

    expect(\Modules\Academy\Models\Completion::where('user_id', $student->id)
        ->where('lesson_item_id', $item->id)
        ->where('status', 'completed')
        ->exists())->toBeTrue();
});
