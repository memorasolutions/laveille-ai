<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Health\Checks\OpcacheCheck;
use Modules\Health\Http\Controllers\OpcacheStatusController;
use Modules\Health\Notifications\CheckFailedNotification;
use Spatie\Health\Enums\Status;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    config()->set('health.opcache', [
        'enabled' => true,
        'path' => '_sante/opcache',
        'token' => 'jeton-secret',
        'timeout' => 5,
        'warn_keys_percent' => 75,
        'fail_keys_percent' => 90,
        'warn_memory_percent' => 75,
        'fail_memory_percent' => 90,
        'warn_interned_percent' => 80,
        'fail_interned_percent' => 95,
        'warn_refusals_delta' => 100,
        'fail_refusals_delta' => 1000,
        'refusals_cache_key' => 'tests:health:opcache:refusals',
    ]);

    Cache::forget('tests:health:opcache:refusals');
});

function opcachePayload(array $overrides = []): array
{
    return array_replace_recursive([
        'sapi' => 'fpm-fcgi',
        'memory_usage' => [
            'used_memory' => 20,
            'free_memory' => 75,
            'wasted_memory' => 5,
            'current_wasted_percentage' => 5.0,
        ],
        'opcache_statistics' => [
            'num_cached_scripts' => 1000,
            'num_cached_keys' => 2000,
            'max_cached_keys' => 10000,
            'hits' => 50000,
            'misses' => 1010,
            'oom_restarts' => 0,
            'hash_restarts' => 0,
            'manual_restarts' => 0,
        ],
        'interned_strings_usage' => [
            'buffer_size' => 100,
            'used_memory' => 20,
            'free_memory' => 80,
            'number_of_strings' => 1000,
        ],
        'cache_full' => false,
    ], $overrides);
}

it('retourne 404 sans jeton', function () {
    $this->get(route('health.opcache.status'))->assertNotFound();
});

it('retourne 404 avec un mauvais jeton', function () {
    $this->get(route('health.opcache.status', ['token' => 'mauvais']))->assertNotFound();
});

it('retourne 404 lorsque la surveillance est désactivée', function () {
    config()->set('health.opcache.enabled', false);

    $this->get(route('health.opcache.status', ['token' => 'jeton-secret']))->assertNotFound();
});

it('retourne le statut OPcache avec le bon jeton', function () {
    $controller = Mockery::mock(OpcacheStatusController::class)->makePartial();
    $controller->shouldReceive('readOpcacheStatus')->once()->andReturn(opcachePayload());
    app()->instance(OpcacheStatusController::class, $controller);

    $this->getJson(route('health.opcache.status', ['token' => 'jeton-secret']))
        ->assertOk()
        ->assertJsonPath('sapi', PHP_SAPI)
        ->assertJsonPath('opcache_statistics.num_cached_scripts', 1000);
});

it('échoue lorsque la requête HTTP échoue', function () {
    Http::fake(['*' => Http::response('Indisponible', 503)]);

    $result = OpcacheCheck::new()->run();

    expect($result->status->equals(Status::failed()))->toBeTrue()
        ->and($result->getNotificationMessage())->toContain('Impossible de mesurer OPcache');
});

it('reste silencieux quand le 503 vient du mode maintenance (deploiement en cours)', function () {
    // Faux signal reel du 2026-08-12 : le deploiement execute `php artisan down --retry=15`,
    // donc Laravel repond 503 a TOUT pendant le rsync - point de controle inclus. Le cron de
    // sante tombe dedans et alertait « intervention rapide » alors que rien n'est casse.
    // L'en-tete Retry-After (pose par --retry) est la signature d'une indisponibilite VOULUE.
    Http::fake(['*' => Http::response('En maintenance', 503, ['Retry-After' => '15'])]);

    $result = OpcacheCheck::new()->run();

    expect($result->status->equals(Status::ok()))->toBeTrue()
        ->and($result->getNotificationMessage())->toContain('maintenance');
});

