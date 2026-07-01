<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - DISCUSSION SOCIALE PAR VIDÉO (dette D-video-discussion, LMS 2026).
 * Prouve, de façon AUTONOME (helpers préfixés vdisc, distincts de v4c/ForumActivityTest
 * pour éviter toute redéclaration PHP) :
 *
 *  - drapeau OFF (défaut) : un item « video » reste 404 sur les routes forum, comme
 *    n'importe quel item non-forum AVANT cette dette (non-régression stricte) ;
 *  - drapeau ON : un « video_timestamp » « mm:ss » valide est converti et persisté en
 *    secondes (ForumService::parseTimestamp, source unique) ;
 *  - drapeau ON : un « video_timestamp » invalide ou absent est rejeté SILENCIEUSEMENT
 *    (colonne null), la soumission du sujet n'échoue jamais pour autant ;
 *  - ForumService::topics(..., sortByVideoTime: true) trie les sujets dans l'ordre
 *    chronologique de la vidéo plutôt que dans l'ordre de publication.
 *
 * SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\ForumTopic;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
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
// Helpers (préfixe vdisc - autonomes)
// ─────────────────────────────────────────────────────────────────────────────

function vdiscCourse(string $slug = 'cours-vdisc'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'Cours Vidéo Discussion',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function vdiscLesson(Course $course): Lesson
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

/** Item de leçon de type « video » (celui que la dette D-video-discussion cible). */
function vdiscVideoItem(Lesson $lesson, int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'video',
        'title'       => 'Vidéo '.$position,
        'position'    => $position,
        'payload'     => ['video_url' => 'https://example.com/video.mp4'],
        'is_required' => false,
    ]);
}

function vdiscStudent(string $name = 'Étudiant Vidéo'): User
{
    $u = User::factory()->create(['name' => $name]);
    $u->assignRole('student');

    return $u;
}

function vdiscEnroll(Course $course, User $user): void
{
    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

function vdiscCreateUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/topics";
}

function vdiscReplyUrl(Course $course, Lesson $lesson, LessonItem $item, ForumTopic $topic): string
{
    return "/academie/courses/{$course->slug}/lessons/{$lesson->id}/items/{$item->id}/forum/topics/{$topic->id}/reply";
}

function vdiscTopic(LessonItem $item, ?User $user = null, array $attrs = []): ForumTopic
{
    return ForumTopic::create(array_merge([
        'lesson_item_id' => $item->id,
        'user_id'        => $user?->id,
        'title'          => 'Sujet existant',
        'body'           => 'Corps du sujet',
    ], $attrs));
}

// ─────────────────────────────────────────────────────────────────────────────
// a. Drapeau OFF (défaut) : non-régression stricte sur un item « video »
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau OFF (défaut) : ouvrir un sujet sur un item vidéo reste refusé (404)', function (): void {
    expect(config('academy.video_discussion_enabled'))->toBeFalse();

    $course  = vdiscCourse('cours-vdisc-off-create');
    $lesson  = vdiscLesson($course);
    $item    = vdiscVideoItem($lesson);
    $student = vdiscStudent();
    vdiscEnroll($course, $student);

    $this->actingAs($student)
        ->post(vdiscCreateUrl($course, $lesson, $item), ['title' => 'X', 'body' => 'Y'])
        ->assertNotFound();

    expect(ForumTopic::where('lesson_item_id', $item->id)->count())->toBe(0);
});

test('drapeau OFF (défaut) : répondre sur un item vidéo reste refusé (404)', function (): void {
    $course  = vdiscCourse('cours-vdisc-off-reply');
    $lesson  = vdiscLesson($course);
    $item    = vdiscVideoItem($lesson);
    $student = vdiscStudent();
    vdiscEnroll($course, $student);
    $topic = vdiscTopic($item, $student);

    $this->actingAs($student)
        ->post(vdiscReplyUrl($course, $lesson, $item, $topic), ['body' => 'tentative'])
        ->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// b. Drapeau ON : « video_timestamp » valide converti et persisté en secondes
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau ON : video_timestamp "2:34" est persisté en 154 secondes', function (): void {
    config()->set('academy.video_discussion_enabled', true);

    $course  = vdiscCourse('cours-vdisc-on-valid');
    $lesson  = vdiscLesson($course);
    $item    = vdiscVideoItem($lesson);
    $student = vdiscStudent();
    vdiscEnroll($course, $student);

    $this->actingAs($student)
        ->post(vdiscCreateUrl($course, $lesson, $item), [
            'title'            => 'Question sur ce passage',
            'body'             => 'Que se passe-t-il ici ?',
            'video_timestamp'  => '2:34',
        ])
        ->assertRedirect();

    $topic = ForumTopic::where('lesson_item_id', $item->id)->first();
    expect($topic)->not->toBeNull();
    expect($topic->video_timestamp_seconds)->toBe(154);
});

