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

// === 2026-07-25 #1350 : perSection - 1 occurrence par terme PAR SECTION H2 ===
// Utilise Reflection sur walkAndReplace pour tester la logique sans dépendre de loadTerms()/DB.

function glxWalk(\DOMDocument $dom, \DOMNode $node, array $terms, bool $perSection, int $maxOcc): int
{
    $seen = [];
    $linkCount = 0;
    $currentSection = 0;
    $method = new ReflectionMethod(GlossaryLinkifier::class, 'walkAndReplace');
    $method->setAccessible(true);
    $method->invokeArgs(null, [$dom, $node, $terms, &$seen, &$linkCount, 120, null, $maxOcc, $perSection, &$currentSection]);

    return $linkCount;
}

function glxTestDom(): array
{
    $html = '<h2>Section 1</h2><p>Le terme Docker apparait ici. Docker est mentionne une seconde fois.</p>'
        .'<h2>Section 2</h2><p>Docker reapparait dans cette nouvelle section.</p>';
    $dom = new \DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"?><div id="glx-root">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    return [$dom, $dom->getElementById('glx-root')];
}

it('links only the first occurrence globally when perSection is false (comportement historique inchangé)', function () {
    [$dom, $root] = glxTestDom();
    $terms = [['name' => 'Docker', 'slug' => 'docker', 'definition' => 'Test', 'type' => 'glossary', 'url' => '/glossaire/docker', 'match_strategy' => 'loose']];

    $linkCount = glxWalk($dom, $root, $terms, false, 1);

    expect($linkCount)->toBe(1);
});

it('links the term once per section H2 when perSection is true (moins agressant visuellement)', function () {
    [$dom, $root] = glxTestDom();
    $terms = [['name' => 'Docker', 'slug' => 'docker', 'definition' => 'Test', 'type' => 'glossary', 'url' => '/glossaire/docker', 'match_strategy' => 'loose']];

    $linkCount = glxWalk($dom, $root, $terms, true, 1);

    // 2 occurrences dans la section 1 (une seule liée) + 1 occurrence dans la section 2 (liée) = 2 liens.
    expect($linkCount)->toBe(2);
});

/**
 * 2026-08-21 (demande fondateur) : à longueur égale, l'entrée dont la stratégie est la PLUS STRICTE
 * doit gagner, pour qu'un alias insensible à la casse ne vole pas un terme dont la casse est exacte.
 * Bug mesuré en prod avant le correctif : « xAI » (entreprise) était lié vers /glossaire/ia-explicable
 * parce que l'alias « XAI » (loose, IA explicable) précédait l'entrée « xAI » (case_sensitive) ;
 * même motif pour « IA » (alias loose vers /glossaire/autonomie-ia).
 */
function glxDomFromHtml(string $html): array
{
    $dom = new \DOMDocument;
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8"?><div id="glx-root">'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    return [$dom, $dom->getElementById('glx-root')];
}

/** Trie comme loadTerms() : longueur DESC, puis spécificité de stratégie (strict avant tolérant). */
function glxSortLikeProduction(array $terms): array
{
    $rank = static fn (array $t): int => match ($t['match_strategy'] ?? 'loose') {
        'case_sensitive', 'exact_phrase' => 0,
        'partial_case_sensitive' => 1,
        default => 2,
    };
    usort($terms, fn ($a, $b) => (mb_strlen($b['name']) <=> mb_strlen($a['name'])) ?: ($rank($a) <=> $rank($b)));

    return $terms;
}

it('préfère le terme à casse exacte plutôt que l\'alias insensible à la casse (xAI ≠ XAI)', function () {
    // Ordre d'entrée VOLONTAIREMENT défavorable : l'alias loose arrive en premier, comme en prod.
    $terms = glxSortLikeProduction([
        ['name' => 'XAI', 'slug' => 'ia-explicable', 'definition' => 'IA explicable', 'type' => 'glossary', 'url' => '/glossaire/ia-explicable', 'match_strategy' => 'loose'],
        ['name' => 'xAI', 'slug' => 'xai', 'definition' => 'Entreprise d\'Elon Musk', 'type' => 'glossary', 'url' => '/glossaire/xai', 'match_strategy' => 'case_sensitive'],
    ]);

    [$dom, $root] = glxDomFromHtml('<p>Le litige oppose xAI au Minnesota.</p>');
    glxWalk($dom, $root, $terms, false, 10);

    $html = $dom->saveHTML($root);
    expect($html)->toContain('/glossaire/xai')
        ->and($html)->not->toContain('/glossaire/ia-explicable');
});

it('laisse l\'alias insensible à la casse gagner quand la casse ne correspond à aucun terme strict', function () {
    // « xai » tout en minuscules : l'entrée case_sensitive « xAI » ne matche pas, le repli loose doit jouer.
    $terms = glxSortLikeProduction([
        ['name' => 'XAI', 'slug' => 'ia-explicable', 'definition' => 'IA explicable', 'type' => 'glossary', 'url' => '/glossaire/ia-explicable', 'match_strategy' => 'loose'],
        ['name' => 'xAI', 'slug' => 'xai', 'definition' => 'Entreprise d\'Elon Musk', 'type' => 'glossary', 'url' => '/glossaire/xai', 'match_strategy' => 'case_sensitive'],
    ]);

    [$dom, $root] = glxDomFromHtml('<p>On parle beaucoup de xai en ce moment.</p>');
    glxWalk($dom, $root, $terms, false, 10);

    expect($dom->saveHTML($root))->toContain('/glossaire/ia-explicable');
});

it('trie les termes de même longueur par spécificité de stratégie décroissante', function () {
    $sorted = glxSortLikeProduction([
        ['name' => 'ABC', 'slug' => 'loose-one', 'match_strategy' => 'loose'],
        ['name' => 'ABC', 'slug' => 'partial-one', 'match_strategy' => 'partial_case_sensitive'],
        ['name' => 'ABC', 'slug' => 'strict-one', 'match_strategy' => 'case_sensitive'],
        ['name' => 'ABCDEF', 'slug' => 'longest-one', 'match_strategy' => 'loose'],
    ]);

    expect(array_column($sorted, 'slug'))->toBe(['longest-one', 'strict-one', 'partial-one', 'loose-one']);
});
