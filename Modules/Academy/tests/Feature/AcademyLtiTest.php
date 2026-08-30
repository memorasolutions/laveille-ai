<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest — Consumer LTI 1.3 minimal (Academy branche des outils EXTERNES,
 * jamais l'inverse).
 *
 * Prouve que :
 *  - le drapeau academy.lti_enabled OFF (défaut) renvoie 404 sur launch ET callback ;
 *  - le flux de connexion (login) génère nonce+state en cache et redirige avec
 *    les bons paramètres OIDC ;
 *  - un jeton d'identité VALIDE (signé par la clé annoncée dans le JWKS mocké,
 *    tous claims corrects) passe la validation sans exception qui fuit ;
 *  - un jeton signé par une AUTRE clé RSA échoue proprement (pas de 500, message
 *    générique, aucun détail technique exposé dans la réponse HTTP) ;
 *  - le REJEU du même state après un premier succès est rejeté (anti-rejeu) ;
 *  - un deployment_id du jeton différent de celui du LtiToolRegistration est
 *    rejeté ;
 *  - un utilisateur NON inscrit/NON autorisé reçoit 403 sur launch.
 *
 * JAMAIS de vrai appel réseau : Http::fake() sert le JWKS mocké. Les paires de
 * clés RSA de test sont générées EN MÉMOIRE via openssl_pkey_new. Autonome :
 * helpers préfixés `lti`, aucune redéclaration d'une fonction d'un autre
 * fichier de test. Garde-fou : si le module Academy est désactivé, tous les
 * tests sont SKIPPED.
 */

declare(strict_types=1);

use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Academy\Models\Chapter;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\Enrollment;
use Modules\Academy\Models\Lesson;
use Modules\Academy\Models\LessonItem;
use Modules\Academy\Models\LtiToolRegistration;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    // Défaut EXPLICITE : chaque test active le drapeau lui-même quand nécessaire.
    config(['academy.lti_enabled' => false]);

    // Gate « en construction » : par défaut à true (config('academy.under_construction')),
    // rempli à false en local par le vrai .env non versionné. Sur CI, cp .env.example -> .env
    // ne définit pas ACADEMY_UNDER_CONSTRUCTION, donc chaque route publique renvoyait 503 et
    // faisait échouer TOUTE cette suite (jamais un bug LTI lui-même). Même convention que les
    // ~90 autres tests Academy qui posent déjà cette ligne (ex. CompetencyGraphTest).
    config(['academy.under_construction' => false]);
});

// ─────────────────────────────────────────────────────────────────────────────
// Helpers lti (préfixés, autonomes)
// ─────────────────────────────────────────────────────────────────────────────

