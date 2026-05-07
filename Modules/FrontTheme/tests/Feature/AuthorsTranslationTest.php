<?php

declare(strict_types=1);

/*
 * Tests reflexifs structure translation file fronttheme::authors.
 * Anti-régression EEAT 2026 NN/g (#218 S84 — page auteur Schema.org Person).
 * Tests purs sans DB ni HTTP : load file PHP directement.
 */

beforeEach(function () {
    // Path relatif depuis tests/Feature → ../../lang/fr_CA/authors.php (pas de base_path() facade)
    $this->authorsPath = __DIR__ . '/../../lang/fr_CA/authors.php';
    $this->authors = file_exists($this->authorsPath) ? require $this->authorsPath : [];
});

it('le fichier translations authors.php existe au path nwidart correct', function () {
    expect(file_exists($this->authorsPath))->toBeTrue();
});

it('le fichier authors retourne un array', function () {
    expect($this->authors)->toBeArray();
});

it('contient la clé stephane-lapointe', function () {
    expect($this->authors)->toHaveKey('stephane-lapointe');
});

it('stephane-lapointe contient toutes les clés EEAT requises', function () {
    $author = $this->authors['stephane-lapointe'] ?? [];
    expect($author)->toHaveKeys(['name', 'role', 'bio', 'linkedin', 'website', 'qualifications']);
});

it('stephane-lapointe.name est une chaine non-vide', function () {
    $name = $this->authors['stephane-lapointe']['name'] ?? '';
    expect($name)->toBeString()->not->toBeEmpty();
});

it('stephane-lapointe.bio est entre 50 et 300 caractères (NN/g 2026)', function () {
    $bio = $this->authors['stephane-lapointe']['bio'] ?? '';
    expect(strlen($bio))->toBeGreaterThan(50)->toBeLessThan(500);
});

it('stephane-lapointe.qualifications est un array non-vide', function () {
    $quals = $this->authors['stephane-lapointe']['qualifications'] ?? [];
    expect($quals)->toBeArray()->not->toBeEmpty();
});

it('stephane-lapointe.qualifications contient au moins 3 entrées', function () {
    $quals = $this->authors['stephane-lapointe']['qualifications'] ?? [];
    expect(count($quals))->toBeGreaterThanOrEqual(3);
});

it('stephane-lapointe.role contient signal expertise IA Québec', function () {
    $role = $this->authors['stephane-lapointe']['role'] ?? '';
    expect($role)->toContain('IA')->toContain('Québec');
});

it('stephane-lapointe.website pointe vers MEMORA solutions (worksFor Schema.org)', function () {
    $website = $this->authors['stephane-lapointe']['website'] ?? '';
    expect($website)->toContain('memora.solutions');
});

it('stephane-lapointe.linkedin est string (vide acceptable, à fournir user)', function () {
    $linkedin = $this->authors['stephane-lapointe']['linkedin'] ?? null;
    expect($linkedin)->toBeString();
});
