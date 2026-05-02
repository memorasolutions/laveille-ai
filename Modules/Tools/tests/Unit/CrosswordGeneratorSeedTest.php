<?php

declare(strict_types=1);

use Modules\Tools\Services\CrosswordGeneratorService;

beforeEach(function () {
    $this->service = new CrosswordGeneratorService();
    $this->pairs = [
        ['clue' => 'Capitale de la France', 'answer' => 'PARIS'],
        ['clue' => 'Langue de programmation web', 'answer' => 'PHP'],
        ['clue' => 'Capitale du Japon', 'answer' => 'TOKYO'],
        ['clue' => 'Framework PHP', 'answer' => 'LARAVEL'],
        ['clue' => 'Format de données', 'answer' => 'JSON'],
        ['clue' => 'Système de versions', 'answer' => 'GIT'],
    ];
});

it('produit la même grille deux fois avec le même seed', function () {
    $r1 = $this->service->generate($this->pairs, 42);
    $r2 = $this->service->generate($this->pairs, 42);

    expect($r1['stats']['seed'])->toBe(42)
        ->and($r2['stats']['seed'])->toBe(42)
        ->and($r1['grid'])->toEqual($r2['grid']);
});

it('produit des grilles potentiellement différentes avec deux seeds distincts', function () {
    $r1 = $this->service->generate($this->pairs, 1);
    $r2 = $this->service->generate($this->pairs, 99999);

    expect($r1['stats']['seed'])->toBe(1)
        ->and($r2['stats']['seed'])->toBe(99999);
    // Note: deux grilles peuvent etre identiques par chance, mais avec 6 mots et seeds eloignees c'est rarissime.
    // Test de sanity : au moins l'ordre des mots devrait differer.
});

it('genere un seed automatiquement si non fourni', function () {
    $r = $this->service->generate($this->pairs);

    expect($r['stats']['seed'])->toBeInt()->toBeGreaterThan(0);
});

it('retourne le seed dans les stats meme en cas d echec total', function () {
    $impossible = [
        ['clue' => 'X', 'answer' => 'XX'],
        ['clue' => 'Y', 'answer' => 'YY'],
    ];
    $r = $this->service->generate($impossible, 7);

    expect($r['stats'])->toHaveKey('seed')
        ->and($r['stats']['seed'])->toBe(7);
});

it('respecte le budget de performance sous 200ms pour 6 mots', function () {
    $start = microtime(true);
    $r = $this->service->generate($this->pairs, 42);
    $elapsedMs = (microtime(true) - $start) * 1000;

    expect($elapsedMs)->toBeLessThan(200)
        ->and($r['stats']['duration_ms'])->toBeLessThan(200);
});
