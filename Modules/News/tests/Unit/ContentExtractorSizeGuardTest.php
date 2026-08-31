<?php

declare(strict_types=1);

/**
 * Garde-fou memoire de ContentExtractor::extract() (2026-08-31). En production, une page
 * anormalement volumineuse telechargee puis passee a Readability faisait exploser
 * Masterminds\HTML5 (dependance jamais appelee directement par ce projet, utilisee en interne
 * par fivefilters/readability.php) : 391 epuisements memoire mesures (plafond 128 Mo CLI), pile
 * dans vendor/masterminds/html5/src/HTML5/Parser/Scanner.php. Ces tests abaissent le plafond via
 * Config::set() plutot que d'allouer une chaine de plusieurs Mo - la garde se verifie sans jamais
 * reproduire la charge memoire qu'elle existe pour eviter.
 */

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Modules\News\Services\ContentExtractor;

uses(Tests\TestCase::class);

it('extract() retourne null et n\'appelle jamais Readability quand le corps HTML depasse le plafond configure', function () {
    Config::set('news.extraction_max_bytes', 50);

    Http::fake([
        '*' => Http::response(str_repeat('a', 200), 200),
    ]);

    $result = (new ContentExtractor())->extract('https://exemple-editeur.com/gros-article');

    expect($result)->toBeNull();
});

it('extract() traite normalement une page sous le plafond (comportement inchange)', function () {
    // Plafond par defaut remis explicitement - aucune dependance a l'ordre d'execution des tests.
    Config::set('news.extraction_max_bytes', 3000000);

    $sentence = 'Ceci est une phrase de test qui decrit un evenement technologique important '
        .'survenu recemment au Quebec et ailleurs dans le monde francophone. ';
    $body = str_repeat('<p>'.str_repeat($sentence, 3).'</p>', 4);
    $html = '<!DOCTYPE html><html lang="fr"><head><title>Article de test</title></head>'
        .'<body><article><h1>Article de test</h1>'.$body.'</article></body></html>';

    Http::fake([
        '*' => Http::response($html, 200),
    ]);

    $result = (new ContentExtractor())->extract('https://exemple-editeur.com/article-normal');

    expect($result)->not->toBeNull();
    expect($result['word_count'])->toBeGreaterThanOrEqual(50);
    expect($result['content'])->not->toBeEmpty();
});

it('la cle de config news.extraction_max_bytes vaut 3 000 000 par defaut', function () {
    expect((int) config('news.extraction_max_bytes'))->toBe(3000000);
});
