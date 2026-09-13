<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Health\Checks\ProductHuntApiCheck;
use Spatie\Health\Enums\Status;

uses(Tests\TestCase::class);

beforeEach(function () {
    config()->set('directory.producthunt_token', 'jeton-de-test');
    config()->set('health.producthunt', [
        'enabled' => true,
        'check_interval_seconds' => 3600,
        'connection_failures_cache_key' => 'tests:ph:echecs',
        'measurement_cache_key' => 'tests:ph:mesure',
        'notify_by_mail' => true,
    ]);

    Cache::forget('tests:ph:echecs');
    Cache::forget('tests:ph:mesure');
});

function producthuntFake(array $corps, int $code = 200): void
{
    Http::fake([
        'api.producthunt.com/*' => Http::response($corps, $code),
    ]);
}

it('signale un jeton absent sans toucher au réseau', function () {
    config()->set('directory.producthunt_token', null);
    Http::fake();

    $resultat = ProductHuntApiCheck::new()->run();

    expect($resultat->status->equals(Status::warning()))->toBeTrue();
    expect($resultat->shortSummary)->toBe('jeton absent');
    expect($resultat->getNotificationMessage())->toContain('découverte');
    Http::assertNothingSent();
});

it('déclare le jeton invalide sur une réponse HTTP 401', function () {
    producthuntFake(['errors' => [['error' => 'invalid_oauth_token']]], 401);

    $resultat = ProductHuntApiCheck::new()->run();

    expect($resultat->status->equals(Status::failed()))->toBeTrue();
    expect($resultat->shortSummary)->toBe('jeton invalide');
    expect($resultat->getNotificationMessage())->toContain('401');
    expect($resultat->getNotificationMessage())->toContain('api.producthunt.com/v2/oauth/applications');
});

// un controle qui ne regarderait que le code HTTP declarerait cette reponse saine
it("déclare le jeton invalide même quand l'erreur arrive dans le corps d'une réponse HTTP 200", function () {
    producthuntFake([
        'data' => null,
        'errors' => [['error' => 'invalid_oauth_token', 'error_description' => 'Please supply a valid access token.']],
    ], 200);

    $resultat = ProductHuntApiCheck::new()->run();

    expect($resultat->status->equals(Status::failed()))->toBeTrue();
    expect($resultat->shortSummary)->toBe('jeton invalide');
});

it('signale une limitation de cadence sur un HTTP 429', function () {
    producthuntFake([], 429);

    $resultat = ProductHuntApiCheck::new()->run();

    expect($resultat->status->equals(Status::warning()))->toBeTrue();
    expect($resultat->shortSummary)->toBe('cadence limitée');
});

it("conclut au vert quand l'API renvoie au moins une publication", function () {
    producthuntFake(['data' => ['posts' => ['edges' => [['node' => ['id' => '123']]]]]]);

    $resultat = ProductHuntApiCheck::new()->run();

    expect($resultat->status->equals(Status::ok()))->toBeTrue();
    expect($resultat->shortSummary)->toBe('fonctionnelle');
});

it("avertit quand l'API répond sans aucune publication", function () {
    producthuntFake(['data' => ['posts' => ['edges' => []]]]);

    $resultat = ProductHuntApiCheck::new()->run();

    expect($resultat->status->equals(Status::warning()))->toBeTrue();
    expect($resultat->shortSummary)->toBe('réponse vide');
});

// le controle tourne chaque minute : sans cet etranglement il interrogerait ProductHunt 1440 fois par jour
it("n'interroge le réseau qu'une seule fois par intervalle", function () {
    producthuntFake(['data' => ['posts' => ['edges' => [['node' => ['id' => '123']]]]]]);

    ProductHuntApiCheck::new()->run();
    ProductHuntApiCheck::new()->run();
    $troisieme = ProductHuntApiCheck::new()->run();

    Http::assertSentCount(1);
    expect($troisieme->status->equals(Status::ok()))->toBeTrue();
    expect($troisieme->shortSummary)->toBe('fonctionnelle');
});

it('tolère deux échecs réseau puis déclare la panne au troisième', function () {
    for ($i = 1; $i <= 3; $i++) {
        Http::fake(fn () => throw new ConnectionException('connexion refusée'));
        // on purge la mesure AVANT chaque passage : sans cela l'etranglement rejouerait le
        // verdict precedent au lieu de refaire un essai, et le compteur n'avancerait jamais.
        Cache::forget('tests:ph:mesure');

        $resultat = ProductHuntApiCheck::new()->run();

        if ($i <= 2) {
            expect($resultat->status->equals(Status::warning()))->toBeTrue();
            expect($resultat->shortSummary)->toBe('échec temporaire');
        } else {
            expect($resultat->status->equals(Status::failed()))->toBeTrue();
            expect($resultat->shortSummary)->toBe('échec persistant');
            expect($resultat->getNotificationMessage())->toContain('3 essais consécutifs');
        }
    }
});

// une panne non memorisee relancerait un appel reseau a chaque passage du planificateur
it('mémorise aussi les pannes, pour ne pas rappeler ProductHunt chaque minute', function () {
    Http::fake(fn () => throw new ConnectionException('connexion refusée'));

    ProductHuntApiCheck::new()->run();

    $memoire = Cache::get('tests:ph:mesure');

    expect($memoire)->toBeArray();
    expect($memoire)->toHaveKey('statut');
    expect($memoire)->toHaveKey('horodatage');
});

// Le drapeau coupe l'ENVOI, jamais la MESURE : c'est la sortie propre si le courriel horaire
// (le delai anti-rafale de Spatie est global par canal) devient du bruit.
it('peut taire le courriel sans rien perdre de la mesure', function () {
    config()->set('health.producthunt.notify_by_mail', false);
    producthuntFake(['errors' => [['error' => 'invalid_oauth_token']]], 401);

    $resultat = ProductHuntApiCheck::new()->run();

    expect($resultat->status->equals(Status::failed()))->toBeTrue();
    expect($resultat->shortSummary)->toBe('jeton invalide');
    expect($resultat->getNotificationMessage())->toBe('');
});

it('parle par défaut, contrairement au drapeau OpenRouter', function () {
    producthuntFake(['errors' => [['error' => 'invalid_oauth_token']]], 401);

    $resultat = ProductHuntApiCheck::new()->run();

    expect($resultat->getNotificationMessage())->toContain('api.producthunt.com/v2/oauth/applications');
});