// ─────────────────────────────────────────────────────────────────────────────
// c. Drapeau ON : video_timestamp invalide/absent => rejet silencieux (null),
//    la soumission n'échoue jamais pour autant.
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau ON : video_timestamp invalide est ignoré (null), le sujet est quand même créé', function (): void {
    config()->set('academy.video_discussion_enabled', true);

    $course  = vdiscCourse('cours-vdisc-on-invalid');
    $lesson  = vdiscLesson($course);
    $item    = vdiscVideoItem($lesson);
    $student = vdiscStudent();
    vdiscEnroll($course, $student);

    $this->actingAs($student)
        ->post(vdiscCreateUrl($course, $lesson, $item), [
            'title'            => 'Sujet sans horodatage valide',
            'body'             => 'Corps du message',
            // Respecte la validation serveur max:8 caractères MAIS ne matche pas le
            // format « mm:ss »/« h:mm:ss » (secondes 99 hors [0-5]\d) => parseTimestamp
            // retourne null (rejet silencieux, pas d'échec de validation).
            'video_timestamp'  => '99:99',
        ])
        ->assertRedirect();

    $topic = ForumTopic::where('lesson_item_id', $item->id)->first();
    expect($topic)->not->toBeNull();
    expect($topic->video_timestamp_seconds)->toBeNull();
});

test('drapeau ON : video_timestamp absent => null, le sujet est quand même créé', function (): void {
    config()->set('academy.video_discussion_enabled', true);

    $course  = vdiscCourse('cours-vdisc-on-absent');
    $lesson  = vdiscLesson($course);
    $item    = vdiscVideoItem($lesson);
    $student = vdiscStudent();
    vdiscEnroll($course, $student);

    $this->actingAs($student)
        ->post(vdiscCreateUrl($course, $lesson, $item), [
            'title' => 'Sujet sans champ horodatage',
            'body'  => 'Corps du message',
        ])
        ->assertRedirect();

    $topic = ForumTopic::where('lesson_item_id', $item->id)->first();
    expect($topic)->not->toBeNull();
    expect($topic->video_timestamp_seconds)->toBeNull();
});

// ─────────────────────────────────────────────────────────────────────────────
// d. Tri par horodatage vidéo (ForumService::topics(..., sortByVideoTime: true))
// ─────────────────────────────────────────────────────────────────────────────

test('ForumService::topics(sortByVideoTime: true) trie les sujets dans l\'ordre chronologique vidéo', function (): void {
    $course  = vdiscCourse('cours-vdisc-sort');
    $lesson  = vdiscLesson($course);
    $item    = vdiscVideoItem($lesson);
    $student = vdiscStudent();
    vdiscEnroll($course, $student);

    // Publiés dans l'ordre inverse de leur ancrage vidéo (le plus récent publié est
    // ancré le plus TÔT dans la vidéo) : seul le tri par horodatage vidéo peut
    // reconstituer l'ordre attendu ci-dessous.
    $late  = vdiscTopic($item, $student, ['title' => 'Passage tardif', 'video_timestamp_seconds' => 300]);
    $early = vdiscTopic($item, $student, ['title' => 'Passage précoce', 'video_timestamp_seconds' => 30]);

    $paginator = ForumService::topics($item, sortByVideoTime: true);
    $titles    = $paginator->getCollection()->pluck('title')->all();

    expect($titles)->toBe(['Passage précoce', 'Passage tardif']);

    // Sans le tri vidéo (défaut), l'ordre est chronologique de publication (le plus
    // récent en tête) : non-régression du tri par défaut.
    $defaultTitles = ForumService::topics($item)->getCollection()->pluck('title')->all();
    expect($defaultTitles)->toBe(['Passage précoce', 'Passage tardif']);
});