it('alerte quand même sur un 503 SANS Retry-After (vraie saturation PHP-FPM)', function () {
    // Verrou du correctif precedent : le silence doit rester borne au SEUL cas maintenance.
    // Une saturation reelle de PHP-FPM renvoie un 503 nu - elle doit continuer d'alerter.
    Http::fake(['*' => Http::response('Service Unavailable', 503)]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::failed()))->toBeTrue();
});

it('affiche la marche à suivre "mesure impossible", jamais la procédure de capacité, sur un timeout', function () {
    // Incident reel du 2026-08-01 21h11 Quebec : un timeout cURL (mesure impossible, aucun
    // pourcentage disponible) affichait quand meme « augmentez la directive saturee », une
    // consigne fausse pour ce cas puisqu'aucune capacite n'a pu etre mesuree.
    Http::fake(function () {
        throw new \Illuminate\Http\Client\ConnectionException('cURL error 28: Operation timed out');
    });

    $check = OpcacheCheck::new();
    $result = $check->run();
    $result->check = $check;

    expect($result->status->equals(Status::failed()))->toBeTrue()
        ->and($result->meta)->not->toHaveKey('keys_percent');

    $courriel = implode("\n", (new CheckFailedNotification([$result]))->toMail()->introLines);

    expect($courriel)
        ->toContain('Marche à suivre (accès WHM ou hébergeur requis)')
        ->toContain('surcharge PONCTUELLE')
        ->not->toContain('opcache.max_accelerated_files')
        ->not->toContain('Ouvrir /opt/cpanel/ea-php84');
});

it('avertit lorsque les clés dépassent seules leur seuil', function () {
    Http::fake(['*' => Http::response(opcachePayload([
        'opcache_statistics' => ['num_cached_keys' => 8000],
    ]))]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::warning()))->toBeTrue();
});

it('échoue lorsque la mémoire dépasse seule son seuil', function () {
    Http::fake(['*' => Http::response(opcachePayload([
        'memory_usage' => ['used_memory' => 91, 'free_memory' => 8, 'wasted_memory' => 1],
    ]))]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::failed()))->toBeTrue();
});

it('avertit lorsque les chaînes internées dépassent seules leur seuil', function () {
    Http::fake(['*' => Http::response(opcachePayload([
        'interned_strings_usage' => ['used_memory' => 85, 'free_memory' => 15],
    ]))]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::warning()))->toBeTrue();
});

it('échoue immédiatement lorsque le cache est plein', function () {
    Http::fake(['*' => Http::response(opcachePayload(['cache_full' => true]))]);

    expect(OpcacheCheck::new()->run()->status->equals(Status::failed()))->toBeTrue();
});

it('ne déclenche aucun signal sous les seuils', function () {
    Http::fake(['*' => Http::response(opcachePayload())]);

    $result = OpcacheCheck::new()->run();

    expect($result->status->equals(Status::ok()))->toBeTrue()
        ->and($result->shortSummary)->toContain('clés 20.0 %');
});

it('n envoie AUCUN courriel quand tout va bien', function () {
    // Spatie envoie une notification pour tout resultat dont getNotificationMessage() n'est
    // pas vide, QUEL QUE SOIT son statut (RunHealthChecksCommand ligne 116) : le filtrage sur
    // l'echec n'intervient que si only_on_failure est vrai, et il est volontairement faux ici.
    // Un message pose sur un ok() suffisait donc a envoyer un courriel « AVERTISSEMENT »
    // dont le contenu disait « aucune action requise ». Recu en production le 2026-08-01
    // avec des clés a 34,4 %, une mémoire a 33,5 % et zéro refus.
    Http::fake(['*' => Http::response(opcachePayload())]);

    $result = OpcacheCheck::new()->run();

    expect($result->status->equals(Status::ok()))->toBeTrue()
        ->and($result->getNotificationMessage())->toBeEmpty();
});

it('porte un message dès qu il y a vraiment quelque chose à signaler', function () {
    // Le pendant du test precedent : le silence quand tout va bien ne doit pas devenir
    // un silence quand ça va mal.
    Http::fake(['*' => Http::response(opcachePayload([
        'opcache_statistics' => ['num_cached_keys' => 8000],
    ]))]);

    $result = OpcacheCheck::new()->run();

    expect($result->status->equals(Status::warning()))->toBeTrue()
        ->and($result->getNotificationMessage())->not->toBeEmpty();
});

