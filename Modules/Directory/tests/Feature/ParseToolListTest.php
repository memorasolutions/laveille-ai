<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->path = base_path('Modules/Directory/resources/views/public/show.blade.php');
    $this->content = file_get_contents($this->path);
});

it('declare la fonction anonyme _parseToolList', function () {
    expect($this->content)->toContain('$_parseToolList = function (?string $raw): array');
});

it('retourne un tableau vide si raw est vide', function () {
    expect($this->content)->toContain('if (empty(trim((string) $raw))) return [];');
});

it('detecte les sauts de ligne dans le texte brut', function () {
    expect($this->content)->toContain('if (str_contains($raw, "\n"))');
});

it('divise le texte par sauts de ligne avec gestion CRLF', function () {
    expect($this->content)->toContain("preg_split('/\\r?\\n/', trim(\$raw))");
});

it('nettoie les puces markdown avec regex specifique', function () {
    expect($this->content)->toContain('/^[-*•]\s*/');
});

it('filtre les elements vides apres nettoyage', function () {
    expect($this->content)->toContain('array_filter');
    expect($this->content)->toContain("fn(\$i) => \$i !== ''");
});

it('utilise explode comme repli CSV si pas de saut de ligne', function () {
    expect($this->content)->toContain("explode(',', \$raw)");
});

it('utilise _parseToolList pour core_features', function () {
    expect($this->content)->toContain('_parseToolList($tool->core_features)');
});

it('utilise _parseToolList pour use_cases', function () {
    expect($this->content)->toContain('_parseToolList($tool->use_cases)');
});

it('utilise _parseToolList pour pros et cons', function () {
    expect($this->content)->toContain('_parseToolList($tool->pros)');
    expect($this->content)->toContain('_parseToolList($tool->cons)');
});
