<?php

declare(strict_types=1);

use Modules\Newsletter\Services\DigestContentService;

/*
 * Tests reflexifs DigestContentService — couverture méthodes pures sans DB ni HTTP.
 * Couvre stripMarkdown (regex), getTechniqueExplanation (match), generateWeeklyPrompt (lookup).
 */

it('stripMarkdown retire le gras avec asterisks doubles', function () {
    expect(DigestContentService::stripMarkdown('Ceci est **important**'))
        ->toBe('Ceci est important');
});

it('stripMarkdown retire les headers Markdown', function () {
    expect(DigestContentService::stripMarkdown('# Titre principal'))
        ->toBe('Titre principal');
});

it('stripMarkdown retire les liens Markdown en preservant le texte', function () {
    expect(DigestContentService::stripMarkdown('Voir [notre site](https://example.com) pour plus'))
        ->toBe('Voir notre site pour plus');
});

it('stripMarkdown supprime les blocs de code backticks integralement', function () {
    expect(DigestContentService::stripMarkdown('Du code `inline` ici'))
        ->toBe('Du code  ici');
});

it('stripMarkdown gere un mix complet bold + header + lien', function () {
    $input = "## Section\nCeci est **très important** voir [docs](https://x.com) maintenant.";
    $output = DigestContentService::stripMarkdown($input);
    expect($output)->toContain('Section')
        ->and($output)->toContain('très important')
        ->and($output)->toContain('docs maintenant')
        ->and($output)->not->toContain('**')
        ->and($output)->not->toContain('##')
        ->and($output)->not->toContain('https://x.com');
});

it('stripMarkdown trim les espaces externes', function () {
    expect(DigestContentService::stripMarkdown('  hello  '))->toBe('hello');
});

it('getTechniqueExplanation reconnait chaine de pensee avec accents', function () {
    expect(DigestContentService::getTechniqueExplanation('chaîne de pensée'))
        ->toContain('étape par étape');
});

it('getTechniqueExplanation gere la casse mixte', function () {
    expect(DigestContentService::getTechniqueExplanation('ROLE PROMPTING'))
        ->toContain('rôle précis');
});

it('getTechniqueExplanation retourne le default pour une technique inconnue', function () {
    $result = DigestContentService::getTechniqueExplanation('technique_inconnue_xyz');
    expect($result)
        ->toContain('Cette technique aide')
        ->and($result)->toContain('reformuler');
});

it('getTechniqueExplanation reconnait self-refine', function () {
    expect(DigestContentService::getTechniqueExplanation('self-refine'))
        ->toContain('vérifier')
        ->and(DigestContentService::getTechniqueExplanation('self-refine'))->toContain('améliorer');
});

it('getTechniqueExplanation reconnait few-shot', function () {
    expect(DigestContentService::getTechniqueExplanation('few-shot'))
        ->toContain('exemples');
});

it('generateWeeklyPrompt mappe chain-of-thought sur prompt statique', function () {
    $result = DigestContentService::generateWeeklyPrompt('chain-of-thought');
    expect($result)->toHaveKeys(['prompt', 'technique'])
        ->and($result['prompt'])->toContain('étape par étape')
        ->and($result['technique'])->toContain('chain-of-thought');
});

it('generateWeeklyPrompt mappe role prompting case-insensitive', function () {
    $result = DigestContentService::generateWeeklyPrompt('Role Prompting');
    expect($result['technique'])->toContain('Role Prompting')
        ->and($result['prompt'])->toContain('chef cuisinier');
});

it('generateWeeklyPrompt mappe chaine de pensee sans accent', function () {
    $result = DigestContentService::generateWeeklyPrompt('chaine de pensee');
    expect($result)->toHaveKeys(['prompt', 'technique'])
        ->and($result['prompt'])->toContain('étape par étape');
});

it('generateWeeklyPrompt mappe partiel sur terme long contenant la cle', function () {
    $result = DigestContentService::generateWeeklyPrompt('Une explication sur tree-of-thought avancé');
    expect($result['technique'])->toContain('tree-of-thought')
        ->and($result['prompt'])->toContain('approches');
});

it('generateWeeklyPrompt mappe few-shot avec exemples concrets', function () {
    $result = DigestContentService::generateWeeklyPrompt('few-shot');
    expect($result)->toHaveKeys(['prompt', 'technique'])
        ->and($result['prompt'])->toContain('exemples')
        ->and($result['technique'])->toContain('few-shot');
});

it('generateWeeklyPrompt mappe self-consistency avec consensus', function () {
    $result = DigestContentService::generateWeeklyPrompt('self-consistency');
    expect($result)->toHaveKeys(['prompt', 'technique'])
        ->and($result['prompt'])->not->toBeEmpty()
        ->and($result['technique'])->toContain('self-consistency');
});
