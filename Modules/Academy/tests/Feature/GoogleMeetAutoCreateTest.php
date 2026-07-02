<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - AUTO-CRÉATION DU LIEN GOOGLE MEET (phase 2, LiveSessionsManager).
 *
 * Couvre :
 *  (a) drapeau academy.google_meet_autocreate_enabled OFF -> comportement inchangé
 *      (aucun appel API, champ manuel requis, aucune case affichée) ;
 *  (b) service non configuré (drapeau ON mais pas d'identifiants) -> repli propre,
 *      aucune exception, champ manuel toujours requis ;
 *  (c) génération réussie (service mocké, AUCUN appel réseau réel) -> join_url
 *      rempli automatiquement, séance créée ;
 *  (d) échec de l'appel Google (service mocké retourne null) -> repli propre,
 *      erreur de validation lisible, PAS de 500, aucune séance créée ;
 *  (e) un non-staff ne peut pas déclencher la génération (403, même gating que
 *      la création manuelle).
 *
 * AUCUN vrai appel réseau Google : GoogleMeetService est TOUJOURS mocké/bindé
 * dans le container (jamais résolu réellement dans ces tests).
 *
 * Garde-fou : SKIPPED si le module Academy est désactivé.
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Academy\Livewire\LiveSessionsManager;
use Modules\Academy\Models\Course;
use Modules\Academy\Models\CourseRole;
use Modules\Academy\Models\LiveSession;
use Modules\Academy\Services\GoogleMeetService;

uses(RefreshDatabase::class);
uses(\Modules\Academy\Tests\Concerns\SkipsWhenAcademyDisabled::class);

beforeEach(function (): void {
    test()->skipIfAcademyDisabled();

    config()->set('academy.under_construction', false);
    config()->set('academy.live_sessions_enabled', true);
    // Drapeau auto-création OFF par défaut (comportement inchangé sauf test dédié).
    config()->set('academy.google_meet_autocreate_enabled', false);

    $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Academy\Database\Seeders\AcademyPermissionsSeeder::class);
});

function gmeetUser(string $email, string $role = 'student'): User
{
    $u = User::factory()->create(['email' => $email, 'name' => 'Personne']);
    $u->assignRole($role);

    return $u;
}

function gmeetCourse(): Course
{
    return Course::create([
        'slug'        => 'cours-meet-' . uniqid(),
        'title'       => 'Cours Meet',
        'language'    => 'fr-CA',
        'level'       => 'intro',
        'visibility'  => 'public',
        'access_type' => 'free',
        'status'      => 'published',
        'currency'    => 'CAD',
    ]);
}

function gmeetOwner(Course $course, string $email): User
{
    $owner = gmeetUser($email, 'instructor');
    CourseRole::create(['course_id' => $course->id, 'user_id' => $owner->id, 'role' => 'owner']);

    return $owner;
}

/** Fake TOUJOURS en mémoire : jamais d'appel réseau réel dans ces tests. */
function fakeConfiguredMeetService(?string $returnUrl): void
{
    $fake = new class($returnUrl) extends GoogleMeetService {
        public function __construct(private ?string $returnUrl)
        {
        }

        public function isConfigured(): bool
        {
            return true;
        }

        public function createMeetLink(string $title, \DateTimeInterface $startsAt, \DateTimeInterface $endsAt): ?string
        {
            return $this->returnUrl;
        }
    };

    app()->instance(GoogleMeetService::class, $fake);
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) Drapeau OFF : comportement inchangé, aucune case, aucun appel
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau off : aucune case affichée et join_url manuel reste requis', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', false);

    $course = gmeetCourse();
    $owner  = gmeetOwner($course, 'owner-flagoff@ex.test');

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->assertSet('canAutoCreateMeet', false)
        ->set('title', 'Séance sans lien')
        ->set('provider', 'meet')
        ->set('join_url', '')
        ->set('starts_at', now('America/Toronto')->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['join_url']);

    expect(LiveSession::where('course_id', $course->id)->count())->toBe(0);
});

