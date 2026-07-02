<?php

/**
 * TEST ADVERSARIAL TEMPORAIRE - Audit sécurité 2026-07-02
 * Vecteurs non couverts par VideoRedirectSecurityTest.php :
 *  1. Manipulation itemId post-signature (signature doit s'invalider)
 *  2. Désinscription APRÈS génération de signature valide (re-check à chaque requête)
 *  3. Distinction 404 vs 403 (énumération)
 *  5. Manipulation du paramètre expires post-signature
 *
 * A SUPPRIMER après le rapport si aucune valeur de régression durable retenue.
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

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();
    config()->set('academy.under_construction', false);
});

function advCourse(string $slug): Course
{
    return Course::create([
        'slug' => $slug, 'title' => 'Adv Cours', 'language' => 'fr-CA',
        'level' => 'intro', 'visibility' => 'public', 'access_type' => 'free',
        'status' => 'published', 'currency' => 'CAD',
    ]);
}

function advLesson(Course $course): Lesson
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Ch', 'position' => 1]);
    return Lesson::create(['chapter_id' => $chapter->id, 'title' => 'Leçon', 'slug' => 'l-' . $course->id, 'position' => 1]);
}

function advVideoItem(Lesson $lesson, string $url, int $position = 1): LessonItem
{
    return LessonItem::create([
        'lesson_id' => $lesson->id, 'type' => 'video', 'title' => 'Vid ' . $position,
        'position' => $position, 'payload' => ['player_url' => $url],
    ]);
}

function advStudent(Course $course): User
{
    $user = User::factory()->create();
    Enrollment::create([
        'course_id' => $course->id, 'user_id' => $user->id, 'status' => 'active',
        'source' => 'admin', 'enrolled_at' => now(),
    ]);
    return $user;
}

function advSignedUrl(Course $course, Lesson $lesson, LessonItem $item): string
{
    return URL::temporarySignedRoute(
        'academy.lessons.video-redirect',
        now()->addHours(4),
        ['course' => $course->slug, 'lesson' => $lesson->id, 'itemId' => $item->id],
    );
}

// ── Vecteur 1 : substitution itemId dans une URL DÉJÀ SIGNÉE (même leçon) ──

test('VECTEUR 1: remplacer itemId par un autre item de la MÊME leçon invalide la signature (403)', function (): void {
    $course  = advCourse('adv-v1');
    $lesson  = advLesson($course);
    $itemA   = advVideoItem($lesson, 'https://share.screenpal.com/player/item-a', 1);
    $itemB   = advVideoItem($lesson, 'https://share.screenpal.com/player/item-b', 2);
    $student = advStudent($course);

    $signedForA = advSignedUrl($course, $lesson, $itemA);

    // Remplace uniquement le segment itemId dans l'URL déjà signée par l'id de itemB,
    // en laissant signature+expires intacts (tentative de rejeu avec item substitué).
    $tampered = preg_replace(
        '#/items/' . $itemA->id . '/#',
        '/items/' . $itemB->id . '/',
        $signedForA
    );

    expect($tampered)->not->toBe($signedForA); // vérifie que la substitution a eu lieu

    $this->actingAs($student)->get($tampered)->assertForbidden();
});

// ── Vecteur 2 : désinscription APRÈS génération de la signature, AVANT expiration ──

test('VECTEUR 2: une signature valide générée avant désinscription est bloquée (403) après désinscription', function (): void {
    $course  = advCourse('adv-v2');
    $lesson  = advLesson($course);
    $item    = advVideoItem($lesson, 'https://share.screenpal.com/player/item-v2', 1);
    $student = advStudent($course);

    // Signature générée PENDANT que l'inscription est active.
    $signedUrl = advSignedUrl($course, $lesson, $item);

    // Accès légitime immédiat : doit fonctionner.
    $this->actingAs($student)->get($signedUrl)->assertRedirect('https://share.screenpal.com/player/item-v2');

    // Désinscription (simulateur d'un cours qui expire / retrait admin).
    Enrollment::where('user_id', $student->id)->where('course_id', $course->id)
        ->update(['status' => 'cancelled']);

    // Même URL signée (toujours valide dans sa fenêtre de 4h), même utilisateur : doit être bloqué.
    $this->actingAs($student)->get($signedUrl)->assertForbidden();
});

test('VECTEUR 2b: cours dépublié APRÈS génération de signature bloque un inscrit actif (403)', function (): void {
    $course  = advCourse('adv-v2b');
    $lesson  = advLesson($course);
    $item    = advVideoItem($lesson, 'https://share.screenpal.com/player/item-v2b', 1);
    $student = advStudent($course);

    $signedUrl = advSignedUrl($course, $lesson, $item);

    $course->update(['status' => 'draft']);

    $this->actingAs($student)->get($signedUrl)->assertForbidden();
});

// ── Vecteur 3 : distinction 404 (item inexistant) vs 403 (item existant, non autorisé) ──

test('VECTEUR 3: item INEXISTANT retourne 404, item EXISTANT mais non autorisé retourne 403 (fuite info)', function (): void {
    $course      = advCourse('adv-v3');
    $lesson      = advLesson($course);
    $item        = advVideoItem($lesson, 'https://share.screenpal.com/player/item-v3', 1);
    $outsideUser = User::factory()->create(); // connecté, PAS inscrit

    // Item réel existant, utilisateur non autorisé => attendu 403.
    $signedRealItem = advSignedUrl($course, $lesson, $item);
    $respReal = $this->actingAs($outsideUser)->get($signedRealItem);

    // Item avec un ID qui n'existe presque certainement pas => attendu 404.
    $fakeId = $item->id + 999999;
    $signedFakeItem = URL::temporarySignedRoute(
        'academy.lessons.video-redirect',
        now()->addHours(4),
        ['course' => $course->slug, 'lesson' => $lesson->id, 'itemId' => $fakeId],
    );
    $respFake = $this->actingAs($outsideUser)->get($signedFakeItem);

    // Documente le comportement réel (peut permettre l'énumération existant/inexistant).
    expect($respReal->status())->toBe(403);
    expect($respFake->status())->toBe(404);
    // Si ces deux codes diffèrent, un attaquant AUTHENTIFIÉ (mais non inscrit) peut,
    // avec une signature qu'il doit malgré tout obtenir légitimement pour CE course/lesson,
    // distinguer un itemId existant d'un itemId inexistant. Portée de l'exploit limitée par
    // le fait que la signature couvre course+lesson+itemId (pas de bruteforce libre, cf V1/V4).
})->group('info-leak-documentation');

// ── Vecteur 5 : manipulation du paramètre expires ──

test('VECTEUR 5: modifier le paramètre expires dans une URL déjà signée invalide la signature (403)', function (): void {
    $course  = advCourse('adv-v5');
    $lesson  = advLesson($course);
    $item    = advVideoItem($lesson, 'https://share.screenpal.com/player/item-v5', 1);
    $student = advStudent($course);

    $signedUrl = advSignedUrl($course, $lesson, $item);

    // Extrait expires actuel et tente de le repousser dans le futur (extension de durée de vie).
    parse_str((string) parse_url($signedUrl, PHP_URL_QUERY), $query);
    expect($query)->toHaveKey('expires');

    $tampered = preg_replace(
        '/expires=\d+/',
        'expires=' . (((int) $query['expires']) + 3600 * 24 * 365),
        $signedUrl
    );

    expect($tampered)->not->toBe($signedUrl);

    $this->actingAs($student)->get($tampered)->assertForbidden();
});

test('VECTEUR 5b: retirer complètement le paramètre signature échoue (403, pas de bypass "signed absent")', function (): void {
    $course  = advCourse('adv-v5b');
    $lesson  = advLesson($course);
    $item    = advVideoItem($lesson, 'https://share.screenpal.com/player/item-v5b', 1);
    $student = advStudent($course);

    $signedUrl = advSignedUrl($course, $lesson, $item);
    $unsignedUrl = strtok($signedUrl, '?'); // enlève toute la query string (signature+expires)

    $this->actingAs($student)->get($unsignedUrl)->assertForbidden();
});

// ── Vecteur 4 (documentation) : rate-limiting sur brute-force itemId ──

test('VECTEUR 4 (documentation): aucun rate-limit dédié observé - un inscrit peut essayer de nombreux itemId différents sans throttle applicatif', function (): void {
    $course  = advCourse('adv-v4');
    $lesson  = advLesson($course);
    $item    = advVideoItem($lesson, 'https://share.screenpal.com/player/item-v4', 1);
    $student = advStudent($course);

    // Un inscrit à CE cours ne peut de toute façon pas forger de signature valide pour un
    // itemId arbitraire (HMAC sur course+lesson+itemId), donc le brute-force direct de
    // itemId sur la route SIGNÉE est structurellement impossible sans casser HMAC-SHA256.
    // Ce test documente juste qu'aucune limite de fréquence n'est imposée sur les requêtes
    // 403/404 légitimement signées elles-mêmes (DoS applicatif potentiel, hors périmètre IDOR).
    $signedUrl = advSignedUrl($course, $lesson, $item);

    for ($i = 0; $i < 20; $i++) {
        $this->actingAs($student)->get($signedUrl);
    }

    // Doit rester 302 (accès légitime) même après 20 requêtes rapides : aucun verrou
    // de type "trop de tentatives" ne bloque un usage normal (attendu), mais confirme
    // aussi l'absence de throttle qui limiterait un abus de bande passante ScreenPal.
    $this->actingAs($student)->get($signedUrl)->assertRedirect('https://share.screenpal.com/player/item-v4');
})->group('info-leak-documentation');
