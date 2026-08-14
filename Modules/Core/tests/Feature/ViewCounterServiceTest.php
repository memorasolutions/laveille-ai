<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Http\Request;
use Modules\Core\Services\ViewCounterService;
use Modules\Tools\Models\Tool;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Couvre l'incident 2026-08-13 (increment() sans filtre robots ni
 * déduplication, rapport vues/clics réels de 8 à 487x selon la fiche).
 * Cible UNIQUEMENT Modules\Core\Services\ViewCounterService, service
 * partagé désormais appelé par Tools, Authors, News et Dictionary.
 */
function makeVcTool(): Tool
{
    return Tool::create([
        'name' => 'Outil test compteur',
        'slug' => 'outil-test-'.uniqid(),
    ]);
}

function bindVcRequest(string $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/128.0', string $ip = '203.0.113.10'): void
{
    $request = Request::create('/outils/exemple', 'GET', [], [], [], [
        'HTTP_USER_AGENT' => $userAgent,
        'REMOTE_ADDR' => $ip,
    ]);
    app()->instance('request', $request);
}

test('une requête ordinaire incrémente le compteur historique ET le compteur propre', function () {
    $tool = makeVcTool();
    bindVcRequest();

    ViewCounterService::record($tool, 'views_count');

    $tool->refresh();
    expect($tool->views_count)->toBe(1)
        ->and($tool->views_count_verified)->toBe(1);
});

test('une requête portant une signature de robot connue n\'incrémente rien', function () {
    $tool = makeVcTool();
    bindVcRequest('Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)');

    ViewCounterService::record($tool, 'views_count');

    $tool->refresh();
    expect($tool->views_count)->toBe(0)
        ->and($tool->views_count_verified)->toBe(0);
});

test('deux requêtes rapprochées du même visiteur ne comptent qu\'une seule fois', function () {
    $tool = makeVcTool();
    bindVcRequest(ip: '198.51.100.7');

    ViewCounterService::record($tool, 'views_count');
    // Même IP + même UA (donc même empreinte) : la seconde requête, dans la
    // fenêtre de déduplication, ne doit pas faire progresser le compteur.
    bindVcRequest(ip: '198.51.100.7');
    ViewCounterService::record($tool, 'views_count');

    $tool->refresh();
    expect($tool->views_count)->toBe(1);
});

test('une défaillance du mécanisme de comptage n\'empêche jamais l\'affichage de la page', function () {
    // Modèle jamais persisté (pas de clé primaire) : whereKey(null) doit être
    // absorbé silencieusement, sans exception, exactement comme si la page
    // s'affichait normalement malgré un compteur en échec.
    $tool = new Tool(['name' => 'Non persisté', 'slug' => 'non-persiste-'.uniqid()]);
    bindVcRequest();

    $exception = null;
    try {
        ViewCounterService::record($tool, 'views_count');
    } catch (Throwable $e) {
        $exception = $e;
    }

    expect($exception)->toBeNull();
});
