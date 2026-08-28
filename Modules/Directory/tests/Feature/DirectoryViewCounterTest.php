<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Couvre le correctif 2026-08-28 (incident 2026-08-13, recoupement GA4 propriété 500300528,
 * janvier 2026 à aujourd'hui - mesure : jusqu'à 652x le trafic humain réel selon la fiche, ex.
 * FLUX 1 957 affichés contre 3 vues réelles) : PublicDirectoryController::show() délègue
 * désormais à Modules\Core\Services\ViewCounterService::record() au lieu d'un
 * $tool->increment('clicks_count') brut, sans tri robots ni déduplication. L'annuaire était le
 * SEUL module resté sur ce mécanisme - Tools, Authors, News et Dictionary étaient déjà passés
 * par le service (voir Modules/Core/tests/Feature/ViewCounterServiceTest.php pour le test
 * générique du service lui-même). Ce fichier-ci prouve l'INTÉGRATION réelle côté annuaire : le
 * contrôleur, ET la colonne clicks_count - nom propre à Directory, PAS views_count comme les 3
 * autres modules - avec son jumeau clicks_count_verified (migration
 * 2026_08_28_100000_add_clicks_count_verified_to_directory_tools.php).
 *
 * Polyfill FIELD() : même contournement que Modules/Directory/tests/Feature/AffiliateLinkTest.php
 * et ThinContentNoindexTest.php (sqlite :memory: de la suite de tests n'a pas la fonction MySQL
 * FIELD() utilisée par show() pour trier les ressources - limitation pré-existante, sans rapport
 * avec ce correctif).
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\ViewCounterService;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

beforeEach(function () {
    $pdo = DB::connection()->getPdo();
    if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
        $pdo->sqliteCreateFunction('FIELD', function (...$args) {
            $needle = array_shift($args);
            foreach ($args as $i => $value) {
                if ($needle === $value) {
                    return $i + 1;
                }
            }

            return 0;
        });
    }
});

function makeViewCounterTestTool(string $slug): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->setTranslation('name', 'fr_CA', 'Outil compteur '.$slug);
    $tool->setTranslation('slug', 'fr_CA', $slug);
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->url = 'https://exemple-'.$slug.'.test';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->save();
    $tool->refresh();

    return $tool;
}

// ── 1. Le service, appliqué au modèle et à la colonne propres à l'annuaire ─────────────────

test('un appel direct au service incrémente clicks_count ET son jumeau clicks_count_verified', function () {
    $tool = makeViewCounterTestTool('outil-vc-service-normal');

    $request = Request::create('/annuaire/'.$tool->slug, 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/128.0',
        'REMOTE_ADDR' => '203.0.113.20',
    ]);
    app()->instance('request', $request);

    ViewCounterService::record($tool, 'clicks_count');

    $tool->refresh();
    expect($tool->clicks_count)->toBe(1)
        ->and($tool->clicks_count_verified)->toBe(1);
});

test('un appel direct au service avec un user-agent de robot déclaré n\'incrémente ni l\'un ni l\'autre', function () {
    $tool = makeViewCounterTestTool('outil-vc-service-robot');

    $request = Request::create('/annuaire/'.$tool->slug, 'GET', [], [], [], [
        'HTTP_USER_AGENT' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'REMOTE_ADDR' => '203.0.113.21',
    ]);
    app()->instance('request', $request);

    ViewCounterService::record($tool, 'clicks_count');

    $tool->refresh();
    expect($tool->clicks_count)->toBe(0)
        ->and($tool->clicks_count_verified)->toBe(0);
});

// ── 2. Le contrôleur réel (route directory.show) - preuve que show() délègue bien au service ──

test('une visite normale de la fiche /annuaire/{slug} incrémente clicks_count ET clicks_count_verified', function () {
    $tool = makeViewCounterTestTool('outil-vc-http-normal');

    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0'])
        ->get(route('directory.show', $tool->slug));

    $response->assertOk();

    $tool->refresh();
    expect($tool->clicks_count)->toBe(1)
        ->and($tool->clicks_count_verified)->toBe(1);
});

test('une visite de /annuaire/{slug} par un robot déclaré n\'incrémente ni clicks_count ni clicks_count_verified', function () {
    $tool = makeViewCounterTestTool('outil-vc-http-robot');

    $response = $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'])
        ->get(route('directory.show', $tool->slug));

    $response->assertOk();

    $tool->refresh();
    expect($tool->clicks_count)->toBe(0)
        ->and($tool->clicks_count_verified)->toBe(0);
});
