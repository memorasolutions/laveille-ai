<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #1868 - Cloudflare Turnstile sur la soumission publique d'un outil
 * (PublicDirectoryController::storeSubmission). Couche SUPPLÉMENTAIRE, jamais la protection
 * principale : le vrai trou (publication sans relecture) est déjà bouché par la porte de
 * modération, déjà testée dans ToolSubmissionModerationGateTest.php - ce fichier ne la
 * reteste pas (DRY).
 *
 * Couvre les 3 cas exigés par le mandat : jeton valide accepté, jeton absent refusé,
 * mécanisme désactivé qui laisse tout passer - décliné sur les DEUX voies de désactivation
 * possibles : clés Cloudflare absentes (état réel de ce projet au 2026-08-31, déjà couvert
 * implicitement par les 4 tests de ToolSubmissionModerationGateTest.php qui ne configurent
 * aucune clé et continuent de passer sans régression) et coupe-circuit dédié
 * directory.turnstile.enabled=false même clés présentes (le mécanisme réellement neuf ici).
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\View;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Rendu DIRECT de directory::public.index (pas de GET HTTP sur route('directory.index')) -
 * même contournement que DirectoryGridCardViewsCounterTest.php et
 * PublicListCachePurgeOnPublishTest.php (2026-08-27) : le bloc "plus votés" de
 * PublicDirectoryController::index() fait un ->having('community_votes_count', '>', 0) sur une
 * colonne de sous-requête, refusé par le SQLite :memory: de la suite de tests (« HAVING clause
 * on a non-aggregate query »). Limitation PRÉ-EXISTANTE, sans rapport avec ce correctif -
 * jamais touchée par ce fichier. Nom de fonction distinct de celui des deux fichiers ci-dessus
 * (même suite de tests, même espace de noms global) pour éviter toute redéclaration.
 */
function renderDirectoryIndexViewForTurnstileTest(): string
{
    return View::make('directory::public.index', [
        'tools' => collect(),
        'categories' => collect(),
        'pricingOptions' => \Modules\Directory\Support\PricingCategories::optionsWithEducation(),
        'featuredTools' => collect(),
        'recentTools' => collect(),
        'popularTools' => collect(),
        'topVoted' => collect(),
        'userCollections' => collect(),
        'showArchived' => false,
        'archivedCount' => 0,
        'ecosystemCounts' => [],
        'ecosystemLabels' => [],
    ])->render();
}

beforeEach(function () {
    // Même seed que ToolSubmissionModerationGateTest.php : storeSubmission() interroge
    // $request->user()?->can('moderate_tools') même pour un utilisateur ordinaire - sans les
    // permissions seedées, Spatie lève PermissionDoesNotExist plutôt que de retourner false.
    $this->seed(\Modules\RolesPermissions\Database\Seeders\RolesAndPermissionsSeeder::class);
    $this->seed(\Modules\Directory\Database\Seeders\DirectoryModeratorRoleSeeder::class);
    config(['app.locale' => 'fr_CA']);
});

test('jeton Turnstile valide et clés configurées : la soumission passe', function () {
    config(['services.turnstile.secret_key' => 'test-secret-key']);
    config(['directory.turnstile.enabled' => true]);

    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-turnstile-valide.test',
        'name' => 'Outil Turnstile Valide',
        'pricing' => 'free',
        'cf-turnstile-response' => 'jeton-valide-simule',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    expect(Tool::where('url', 'https://exemple-turnstile-valide.test')->exists())->toBeTrue();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'challenges.cloudflare.com'));
});

test('jeton Turnstile absent et clés configurées : la soumission est refusée, rien n\'est créé', function () {
    config(['services.turnstile.secret_key' => 'test-secret-key']);
    config(['directory.turnstile.enabled' => true]);

    // Le jeton absent court-circuite verify() avant tout appel réseau (empty($token) ->
    // return false), mais on fake quand même par hygiène : aucun appel réel ne doit jamais
    // partir pendant les tests.
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => true]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-turnstile-absent.test',
        'name' => 'Outil Turnstile Absent',
        'pricing' => 'free',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['success' => false]);
    expect(Tool::where('url', 'https://exemple-turnstile-absent.test')->exists())->toBeFalse();
});

