<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests EcosystemResolverService::resolve() — regroupement visuel des outils de l'annuaire par
 * écosystème d'entreprise (colonne directory_tools.ecosystem_tag). Couvre la correspondance
 * exacte de domaine racine (via jeremykendall/php-domain-parser + Public Suffix List) et
 * l'anti-faux-positif explicitement signalé lors de la recherche (jamais de str_contains).
 */

declare(strict_types=1);

use Modules\Directory\Services\EcosystemResolverService;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->resolver = new EcosystemResolverService();
});

test('detecte le tag ecosysteme pour un domaine racine exact (openai.com)', function () {
    expect($this->resolver->resolve('https://openai.com'))->toBe('openai');
});

test('detecte le tag ecosysteme via un sous-domaine (platform.openai.com)', function () {
    expect($this->resolver->resolve('https://platform.openai.com/docs'))->toBe('openai');
});

test('detecte plusieurs domaines distincts mappes vers le meme tag (chatgpt.com et sora.com => openai)', function () {
    expect($this->resolver->resolve('https://chatgpt.com'))->toBe('openai');
    expect($this->resolver->resolve('https://sora.com'))->toBe('openai');
});

test('resout un domaine hors de la table config vers un autre tag (claude.ai => anthropic)', function () {
    expect($this->resolver->resolve('https://claude.ai'))->toBe('anthropic');
});

test('normalise le prefixe www. avant la correspondance', function () {
    expect($this->resolver->resolve('https://www.anthropic.com/claude'))->toBe('anthropic');
});

test('ne matche JAMAIS un faux positif par sous-chaine (fakeopenai.com != openai.com)', function () {
    // Piège explicitement signalé par la recherche : str_contains($url, 'openai.com')
    // accepterait à tort "fakeopenai.com". La correspondance doit être EXACTE sur le domaine
    // racine résolu par la Public Suffix List, jamais une comparaison de sous-chaîne.
    expect($this->resolver->resolve('https://fakeopenai.com'))->toBeNull();
});

test('retourne null pour un domaine totalement inconnu de la table', function () {
    expect($this->resolver->resolve('https://un-outil-totalement-inconnu-xyz123.example'))->toBeNull();
});

test('retourne null pour une URL vide ou invalide sans planter', function () {
    expect($this->resolver->resolve(''))->toBeNull();
    expect($this->resolver->resolve('pas-une-url'))->toBeNull();
});