test('drapeau off : cocher generateMeetLink manuellement ne contourne rien (repli identique)', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', false);

    $course = gmeetCourse();
    $owner  = gmeetOwner($course, 'owner-flagoff2@ex.test');

    $this->actingAs($owner);

    // Même si le front forçait generateMeetLink=true, willAutoCreateMeet() est
    // re-vérifié serveur (canAutoCreateMeet=false ici) : join_url reste requis.
    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->set('generateMeetLink', true)
        ->set('title', 'Séance forcée')
        ->set('provider', 'meet')
        ->set('join_url', '')
        ->set('starts_at', now('America/Toronto')->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['join_url']);

    expect(LiveSession::where('course_id', $course->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Service non configuré (drapeau ON, pas d'identifiants) : repli propre
// ─────────────────────────────────────────────────────────────────────────────

test('drapeau on mais service non configure : repli propre, join_url manuel requis', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', true);
    // Aucun identifiant renseigné => GoogleMeetService::isConfigured() réel = false.
    config()->set('academy.google_meet_service_account_json', null);
    config()->set('academy.google_meet_service_account_json_path', null);

    $course = gmeetCourse();
    $owner  = gmeetOwner($course, 'owner-noconfig@ex.test');

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->assertSet('canAutoCreateMeet', false)
        ->set('generateMeetLink', true)
        ->set('title', 'Séance non configurée')
        ->set('provider', 'meet')
        ->set('join_url', '')
        ->set('starts_at', now('America/Toronto')->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['join_url']);

    expect(LiveSession::where('course_id', $course->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) Génération réussie (service mocké, zéro appel réseau réel)
// ─────────────────────────────────────────────────────────────────────────────

test('generation reussie remplit join_url automatiquement', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', true);
    fakeConfiguredMeetService('https://meet.google.com/auto-generated-xyz');

    $course = gmeetCourse();
    $owner  = gmeetOwner($course, 'owner-success@ex.test');

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->assertSet('canAutoCreateMeet', true)
        ->set('generateMeetLink', true)
        ->set('title', 'Séance auto Meet')
        ->set('provider', 'meet')
        ->set('join_url', '')
        ->set('starts_at', now('America/Toronto')->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $session = LiveSession::where('course_id', $course->id)->first();
    expect($session)->not->toBeNull();
    expect($session->join_url)->toBe('https://meet.google.com/auto-generated-xyz');
});

test('provider different de meet ignore la case meme cochee', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', true);
    fakeConfiguredMeetService('https://meet.google.com/should-not-be-used');

    $course = gmeetCourse();
    $owner  = gmeetOwner($course, 'owner-zoom@ex.test');

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->set('generateMeetLink', true)
        ->set('title', 'Séance Zoom')
        ->set('provider', 'zoom')
        ->set('join_url', '')
        ->set('starts_at', now('America/Toronto')->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['join_url']);

    expect(LiveSession::where('course_id', $course->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Échec de l'appel Google : repli propre, pas de 500
// ─────────────────────────────────────────────────────────────────────────────

test('echec de l appel google : repli propre, pas de 500, aucune seance creee', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', true);
    // Le service simule un échec (retourne null) — jamais d'exception.
    fakeConfiguredMeetService(null);

    $course = gmeetCourse();
    $owner  = gmeetOwner($course, 'owner-failure@ex.test');

    $this->actingAs($owner);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->set('generateMeetLink', true)
        ->set('title', 'Séance qui échoue')
        ->set('provider', 'meet')
        ->set('join_url', '')
        ->set('starts_at', now('America/Toronto')->addDays(2)->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasErrors(['join_url']);

    expect(LiveSession::where('course_id', $course->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (e) Non-staff ne peut pas déclencher la génération (même gating que création)
// ─────────────────────────────────────────────────────────────────────────────

test('un non-staff ne peut pas ouvrir le gestionnaire pour generer un lien (403)', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', true);
    fakeConfiguredMeetService('https://meet.google.com/unauthorized-attempt');

    $course  = gmeetCourse();
    $student = gmeetUser('stu-403-meet@ex.test');

    $this->actingAs($student);

    Livewire::test(LiveSessionsManager::class, ['course' => $course])
        ->assertStatus(403);

    expect(LiveSession::where('course_id', $course->id)->count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// GoogleMeetService::isConfigured() — unitaire, sans mock (vraie instance)
// ─────────────────────────────────────────────────────────────────────────────

test('GoogleMeetService isConfigured retourne false sans identifiants (aucune exception)', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', true);
    config()->set('academy.google_meet_service_account_json', null);
    config()->set('academy.google_meet_service_account_json_path', null);

    $service = app(GoogleMeetService::class);

    expect($service->isConfigured())->toBeFalse();
});

test('GoogleMeetService isConfigured retourne false si le drapeau est off meme avec des identifiants', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', false);
    config()->set('academy.google_meet_service_account_json', json_encode(['type' => 'service_account']));

    $service = app(GoogleMeetService::class);

    expect($service->isConfigured())->toBeFalse();
});

test('GoogleMeetService createMeetLink retourne null proprement quand non configure', function (): void {
    config()->set('academy.google_meet_autocreate_enabled', false);

    $service = app(GoogleMeetService::class);

    $result = $service->createMeetLink('Titre test', now(), now()->addHour());

    expect($result)->toBeNull();
});
