<?php

declare(strict_types=1);

use Modules\Core\Services\GlossaryLinkifier;

it('extracts base name from qualifier "X (Y)"', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('Loi 25 (Québec)'))
        ->toBe(['Loi 25']);
});

it('extracts both base + qualifier when qualifier is an acronym', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('Réseau convolutif (CNN)'))
        ->toBe(['Réseau convolutif', 'CNN']);

    expect(GlossaryLinkifier::extractQualifierAliases('Explicabilité (XAI)'))
        ->toBe(['Explicabilité', 'XAI']);

    expect(GlossaryLinkifier::extractQualifierAliases('APE (Automatic Prompt Engineer)'))
        ->toBe(['APE']);
});

it('extracts CapCase short qualifier (ReAct)', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('ReAct (Reason + Act)'))
        ->toBe(['ReAct']);
});

it('extracts only base when qualifier is descriptive sentence', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('IoT (Internet des objets)'))
        ->toBe(['IoT']);
});

it('returns empty for name without qualifier', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('Vibe Coding'))
        ->toBe([]);
    expect(GlossaryLinkifier::extractQualifierAliases('ChatGPT'))
        ->toBe([]);
});

it('skips qualifier that is single lowercase word (mécanisme)', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('Attention (mécanisme)'))
        ->toBe(['Attention']);
});

it('handles edge case of empty qualifier', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('Foo ()'))
        ->toBe([]);
});

// === #146 Phase A : aliases morphologiques FR ===

it('generates plural -s for simple FR term', function () {
    expect(GlossaryLinkifier::extractMorphologicalAliases('Tensor'))
        ->toContain('Tensors')
        ->toContain('tensors')
        ->toContain('tensor');
});

it('generates -aux plural for -al ending', function () {
    expect(GlossaryLinkifier::extractMorphologicalAliases('algorithme régional'))
        ->toContain('algorithme régionaux');
});

it('generates -eaux for -eau ending', function () {
    expect(GlossaryLinkifier::extractMorphologicalAliases('réseau'))
        ->toContain('réseaux')
        ->toContain('Réseau');
});

it('skips morpho for short acronyms (IA, ML, IoT)', function () {
    expect(GlossaryLinkifier::extractMorphologicalAliases('CNN'))->toBe([]);
    expect(GlossaryLinkifier::extractMorphologicalAliases('IoT'))->toBe([]);
});

it('does not double-pluralize already-plural terms', function () {
    expect(GlossaryLinkifier::extractMorphologicalAliases('Skills'))
        ->not->toContain('Skillss');
});

it('handles compound term "processus cognitif"', function () {
    $aliases = GlossaryLinkifier::extractMorphologicalAliases('processus cognitif');
    expect($aliases)
        ->toContain('processus cognitifs')
        ->toContain('Processus Cognitif');
});

it('generates lowercase + Titled + ucfirst variants', function () {
    $aliases = GlossaryLinkifier::extractMorphologicalAliases('Anthropomorphisme');
    expect($aliases)
        ->toContain('anthropomorphisme')
        ->toContain('Anthropomorphismes');
});
