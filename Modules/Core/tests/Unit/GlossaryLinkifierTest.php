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

// Live integration test "Loi 25 → alias auto" volontairement omis ici (requiert bootstrap Laravel + DB).
// Validé en smoke prod post-deploy via _glx_diag.php.