test('coupe-circuit directory.turnstile.enabled=false : la soumission passe sans jeton, même clés configurées', function () {
    config(['services.turnstile.secret_key' => 'test-secret-key']);
    config(['directory.turnstile.enabled' => false]);

    // Réponse volontairement défavorable (success=false) : si le coupe-circuit ne
    // fonctionnait pas, ce test échouerait sur le statut HTTP, pas seulement sur assertNotSent.
    Http::fake([
        'challenges.cloudflare.com/*' => Http::response(['success' => false]),
    ]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-turnstile-coupe-circuit.test',
        'name' => 'Outil Turnstile Coupe Circuit',
        'pricing' => 'free',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    expect(Tool::where('url', 'https://exemple-turnstile-coupe-circuit.test')->exists())->toBeTrue();

    Http::assertNotSent(fn ($request) => str_contains($request->url(), 'challenges.cloudflare.com'));
});

test('clés Cloudflare absentes (état par défaut de ce projet au 2026-08-31) : la soumission passe sans jeton', function () {
    config(['services.turnstile.secret_key' => null]);
    config(['directory.turnstile.enabled' => true]);

    Http::fake();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('directory.submit'), [
        'url' => 'https://exemple-turnstile-cles-absentes.test',
        'name' => 'Outil Turnstile Cles Absentes',
        'pricing' => 'free',
    ]);

    $response->assertOk();
    $response->assertJson(['success' => true]);
    expect(Tool::where('url', 'https://exemple-turnstile-cles-absentes.test')->exists())->toBeTrue();

    Http::assertNothingSent();
});

test('le widget apparaît sur la page /annuaire dès que les clés Cloudflare sont configurées', function () {
    // Preuve de l'activation elle-même : les clés réelles n'existent nulle part (ni en local
    // ni en production, 2026-08-31), donc impossible à vérifier au navigateur. Ce test prouve
    // que le rendu conditionnel ($turnstileSiteKey, index.blade.php) fonctionne bel et bien
    // dès que Stéphane pose les deux clés côté .env - rien d'autre à changer côté code.
    config(['services.turnstile.site_key' => 'test-site-key-visible']);
    config(['services.turnstile.secret_key' => 'test-secret-key']);
    config(['directory.turnstile.enabled' => true]);

    $html = renderDirectoryIndexViewForTurnstileTest();

    expect($html)->toContain('class="cf-turnstile"');
    expect($html)->toContain('data-sitekey="test-site-key-visible"');
    expect($html)->toContain('challenges.cloudflare.com/turnstile/v0/api.js');
});

test('le widget est absent de la page /annuaire quand les clés sont absentes (état réel de ce projet)', function () {
    config(['services.turnstile.site_key' => null]);
    config(['services.turnstile.secret_key' => null]);
    config(['directory.turnstile.enabled' => true]);

    $html = renderDirectoryIndexViewForTurnstileTest();

    // La div du widget et le script Cloudflare ne doivent JAMAIS apparaître sans clé.
    expect($html)->not->toContain('class="cf-turnstile"');
    expect($html)->not->toContain('challenges.cloudflare.com/turnstile');
    // Le champ JS 'cf-turnstile-response' (submitTool(), toujours présent - il envoie une
    // chaîne vide quand aucun widget n'existe) contient la même sous-chaîne "cf-turnstile" :
    // vérifié ici pour que la distinction reste explicite et ne soit plus jamais confondue
    // avec une fuite du widget lui-même (déjà pincé une fois pendant l'écriture de ce test).
    expect($html)->toContain("'cf-turnstile-response'");
});

test('clé de site posée SANS clé secrète : le widget reste absent (serveur qui ne vérifierait jamais rien)', function () {
    // Revue adversariale (Hermes/deepseek-v4-flash, 2026-08-31) : la vue décidait d'afficher le
    // widget sur la seule présence de la clé PUBLIQUE (site_key), alors que le contrôleur
    // décide de vérifier sur la seule présence de la clé SECRÈTE (secret_key) - deux valeurs
    // .env DISTINCTES. Une configuration partielle (site_key posée, secret_key oubliée) aurait
    // donc affiché un défi Cloudflare que storeSubmission() ne vérifie jamais : friction pour
    // le visiteur, protection nulle malgré l'apparence contraire. Corrigé en exigeant aussi
    // isEnabled() (secret_key) avant de calculer $turnstileSiteKey (index.blade.php).
    config(['services.turnstile.site_key' => 'test-site-key-orpheline']);
    config(['services.turnstile.secret_key' => null]);
    config(['directory.turnstile.enabled' => true]);

    $html = renderDirectoryIndexViewForTurnstileTest();

    expect($html)->not->toContain('class="cf-turnstile"');
    expect($html)->not->toContain('data-sitekey="test-site-key-orpheline"');
    expect($html)->not->toContain('challenges.cloudflare.com/turnstile');
});