/** Crée un cours gratuit publié minimal. */
function ltiCourse(string $slug = 'lti-cours'): Course
{
    return Course::create([
        'slug'        => $slug,
        'title'       => 'LTI Cours',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

/** Crée une leçon minimale rattachée au cours donné. */
function ltiLesson(Course $course, string $suffix = '1'): Lesson
{
    $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Chapitre', 'position' => 1]);

    return Lesson::create([
        'chapter_id' => $chapter->id,
        'title'      => 'Leçon',
        'slug'       => "lti-lecon-{$suffix}-{$course->id}",
        'position'   => 1,
    ]);
}

/** Crée un item de leçon de type « lti_tool » rattaché à l'outil donné. */
function ltiLessonItem(Lesson $lesson, LtiToolRegistration $tool): LessonItem
{
    return LessonItem::create([
        'lesson_id'   => $lesson->id,
        'type'        => 'lti_tool',
        'title'       => 'Outil externe',
        'position'    => 1,
        'is_required' => false,
        'payload'     => ['lti_tool_registration_id' => $tool->id],
    ]);
}

/** Enregistre un outil LTI 1.3 externe actif. */
function ltiRegistration(array $overrides = []): LtiToolRegistration
{
    return LtiToolRegistration::create(array_merge([
        'name'           => 'Outil de test',
        'issuer'         => 'https://outil-externe.test',
        'client_id'      => 'client-abc-123',
        'deployment_id'  => 'deploiement-1',
        'auth_login_url' => 'https://outil-externe.test/lti/login',
        'auth_token_url' => 'https://outil-externe.test/lti/token',
        'jwks_url'       => 'https://outil-externe.test/.well-known/jwks.json',
        'is_active'      => true,
    ], $overrides));
}

/** Inscrit activement un utilisateur à un cours (accès autorisé). */
function ltiEnroll(User $user, Course $course): Enrollment
{
    return Enrollment::create([
        'course_id'   => $course->id,
        'user_id'     => $user->id,
        'status'      => 'active',
        'source'      => 'admin',
        'enrolled_at' => now(),
    ]);
}

/**
 * Génère une paire de clés RSA de test EN MÉMOIRE (jamais sur disque) et
 * retourne ['private' => PEM, 'public' => PEM].
 *
 * @return array{private: string, public: string}
 */
function ltiGenerateRsaKeyPair(): array
{
    $resource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    openssl_pkey_export($resource, $privateKeyPem);
    $publicKeyPem = openssl_pkey_get_details($resource)['key'];

    return ['private' => $privateKeyPem, 'public' => $publicKeyPem];
}

/**
 * Construit un JWKS (JSON Web Key Set) au format standard à partir d'une clé
 * publique RSA PEM, pour simuler la réponse HTTP de l'endpoint JWKS de l'outil.
 */
function ltiJwksFromPublicKey(string $publicKeyPem, string $kid = 'test-key-1'): array
{
    $details = openssl_pkey_get_details(openssl_pkey_get_public($publicKeyPem));
    $n = $details['rsa']['n'];
    $e = $details['rsa']['e'];

    $base64UrlEncode = fn (string $data): string => rtrim(strtr(base64_encode($data), '+/', '-_'), '=');

    return [
        'keys' => [
            [
                'kty' => 'RSA',
                'kid' => $kid,
                'use' => 'sig',
                'alg' => 'RS256',
                'n'   => $base64UrlEncode($n),
                'e'   => $base64UrlEncode($e),
            ],
        ],
    ];
}

/** Construit les claims LTI 1.3 standards d'un id_token valide pour l'outil/l'item donnés. */
function ltiClaims(LtiToolRegistration $tool, string $nonce, ?string $deploymentId = null): array
{
    return [
        'iss'   => $tool->issuer,
        'aud'   => $tool->client_id,
        'sub'   => 'apprenant-test',
        'exp'   => time() + 300,
        'iat'   => time(),
        'nonce' => $nonce,
        'https://purl.imsglobal.org/spec/lti/claim/deployment_id'   => $deploymentId ?? $tool->deployment_id,
        'https://purl.imsglobal.org/spec/lti-1p0/claim/message_type' => 'LtiResourceLinkRequest',
        'https://purl.imsglobal.org/spec/lti/claim/version'          => '1.3.0',
    ];
}

/** Encode un id_token RS256 signé avec la clé privée fournie. */
function ltiEncodeToken(array $claims, string $privateKeyPem, string $kid = 'test-key-1'): string
{
    return JWT::encode($claims, $privateKeyPem, 'RS256', $kid);
}

/** Amorce le flux : crée état/nonce en cache via une vraie requête `launch`, retourne le state extrait de la redirection. */
function ltiCaptureState(\Illuminate\Testing\TestResponse $response): string
{
    $location = $response->headers->get('Location');
    parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);

    return (string) ($query['state'] ?? '');
}

// ─────────────────────────────────────────────────────────────────────────────
// (1) Drapeau OFF (défaut) = launch 404
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau lti_enabled OFF (défaut) : la route launch retourne 404', function (): void {
    config(['academy.lti_enabled' => false]);

    $user   = User::factory()->create();
    $course = ltiCourse();
    $lesson = ltiLesson($course);
    $tool   = ltiRegistration();
    $item   = ltiLessonItem($lesson, $tool);
    ltiEnroll($user, $course);

    $this->actingAs($user)
        ->get(route('academy.lti.launch', [$course, $lesson, $item->id]))
        ->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// (2) Drapeau OFF (défaut) = callback 404
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau lti_enabled OFF (défaut) : la route callback retourne 404', function (): void {
    config(['academy.lti_enabled' => false]);

    $this->post(route('academy.lti.callback'), ['state' => 'x', 'id_token' => 'y'])
        ->assertNotFound();
});

// ─────────────────────────────────────────────────────────────────────────────
// (3) Login flow : nonce+state en cache, redirection avec bons paramètres OIDC
// ─────────────────────────────────────────────────────────────────────────────

