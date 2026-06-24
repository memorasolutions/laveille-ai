<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - F18 : NOTES (étoiles 1 à 5) + COMMENTAIRES sur les items de leçon
 * (parité Moodle ratings/comments). Prouve, de façon AUTONOME (helpers préfixés f18) :
 *
 *  - commenter / noter EXIGE l'inscription active (non-inscrit / anonyme => 403) ;
 *  - honeypot `hp_url` rempli => rejet SILENCIEUX (aucune écriture) ;
 *  - corps borné (> 2000 rejeté) + assaini au rendu (<script> neutralisé) ;
 *  - suppression d'un commentaire : auteur OU gérant ; un autre étudiant => 403 ;
 *    suppression = soft-delete (audit conservé) ;
 *  - note bornée 1..5 (hors borne rejetée), UNE note par (item, user) : re-noter MET
 *    À JOUR la même ligne (jamais de doublon) ; moyenne correcte ;
 *  - anti-IDOR : item d'un autre cours => 404 ;
 *  - rétrocompat : un item sans note/commentaire reste inchangé (page 200, 0 ligne).
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\ItemComment;
use Modules\Academy\Models\ItemRating;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Services\ItemEngagementService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixe f18 - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function f18Course(string $slug = 'cours-f18'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours F18',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function f18Lesson(Course $course): Lesson
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

function f18Item(Lesson $lesson, int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'doc',
        'title'       => 'Élément '.$position,
        'position'    => $position,
        'payload'     => ['rich_text' => 'Contenu de la leçon.'],
        'is_required' => false,
    ]);
}

function f18Student(string $name = 'Étudiant Test'): User
{
    $u = User::factory()->create(['name' => $name]);
    $u->assignRole('student');

    return $u;
}

function f18Owner(Course $course): User
{
    $u = User::factory()->create(['name' => 'Formateur Test']);
    $u->assignRole('instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $u->id, 'role' => 'owner']);

    return $u;
}

function f18Enroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function f18CommentUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/comments";
}

function f18CommentDeleteUrl(Course $course, Lesson $lesson, LessonItem $item, ItemComment $comment): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/comments/{$comment->id}/delete";
}

function f18RateUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/rate";
}

function f18ShowUrl(Course $course, Lesson $lesson): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}";
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. COMMENTAIRES - inscription requise
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit publie un commentaire', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student();
    f18Enroll($course, $user);

    $this->actingAs($user)
        ->post(f18CommentUrl($course, $lesson, $item), ['body' => 'Très bonne leçon !'])
        ->assertRedirect();

    expect(ItemComment::where('lesson_item_id', $item->id)->where('user_id', $user->id)->count())->toBe(1);
});

test('commenter EXIGE l\'inscription : un non-inscrit reçoit 403', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student(); // pas inscrit

    $this->actingAs($user)
        ->post(f18CommentUrl($course, $lesson, $item), ['body' => 'Tentative'])
        ->assertForbidden();

    expect(ItemComment::count())->toBe(0);
});

test('commenter en anonyme est rejeté (redirection login, aucune écriture)', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);

    $this->post(f18CommentUrl($course, $lesson, $item), ['body' => 'Anonyme'])
        ->assertRedirect();

    expect(ItemComment::count())->toBe(0);
});

test('honeypot rempli => rejet SILENCIEUX (redirection succès, aucune écriture)', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student();
    f18Enroll($course, $user);

    $this->actingAs($user)
        ->post(f18CommentUrl($course, $lesson, $item), [
            'body'   => 'Spam',
            'hp_url' => 'http://spam.example',
        ])
        ->assertRedirect();

    expect(ItemComment::count())->toBe(0);
});

test('le corps est borné côté serveur (> 2000 caractères rejeté)', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student();
    f18Enroll($course, $user);

    $this->actingAs($user)
        ->post(f18CommentUrl($course, $lesson, $item), [
            'body' => str_repeat('a', ItemEngagementService::COMMENT_MAX + 1),
        ])
        ->assertSessionHasErrors('body');

    expect(ItemComment::count())->toBe(0);
});

test('le corps est assaini au rendu (anti-XSS : <script> neutralisé)', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student();
    f18Enroll($course, $user);

    $this->actingAs($user)
        ->post(f18CommentUrl($course, $lesson, $item), [
            'body' => 'Bonjour tout le monde <script>alert(1)</script>',
        ])
        ->assertRedirect();

    $comment = ItemComment::where('lesson_item_id', $item->id)->firstOrFail();
    // Les balises HTML brutes sont retirées (html_input=strip) : aucune balise
    // <script> ne survit, le payload restant n'est que du texte inerte. Le contenu
    // légitime est préservé.
    expect($comment->renderedBody())->not->toContain('<script>');
    expect($comment->renderedBody())->toContain('Bonjour tout le monde');
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. SUPPRESSION (auteur OU gérant)
// ─────────────────────────────────────────────────────────────────────────────

test('l\'auteur supprime son commentaire (soft-delete)', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student();
    f18Enroll($course, $user);

    $comment = ItemComment::create(['lesson_item_id' => $item->id, 'user_id' => $user->id, 'body' => 'Mon commentaire']);

    $this->actingAs($user)
        ->post(f18CommentDeleteUrl($course, $lesson, $item, $comment))
        ->assertRedirect();

    expect(ItemComment::find($comment->id))->toBeNull();                 // exclu (scope soft-delete)
    expect(ItemComment::withTrashed()->find($comment->id))->not->toBeNull(); // conservé (audit)
});

