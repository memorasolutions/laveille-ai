<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Proxy vidéo signé (« protéger l'accès, pas l'iframe »).
 *
 * Prouve que :
 *  - le lien ScreenPal réel n'apparaît JAMAIS dans le HTML de la page de leçon
 *    (seule l'URL signée du proxy interne y figure) ;
 *  - un utilisateur NON autorisé (non connecté ou non inscrit) reçoit 403 sur
 *    la route signée, même avec une signature valide et non expirée ;
 *  - un utilisateur autorisé (inscrit actif) est redirigé (302) vers le vrai
 *    lien ScreenPal ;
 *  - une URL signée EXPIRÉE est rejetée (middleware `signed`, régression).
 *
 * Garde-fou : skippé si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

const VIDEO_REDIRECT_SCREENPAL_URL = 'https://share.screenpal.com/player/secret-abc123';

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers (préfixés vr, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Crée un cours publié minimal. */
function vrCourse(string $slug = 'vr-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'VR Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Crée un chapitre + une leçon pour le cours donné. */
function vrLesson(Course $course): Lesson
{
    $chapter = Chapter::create([
        'course_id' => $course->id,
        'title'     => 'Chapitre VR',
        'position'  => 1,
    ]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon VR',
        'slug'       => 'lecon-vr-' . $course->id,
        'position'   => 1,
    ]);
}

/** Crée un item vidéo dans la leçon donnée, avec le lien ScreenPal réel. */
function vrVideoItem(Lesson $lesson, string $playerUrl = VIDEO_REDIRECT_SCREENPAL_URL): LessonItem
{
    return LessonItem::create([
        'lesson_id' => $lesson->id,
        'type'      => 'video',
        'title'     => 'Vidéo VR',
        'position'  => 1,
        'payload'   => ['player_url' => $playerUrl],
    ]);
}

/** Crée un étudiant avec une inscription active au cours donné. */
function vrStudent(Course $course): User
{
    $user = User::factory()->create();

    Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);

    return $user;
}

/** Construit l'URL signée (4 h) vers la route de proxy vidéo pour l'item donné. */
function vrSignedUrl(Course $course, Lesson $lesson, LessonItem $item, ?\DateTimeInterface $expiration = null): string
{
    return URL::temporarySignedRoute(
        'academy.lessons.video-redirect',
        $expiration ?? now()->addHours(4),
        ['course' => $course->slug, 'lesson' => $lesson->id, 'itemId' => $item->id],
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. Le lien ScreenPal réel ne fuite plus dans le HTML de la leçon
// ─────────────────────────────────────────────────────────────────────────────

test('le lien ScreenPal brut n\'apparaît plus dans le HTML de la leçon (proxy signé injecté à la place)', function (): void {
    $course  = vrCourse('vr-cours-html');
    $lesson  = vrLesson($course);
    $item    = vrVideoItem($lesson);
    $student = vrStudent($course);

    $response = $this->actingAs($student)
        ->get(route('academy.lessons.show', [$course, $lesson]));

    $response->assertStatus(200);
    $response->assertDontSee('share.screenpal.com');
    $response->assertDontSee('media.memora.solutions');
    $response->assertSee(route('academy.lessons.video-redirect', [
        'course' => $course->slug,
        'lesson' => $lesson->id,
        'itemId' => $item->id,
    ]), false);
});

// ─────────────────────────────────────────────────────────────────────────────
// 2. Accès NON autorisé → 403, même avec signature valide et non expirée
// ─────────────────────────────────────────────────────────────────────────────

test('un utilisateur NON connecté reçoit 403 sur la route signée même avec une signature valide', function (): void {
    $course = vrCourse('vr-cours-guest');
    $lesson = vrLesson($course);
    $item   = vrVideoItem($lesson);

    $signedUrl = vrSignedUrl($course, $lesson, $item);

    $this->get($signedUrl)->assertForbidden();
});

test('un utilisateur connecté mais NON inscrit reçoit 403 sur la route signée', function (): void {
    $course       = vrCourse('vr-cours-non-inscrit');
    $lesson       = vrLesson($course);
    $item         = vrVideoItem($lesson);
    $outsideUser  = User::factory()->create();

    $signedUrl = vrSignedUrl($course, $lesson, $item);

    $this->actingAs($outsideUser)
        ->get($signedUrl)
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 3. Accès autorisé → 302 vers le vrai lien ScreenPal
// ─────────────────────────────────────────────────────────────────────────────

test('un étudiant inscrit est redirigé (302) vers le vrai lien ScreenPal', function (): void {
    $course  = vrCourse('vr-cours-inscrit');
    $lesson  = vrLesson($course);
    $item    = vrVideoItem($lesson, 'https://share.screenpal.com/player/xyz789');
    $student = vrStudent($course);

    $signedUrl = vrSignedUrl($course, $lesson, $item);

    $this->actingAs($student)
        ->get($signedUrl)
        ->assertRedirect('https://share.screenpal.com/player/xyz789');
});

// ─────────────────────────────────────────────────────────────────────────────
// 4. URL signée EXPIRÉE → rejetée (régression middleware `signed`)
// ─────────────────────────────────────────────────────────────────────────────

test('une URL signée EXPIRÉE est rejetée même pour un étudiant inscrit', function (): void {
    $course  = vrCourse('vr-cours-expire');
    $lesson  = vrLesson($course);
    $item    = vrVideoItem($lesson);
    $student = vrStudent($course);

    $expiredUrl = vrSignedUrl($course, $lesson, $item, now()->subHour());

    $this->actingAs($student)
        ->get($expiredUrl)
        ->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// 5. Anti-IDOR : l'item doit appartenir à la leçon/au cours de l'URL
// ─────────────────────────────────────────────────────────────────────────────

test('un item vidéo d\'un AUTRE cours est rejeté (404, anti-IDOR)', function (): void {
    $courseA = vrCourse('vr-cours-a');
    $lessonA = vrLesson($courseA);

    $courseB = vrCourse('vr-cours-b');
    $lessonB = vrLesson($courseB);
    $itemB   = vrVideoItem($lessonB);
    $student = vrStudent($courseA);

    // Signature calculée pour lessonA/courseA mais avec l'itemId de courseB.
    $signedUrl = vrSignedUrl($courseA, $lessonA, $itemB);

    $this->actingAs($student)
        ->get($signedUrl)
        ->assertNotFound();
});
