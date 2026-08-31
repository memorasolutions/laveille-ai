<?php

declare(strict_types=1);

/**
 * Garde-fous memoire de ContentExtractor (ticket #2110). Deux couches mesurees en production
 * le 2026-08-31, dans cet ordre :
 *
 * 1. Un garde de taille brute sur le HTML avant Readability (extractInProcess) - insuffisant a
 *    lui seul : une nouvelle exhaustion memoire a ete mesuree le jour meme de son deploiement,
 *    sur la MEME pile d'appel (Masterminds\HTML5, Scanner.php:351), pour un document pourtant
 *    sous le plafond. La taille brute ne predit pas l'amplification memoire du parsing.
 * 2. L'isolation de l'extraction dans un sous-processus PHP dedie et jetable (extract() ->
 *    news:extract-isolated) - un epuisement memoire a l'interieur ne tue plus jamais le cron
 *    news:fetch parent, quelle que soit l'ampleur du probleme cote dependance.
 *
 * Les tests de la couche 1 appellent extractInProcess() directement (le dispatcher extract()
 * isolerait sinon l'appel dans un vrai sous-processus, hors de portee de Http::fake()). Les
 * tests de la couche 2 appellent extract() avec Process::fake() pour simuler un sous-processus
 * qui reussit ou qui plante (OOM), sans jamais allouer de memoire reelle pendant la suite.
 */

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Modules\News\Services\ContentExtractor;

uses(Tests\TestCase::class);

// ── Couche 1 : garde de taille brute (extractInProcess) ────────────────────────────────

it('extractInProcess() retourne null et n\'appelle jamais Readability quand le corps HTML depasse le plafond configure', function () {
    Config::set('news.extraction_max_bytes', 50);

    Http::fake([
        '*' => Http::response(str_repeat('a', 200), 200),
    ]);

    $result = (new ContentExtractor())->extractInProcess('https://exemple-editeur.com/gros-article');

    expect($result)->toBeNull();
});

it('extractInProcess() traite normalement une page sous le plafond (comportement inchange)', function () {
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

    $result = (new ContentExtractor())->extractInProcess('https://exemple-editeur.com/article-normal');

    expect($result)->not->toBeNull();
    expect($result['word_count'])->toBeGreaterThanOrEqual(50);
    expect($result['content'])->not->toBeEmpty();
});

it('la cle de config news.extraction_max_bytes vaut 3 000 000 par defaut', function () {
    expect((int) config('news.extraction_max_bytes'))->toBe(3000000);
});

// ── Couche 2 : isolation en sous-processus (extract) ────────────────────────────────────

it('la cle de config news.extraction_isolated_process vaut true par defaut', function () {
    expect((bool) config('news.extraction_isolated_process'))->toBeTrue();
});

it('extract() decode la sortie JSON du sous-processus isole quand il reussit', function () {
    Config::set('news.extraction_isolated_process', true);

    $expected = [
        'title' => 'Titre isole',
        'content' => 'Contenu isole suffisamment long pour un test.',
        'html' => '<p>Contenu isole suffisamment long pour un test.</p>',
        'image' => null,
        'author' => null,
        'word_count' => 60,
    ];

    Process::fake([
        '*' => Process::result(output: json_encode($expected), exitCode: 0),
    ]);

    $result = (new ContentExtractor())->extract('https://exemple-editeur.com/article-isole');

    expect($result)->toBe($expected);
});

it('extract() retourne null sans propager l\'echec quand le sous-processus isole plante (OOM simule)', function () {
    Config::set('news.extraction_isolated_process', true);

    // Code de sortie 137 = SIGKILL, signature typique d'un processus tue par epuisement
    // memoire (OOM killer ou limite memory_limit) - exactement le scenario qui, avant ce
    // correctif, faisait mourir tout le cron news:fetch en cours.
    Process::fake([
        '*' => Process::result(output: '', errorOutput: 'Allowed memory size exhausted', exitCode: 137),
    ]);

    $result = (new ContentExtractor())->extract('https://exemple-editeur.com/article-qui-fait-planter-le-sous-processus');

    expect($result)->toBeNull();
});

it('extract() retourne null quand le sous-processus isole renvoie une sortie non-JSON', function () {
    Config::set('news.extraction_isolated_process', true);

    Process::fake([
        '*' => Process::result(output: 'sortie corrompue, pas du JSON', exitCode: 0),
    ]);

    $result = (new ContentExtractor())->extract('https://exemple-editeur.com/article-sortie-corrompue');

    expect($result)->toBeNull();
});