it('calcule la progression des refus entre deux passages', function () {
    Http::fakeSequence()
        ->push(opcachePayload(), 200)
        ->push(opcachePayload(['opcache_statistics' => ['misses' => 1211]]), 200);

    $first = OpcacheCheck::new()->run();
    $second = OpcacheCheck::new()->run();

    expect($first->meta['refusals_delta'])->toBe(0)
        ->and($second->meta['refusals_delta'])->toBe(201);
});

it('ignore une progression des refus quand le cache n est PAS sous pression', function () {
    // Un déploiement invalide puis recompile des centaines de fichiers : les ratés
    // grimpent sans qu'aucun script ne soit refusé. Constaté en production le
    // 2026-08-01, écart passé de 23 à 436 avec un cache rempli à 28,7 pour cent.
    // Sans ce garde, l'alerte sonnerait à chaque mise en ligne.
    Http::fakeSequence()
        ->push(opcachePayload(), 200)
        ->push(opcachePayload(['opcache_statistics' => ['misses' => 1211]]), 200);

    OpcacheCheck::new()->run();
    $second = OpcacheCheck::new()->run();

    expect($second->meta['refusals_delta'])->toBe(201)
        ->and($second->status->equals(Status::ok()))->toBeTrue();
});

it('rend un courriel lisible, sans flottant brut, avec la marche à suivre', function () {
    // Verrouille le correctif du 2026-08-01 : la premiere alerte reellement recue
    // contenait « 29.39999999999999857891452847979962825775146484375 » et un pave JSON,
    // dans un courriel cense etre comprehensible par un humain non technicien.
    Http::fake(['*' => Http::response(opcachePayload([
        'opcache_statistics' => ['num_cached_keys' => 8000],
    ]))]);

    // Spatie renseigne ->check au moment d'executer la commande de sante, pas dans run().
    $check = OpcacheCheck::new();
    $result = $check->run();
    $result->check = $check;

    $lignes = (new CheckFailedNotification([$result]))->toMail()->introLines;
    $courriel = implode("\n", $lignes);

    expect($courriel)
        ->not->toContain('999999999')
        ->not->toContain('{"')
        ->toContain('Table des clés occupée : 80,0 %')
        ->toContain('Cache déclaré plein : non')
        ->toContain('Marche à suivre (accès root WHM requis) :')
        ->toContain('11-opcache-memora.ini')
        ->toContain('/scripts/restartsrv_apache_php_fpm --restart')
        ->toContain('TOUS les sites PHP du serveur');
});

it('n ajoute PAS la marche à suivre quand OPcache va bien', function () {
    // Constaté en production le 2026-08-01 a 16h29 Quebec (20:29 UTC) : un courriel declenche
    // par l'echec d'un AUTRE controle affichait quand meme les 5 etapes « augmentez la directive
    // saturee », juste sous un OPcache annoncant « aucune action requise ». Une consigne
    // contradictoire est une consigne qu'on apprend a ignorer.
    Http::fake(['*' => Http::response(opcachePayload())]);

    $check = OpcacheCheck::new();
    $result = $check->run();
    $result->check = $check;

    $courriel = implode("\n", (new CheckFailedNotification([$result]))->toMail()->introLines);

    expect($result->status->equals(Status::ok()))->toBeTrue()
        ->and($courriel)
        ->not->toContain('Marche à suivre')
        ->not->toContain('restartsrv_apache_php_fpm')
        ->toContain('Table des clés occupée : 20,0 %');
});

it('signale une progression des refus quand le cache EST sous pression', function () {
    $sousPression = ['opcache_statistics' => ['num_cached_keys' => 110000]];

    Http::fakeSequence()
        ->push(opcachePayload($sousPression), 200)
        ->push(opcachePayload(['opcache_statistics' => ['num_cached_keys' => 110000, 'misses' => 1211]]), 200);

    OpcacheCheck::new()->run();
    $second = OpcacheCheck::new()->run();

    expect($second->meta['refusals_delta'])->toBe(201)
        ->and($second->status->equals(Status::failed()))->toBeTrue();
});