test('le flux de connexion génère nonce+state en cache et redirige avec les paramètres OIDC attendus', function (): void {
    config(['academy.lti_enabled' => true]);

    $user   = User::factory()->create();
    $course = ltiCourse();
    $lesson = ltiLesson($course);
    $tool   = ltiRegistration();
    $item   = ltiLessonItem($lesson, $tool);
    ltiEnroll($user, $course);

    $response = $this->actingAs($user)
        ->get(route('academy.lti.launch', [$course, $lesson, $item->id]));

    $response->assertRedirect();
    $location = (string) $response->headers->get('Location');

    expect($location)->toStartWith($tool->auth_login_url);

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    expect($query['client_id'])->toBe($tool->client_id);
    expect($query['login_hint'])->toBe((string) $user->id);
    expect($query['response_type'])->toBe('id_token');
    expect($query['response_mode'])->toBe('form_post');
    expect($query['scope'])->toBe('openid');
    expect($query['prompt'])->toBe('none');
    expect($query['state'])->not->toBeEmpty();
    expect($query['nonce'])->not->toBeEmpty();

    // L'état et le nonce sont bien posés en cache (clé opaque, valeurs cohérentes).
    $cached = Cache::get("academy_lti_state_{$query['state']}");
    expect($cached)->not->toBeNull();
    expect($cached['nonce'])->toBe($query['nonce']);
    expect($cached['lesson_item_id'])->toBe($item->id);
    expect($cached['user_id'])->toBe($user->id);
    expect($cached['tool_registration_id'])->toBe($tool->id);
});

// ─────────────────────────────────────────────────────────────────────────────
// (4) Callback avec jeton VALIDE = succès, aucune exception ne fuit
// ─────────────────────────────────────────────────────────────────────────────

test('callback avec un id_token valide (signature + tous les claims corrects) réussit', function (): void {
    config(['academy.lti_enabled' => true]);

    $user   = User::factory()->create();
    $course = ltiCourse();
    $lesson = ltiLesson($course);
    $tool   = ltiRegistration();
    $item   = ltiLessonItem($lesson, $tool);
    ltiEnroll($user, $course);

    $keyPair = ltiGenerateRsaKeyPair();
    Http::fake([
        $tool->jwks_url => Http::response(ltiJwksFromPublicKey($keyPair['public'])),
    ]);

    $loginResponse = $this->actingAs($user)
        ->get(route('academy.lti.launch', [$course, $lesson, $item->id]));
    $state  = ltiCaptureState($loginResponse);
    $cached = Cache::get("academy_lti_state_{$state}");

    $idToken = ltiEncodeToken(ltiClaims($tool, (string) $cached['nonce']), $keyPair['private']);

    $response = $this->post(route('academy.lti.callback'), [
        'state'    => $state,
        'id_token' => $idToken,
    ]);

    $response->assertOk();
    $response->assertSee($tool->name);
});

// ─────────────────────────────────────────────────────────────────────────────
// (5) ADVERSARIAL — jeton signé par une AUTRE clé RSA = échec propre
// ─────────────────────────────────────────────────────────────────────────────

test('ADVERSARIAL : un id_token signé par une AUTRE clé RSA que celle du JWKS échoue proprement (pas de 500, message générique)', function (): void {
    config(['academy.lti_enabled' => true]);

    $user   = User::factory()->create();
    $course = ltiCourse();
    $lesson = ltiLesson($course);
    $tool   = ltiRegistration();
    $item   = ltiLessonItem($lesson, $tool);
    ltiEnroll($user, $course);

    // Le JWKS mocké annonce la clé publique A, mais le jeton est signé avec B.
    $keyPairA = ltiGenerateRsaKeyPair();
    $keyPairB = ltiGenerateRsaKeyPair();
    Http::fake([
        $tool->jwks_url => Http::response(ltiJwksFromPublicKey($keyPairA['public'])),
    ]);

    $loginResponse = $this->actingAs($user)
        ->get(route('academy.lti.launch', [$course, $lesson, $item->id]));
    $state  = ltiCaptureState($loginResponse);
    $cached = Cache::get("academy_lti_state_{$state}");

    $idToken = ltiEncodeToken(ltiClaims($tool, (string) $cached['nonce']), $keyPairB['private']);

    $response = $this->post(route('academy.lti.callback'), [
        'state'    => $state,
        'id_token' => $idToken,
    ]);

    // Jamais de 500 : le contrôleur attrape TOUTES les exceptions (SignatureInvalidException incluse).
    $response->assertOk();
    $response->assertSee('Impossible de charger cet outil externe');
    // Aucun détail technique (nom d'exception, message interne) dans la réponse HTTP.
    $response->assertDontSee('SignatureInvalidException');
    $response->assertDontSee('lti_callback_');
});