test('un gérant supprime le commentaire d\'un étudiant', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $student = f18Student();
    f18Enroll($course, $student);
    $owner = f18Owner($course); // gérant non inscrit

    $comment = ItemComment::create(['lesson_item_id' => $item->id, 'user_id' => $student->id, 'body' => 'À modérer']);

    $this->actingAs($owner)
        ->post(f18CommentDeleteUrl($course, $lesson, $item, $comment))
        ->assertRedirect();

    expect(ItemComment::withTrashed()->find($comment->id)->trashed())->toBeTrue();
});

test('un autre étudiant ne peut PAS supprimer le commentaire d\'autrui (403)', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $author = f18Student('Auteur');
    $other  = f18Student('Autre');
    f18Enroll($course, $author);
    f18Enroll($course, $other);

    $comment = ItemComment::create(['lesson_item_id' => $item->id, 'user_id' => $author->id, 'body' => 'Privé']);

    $this->actingAs($other)
        ->post(f18CommentDeleteUrl($course, $lesson, $item, $comment))
        ->assertForbidden();

    expect(ItemComment::find($comment->id))->not->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. NOTES (étoiles)
// ─────────────────────────────────────────────────────────────────────────────

test('un inscrit note un item (1..5) ; re-noter MET À JOUR la même ligne', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student();
    f18Enroll($course, $user);

    $this->actingAs($user)->post(f18RateUrl($course, $lesson, $item), ['value' => 4])->assertRedirect();
    expect(ItemRating::where('lesson_item_id', $item->id)->where('user_id', $user->id)->count())->toBe(1);
    expect(ItemRating::where('lesson_item_id', $item->id)->where('user_id', $user->id)->value('value'))->toBe(4);

    // Re-noter : pas de doublon, valeur mise à jour.
    $this->actingAs($user)->post(f18RateUrl($course, $lesson, $item), ['value' => 2])->assertRedirect();
    expect(ItemRating::where('lesson_item_id', $item->id)->where('user_id', $user->id)->count())->toBe(1);
    expect(ItemRating::where('lesson_item_id', $item->id)->where('user_id', $user->id)->value('value'))->toBe(2);
});

test('la note est bornée 1..5 (0 et 6 rejetés)', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student();
    f18Enroll($course, $user);

    $this->actingAs($user)->post(f18RateUrl($course, $lesson, $item), ['value' => 0])->assertSessionHasErrors('value');
    $this->actingAs($user)->post(f18RateUrl($course, $lesson, $item), ['value' => 6])->assertSessionHasErrors('value');

    expect(ItemRating::count())->toBe(0);
});

test('noter EXIGE l\'inscription : un non-inscrit reçoit 403', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $user   = f18Student(); // pas inscrit

    $this->actingAs($user)->post(f18RateUrl($course, $lesson, $item), ['value' => 5])->assertForbidden();

    expect(ItemRating::count())->toBe(0);
});

test('la moyenne préchargée est correcte', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    $item   = f18Item($lesson);
    $u1 = f18Student('U1');
    $u2 = f18Student('U2');
    $u3 = f18Student('U3');

    ItemRating::create(['lesson_item_id' => $item->id, 'user_id' => $u1->id, 'value' => 5]);
    ItemRating::create(['lesson_item_id' => $item->id, 'user_id' => $u2->id, 'value' => 4]);
    ItemRating::create(['lesson_item_id' => $item->id, 'user_id' => $u3->id, 'value' => 3]);

    $stats = ItemEngagementService::preloadRatingStats([$item->id]);
    $stat  = $stats->get($item->id);

    expect((int) $stat->votes_count)->toBe(3);
    expect(round((float) $stat->avg_value, 1))->toBe(4.0); // (5+4+3)/3 = 4.0
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. ANTI-IDOR + RÉTROCOMPAT
// ─────────────────────────────────────────────────────────────────────────────

test('anti-IDOR : noter/commenter un item d\'un AUTRE cours via cette leçon => 404', function (): void {
    $courseA = f18Course('cours-a');
    $lessonA = f18Lesson($courseA);
    $courseB = f18Course('cours-b');
    $lessonB = f18Lesson($courseB);
    $itemB   = f18Item($lessonB);

    $user = f18Student();
    f18Enroll($courseA, $user);

    // URL du cours A / leçon A mais itemId du cours B (forge).
    $forged = "/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/comments";
    $this->actingAs($user)->post($forged, ['body' => 'Forge'])->assertNotFound();

    $forgedRate = "/academie/courses/{$courseA->slug}/lessons/{$lessonA->id}/items/{$itemB->id}/rate";
    $this->actingAs($user)->post($forgedRate, ['value' => 5])->assertNotFound();

    expect(ItemComment::count())->toBe(0);
    expect(ItemRating::count())->toBe(0);
});

test('rétrocompat : un item sans note ni commentaire s\'affiche sans erreur (200)', function (): void {
    $course = f18Course();
    $lesson = f18Lesson($course);
    f18Item($lesson);
    $user = f18Student();
    f18Enroll($course, $user);

    $this->actingAs($user)
        ->get(f18ShowUrl($course, $lesson))
        ->assertOk();

    expect(ItemComment::count())->toBe(0);
    expect(ItemRating::count())->toBe(0);
});