// ─────────────────────────────────────────────────────────────────────────────
// (6) ADVERSARIAL — REJEU du même state après un premier succès = rejeté
// ─────────────────────────────────────────────────────────────────────────────

test('ADVERSARIAL : rejouer le MÊME state une seconde fois après un premier succès est rejeté (anti-rejeu)', function (): void {
    config(['academy.lti_enabled' => true]);

    $user   = User::factory()->create();
    $course = ltiCourse();
    $lesson = ltiLesson($course);
    $tool   = ltiRegistration();
    $item   = ltiLessonItem($lesson, $tool);
    ltiEnroll($user, $course);

    $keyPair = ltiGenerateRsaKeyPair();
    Http::fake([
        $tool->jwks_url => Http::response(ltiJwksFromPublicKey($keyPair['public'])),
    ]);

    $loginResponse = $this->actingAs($user)
        ->get(route('academy.lti.launch', [$course, $lesson, $item->id]));
    $state  = ltiCaptureState($loginResponse);
    $cached = Cache::get("academy_lti_state_{$state}");

    $idToken = ltiEncodeToken(ltiClaims($tool, (string) $cached['nonce']), $keyPair['private']);

    // 1re tentative : succès (Cache::pull consomme le state).
    $first = $this->post(route('academy.lti.callback'), [
        'state'    => $state,
        'id_token' => $idToken,
    ]);
    $first->assertOk();
    $first->assertSee($tool->name);

    // 2e tentative avec EXACTEMENT le même state : rejetée (state déjà consommé).
    $second = $this->post(route('academy.lti.callback'), [
        'state'    => $state,
        'id_token' => $idToken,
    ]);
    $second->assertOk();
    $second->assertSee('Impossible de charger cet outil externe');
    $second->assertDontSee($tool->name);
});

// ─────────────────────────────────────────────────────────────────────────────
// (7) ADVERSARIAL — deployment_id du jeton différent du LtiToolRegistration
// ─────────────────────────────────────────────────────────────────────────────

test('ADVERSARIAL : un deployment_id du jeton différent de celui du LtiToolRegistration est rejeté', function (): void {
    config(['academy.lti_enabled' => true]);

    $user   = User::factory()->create();
    $course = ltiCourse();
    $lesson = ltiLesson($course);
    $tool   = ltiRegistration(['deployment_id' => 'deploiement-attendu']);
    $item   = ltiLessonItem($lesson, $tool);
    ltiEnroll($user, $course);

    $keyPair = ltiGenerateRsaKeyPair();
    Http::fake([
        $tool->jwks_url => Http::response(ltiJwksFromPublicKey($keyPair['public'])),
    ]);

    $loginResponse = $this->actingAs($user)
        ->get(route('academy.lti.launch', [$course, $lesson, $item->id]));
    $state  = ltiCaptureState($loginResponse);
    $cached = Cache::get("academy_lti_state_{$state}");

    // Jeton signé correctement, mais deployment_id ÉTRANGER au tool enregistré.
    $idToken = ltiEncodeToken(
        ltiClaims($tool, (string) $cached['nonce'], deploymentId: 'deploiement-etranger'),
        $keyPair['private'],
    );

    $response = $this->post(route('academy.lti.callback'), [
        'state'    => $state,
        'id_token' => $idToken,
    ]);

    $response->assertOk();
    $response->assertSee('Impossible de charger cet outil externe');
    $response->assertDontSee($tool->name);
});

// ─────────────────────────────────────────────────────────────────────────────
// (8) ADVERSARIAL — utilisateur NON inscrit/NON autorisé = 403 sur launch
// ─────────────────────────────────────────────────────────────────────────────

test('ADVERSARIAL : un utilisateur NON inscrit/NON autorisé qui lance l\'outil reçoit 403', function (): void {
    config(['academy.lti_enabled' => true]);

    $user   = User::factory()->create();
    $course = ltiCourse();
    $lesson = ltiLesson($course);
    $tool   = ltiRegistration();
    $item   = ltiLessonItem($lesson, $tool);
    // AUCUNE inscription pour $user : ni gérant, ni inscrit actif, item non « preview ».

    $this->actingAs($user)
        ->get(route('academy.lti.launch', [$course, $lesson, $item->id]))
        ->assertForbidden();
});
