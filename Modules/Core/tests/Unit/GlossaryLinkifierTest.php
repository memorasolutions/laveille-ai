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

/**
 * 2026-08-21 : 3e critère de tri - à longueur ET stratégie égales, le NOM PRINCIPAL d'une fiche bat
 * un alias curé, qui bat un alias auto-dérivé. Mesuré sur la production : ce seul critère résout 7
 * des 11 collisions restantes (ex. « Modèle multimodal », nom principal de modele-multimodal, était
 * capté par ia-multimodale où ce n'est qu'un alias).
 */
function glxSortWithOrigin(array $terms): array
{
    $strategyRank = static fn (array $t): int => match ($t['match_strategy'] ?? 'loose') {
        'case_sensitive', 'exact_phrase' => 0,
        'partial_case_sensitive' => 1,
        default => 2,
    };

    usort($terms, function ($a, $b) use ($strategyRank) {
        return (mb_strlen($b['name']) <=> mb_strlen($a['name']))
            ?: ($strategyRank($a) <=> $strategyRank($b))
            ?: (($a['origin_rank'] ?? GlossaryLinkifier::ORIGIN_DERIVED_ALIAS) <=> ($b['origin_rank'] ?? GlossaryLinkifier::ORIGIN_DERIVED_ALIAS));
    });

    return $terms;
}

it('fait gagner le nom principal d\'une fiche contre l\'alias d\'une autre fiche', function () {
    // Ordre d'entrée défavorable : l'alias arrive en premier, comme en production.
    $sorted = glxSortWithOrigin([
        ['name' => 'Modèle multimodal', 'slug' => 'ia-multimodale', 'match_strategy' => 'loose', 'origin_rank' => GlossaryLinkifier::ORIGIN_CURATED_ALIAS],
        ['name' => 'Modèle multimodal', 'slug' => 'modele-multimodal', 'match_strategy' => 'loose', 'origin_rank' => GlossaryLinkifier::ORIGIN_PRIMARY],
    ]);

    expect($sorted[0]['slug'])->toBe('modele-multimodal');
});

it('classe les origines dans l\'ordre : nom principal, alias curé, alias auto-dérivé', function () {
    $sorted = glxSortWithOrigin([
        ['name' => 'Terme', 'slug' => 'derive', 'match_strategy' => 'loose', 'origin_rank' => GlossaryLinkifier::ORIGIN_DERIVED_ALIAS],
        ['name' => 'Terme', 'slug' => 'principal', 'match_strategy' => 'loose', 'origin_rank' => GlossaryLinkifier::ORIGIN_PRIMARY],
        ['name' => 'Terme', 'slug' => 'cure', 'match_strategy' => 'loose', 'origin_rank' => GlossaryLinkifier::ORIGIN_CURATED_ALIAS],
    ]);

    expect(array_column($sorted, 'slug'))->toBe(['principal', 'cure', 'derive']);
});

it('garde la spécificité de stratégie PRIORITAIRE sur l\'origine', function () {
    // Une entrée stricte (même si c'est un alias) doit rester devant une entrée tolérante : c'est
    // elle qui porte la casse exacte, et elle ne matche rien d'autre (cas xAI, corrigé plus haut).
    $sorted = glxSortWithOrigin([
        ['name' => 'ABC', 'slug' => 'loose-principal', 'match_strategy' => 'loose', 'origin_rank' => GlossaryLinkifier::ORIGIN_PRIMARY],
        ['name' => 'ABC', 'slug' => 'strict-alias', 'match_strategy' => 'case_sensitive', 'origin_rank' => GlossaryLinkifier::ORIGIN_CURATED_ALIAS],
    ]);

    expect($sorted[0]['slug'])->toBe('strict-alias');
});

// 2026-08-23 : un qualifier qui nomme le FABRICANT est un désambiguïsateur, jamais un synonyme.
// Défaut mesuré en production : « Gemini (Google) » promouvait « Google » en alias, si bien que
// chaque mention de l'entreprise renvoyait vers la fiche du modèle Gemini (6 faux liens sur une
// seule actualité). Trois autres termes portaient le même défaut.
it('ne promeut pas un qualifier qui nomme le fabricant', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('Gemini (Google)'))
        ->toBe(['Gemini']);

    expect(GlossaryLinkifier::extractQualifierAliases('Claude (Anthropic)'))
        ->toBe(['Claude']);

    expect(GlossaryLinkifier::extractQualifierAliases('Llama (Meta)'))
        ->toBe(['Llama']);
});

// La contrepartie : un acronyme TECHNIQUE en qualifier reste un vrai synonyme du terme, et doit
// continuer d'être promu. C'est ce qui distingue « (CNN) » de « (Google) ».
it('promeut toujours un qualifier qui est un acronyme technique', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('Réseau convolutif (CNN)'))
        ->toBe(['Réseau convolutif', 'CNN']);

    expect(GlossaryLinkifier::extractQualifierAliases('Raisonnement outillé (ReAct)'))
        ->toBe(['Raisonnement outillé', 'ReAct']);
});

// Homographe assumé : « XAI » abrège *eXplainable AI* et reste un synonyme légitime, alors que
// l'entreprise « xAI » porte la même chaîne. Ce test verrouille le fait que la correction
// ci-dessus n'a PAS sacrifié le sens technique pour réparer le sens commercial.
it('conserve XAI comme synonyme technique malgre l entreprise homographe', function () {
    expect(GlossaryLinkifier::extractQualifierAliases('Explicabilité (XAI)'))
        ->toBe(['Explicabilité', 'XAI']);
});

/**
 * 2026-08-26, trouve EN PRODUCTION par le controle des auto-liens qui suit une publication.
 *
 * Le terme de glossaire « Gemini 3 » matchait a l'interieur de « Gemini 3.5 Transcribe », rendant
 * `<a>Gemini 3</a>.5 Transcribe` - un nom de produit coupe en deux, dont l'infobulle decrivait un
 * AUTRE modele (« Gemini 3, modele phare, contexte 2M tokens »). La frontiere de fin etait
 * `(?![\p{L}\p{N}])` : un point n'etant ni lettre ni chiffre, elle laissait passer « .5 ».
 *
 * Le defaut ne touchait pas une fiche mais TOUTE page mentionnant un numero de version, ce qui en
 * fait un correctif de composant, pas une correction de contenu.
 */
function glxTermeVersionne(string $nom, string $slug): array
{
    return [['name' => $nom, 'slug' => $slug, 'definition' => 'Test',
             'type' => 'glossary', 'url' => '/glossaire/'.$slug, 'match_strategy' => 'loose']];
}

it('ne coupe pas un numero de version en deux (Gemini 3 dans Gemini 3.5 Transcribe)', function () {
    [$dom, $root] = glxDomFromHtml('<p>Google a presente Gemini 3.5 Transcribe, son modele vocal.</p>');

    $liens = glxWalk($dom, $root, glxTermeVersionne('Gemini 3', 'gemini-google'), false, 1);

    expect($liens)->toBe(0);
    expect(str_contains($dom->saveHTML(), '</a>.5'))->toBeFalse(
        'Un nom de produit versionne ne doit jamais etre coupe par un lien de glossaire.'
    );
});

// Non-regression : c'est bien le POINT SUIVI D'UN CHIFFRE qui bloque, pas le point tout court.
it('lie encore le terme quand le point est une fin de phrase', function () {
    [$dom, $root] = glxDomFromHtml('<p>Cette equipe utilise Gemini 3. La suite arrive bientot.</p>');

    expect(glxWalk($dom, $root, glxTermeVersionne('Gemini 3', 'gemini-google'), false, 1))->toBe(1);
});

it('lie encore le terme en plein milieu de phrase', function () {
    [$dom, $root] = glxDomFromHtml('<p>Le modele Gemini 3 est multimodal et rapide.</p>');

    expect(glxWalk($dom, $root, glxTermeVersionne('Gemini 3', 'gemini-google'), false, 1))->toBe(1);
});

// Le meme piege vaut pour tout terme finissant par un chiffre : GPT-4 ne doit pas voler GPT-4.1.
it('ne coupe pas non plus un terme du type GPT-4 dans GPT-4.1', function () {
    [$dom, $root] = glxDomFromHtml('<p>Le passage a GPT-4.1 a change la donne.</p>');

    expect(glxWalk($dom, $root, glxTermeVersionne('GPT-4', 'gpt-4'), false, 1))->toBe(0);
});

/**
 * 2026-08-26 - defaut MESURE sur une fiche publiee le jour meme : le corps contenait
 * « intelligence artificielle generale », mais l'auto-lien n'attrapait que la sous-chaine
 * « intelligence artificielle » et envoyait le lecteur vers la fiche GENERIQUE /glossaire/ia.
 * Le sigle « AGI » etait lie sept fois vers la bonne page, l'expression francaise developpee
 * zero fois - le lecteur qui rencontrait le terme en toutes lettres etait le seul a ne pas
 * recevoir la definition precise.
 *
 * La migration 2026_08_26_330000_add_agi_aliases pose l'alias et AFFIRME dans son docblock que
 * le tri par longueur decroissante reglera l'arbitrage sans regle de priorite supplementaire.
 * Ce test PROUVE cette affirmation au lieu de la supposer : si l'hypothese etait fausse, la
 * migration poserait un alias inoperant et personne ne s'en apercevrait.
 */
it('fait gagner l expression longue sur la sous-chaine generique (AGI vs IA)', function () {
    // Ordre d'entree VOLONTAIREMENT defavorable : le terme generique arrive en premier.
    $terms = glxSortLikeProduction([
        ['name' => 'intelligence artificielle', 'slug' => 'ia', 'definition' => 'IA',
         'type' => 'glossary', 'url' => '/glossaire/ia', 'match_strategy' => 'loose'],
        ['name' => 'intelligence artificielle générale', 'slug' => 'agi', 'definition' => 'AGI',
         'type' => 'glossary', 'url' => '/glossaire/agi', 'match_strategy' => 'loose'],
    ]);

    [$dom, $root] = glxDomFromHtml("<p>OpenAI n'est pas encore arrivee a l'intelligence artificielle générale.</p>");
    glxWalk($dom, $root, $terms, false, 1);
    $rendu = $dom->saveHTML();

    expect(str_contains($rendu, '/glossaire/agi'))->toBeTrue(
        "L'expression developpee doit mener a la fiche AGI, jamais a la fiche generique IA."
    );
    expect(str_contains($rendu, '/glossaire/ia"'))->toBeFalse(
        'La sous-chaine generique ne doit pas voler le lien a l expression plus precise.'
    );
});

// Non-regression : hors de l'expression complete, « intelligence artificielle » garde son lien.
it('laisse le terme generique lier quand l expression longue est absente', function () {
    $terms = glxSortLikeProduction([
        ['name' => 'intelligence artificielle', 'slug' => 'ia', 'definition' => 'IA',
         'type' => 'glossary', 'url' => '/glossaire/ia', 'match_strategy' => 'loose'],
        ['name' => 'intelligence artificielle générale', 'slug' => 'agi', 'definition' => 'AGI',
         'type' => 'glossary', 'url' => '/glossaire/agi', 'match_strategy' => 'loose'],
    ]);

    [$dom, $root] = glxDomFromHtml('<p>Un cours sur intelligence artificielle pour debutants.</p>');
    glxWalk($dom, $root, $terms, false, 1);

    expect(str_contains($dom->saveHTML(), '/glossaire/ia'))->toBeTrue();
});

/**
 * 2026-08-27 : la frontiere de mot ne bornait que lettres et chiffres, si bien qu'un point, un
 * underscore, un tiret ou une barre oblique ne separaient rien. Des liens etaient poses A
 * L'INTERIEUR de « DeepLearning.AI », « aistudio.google.com » et « pollen-robotics/microduck_rl ».
 *
 * Correctif applique aux DEUX variantes de construction du motif (preg_quote ET
 * buildPartialCasePattern), sinon la moitie des termes garde l'ancien comportement.
 *
 * Piege delibere EVITE : ajouter « . » symetriquement des deux cotes casserait les termes du
 * glossaire qui CONTIENNENT eux-memes un point en fin de phrase (Node.js, Z.ai, jan.ai). D'ou
 * l'asymetrie : le lookahead ne refuse un point que s'il est SUIVI d'un caractere de mot (\.\w),
 * jamais un point isole ou de fin de phrase.
 */
function glxTerme(string $nom, string $slug, string $strategy = 'loose'): array
{
    return [['name' => $nom, 'slug' => $slug, 'definition' => 'Test',
             'type' => 'glossary', 'url' => '/glossaire/'.$slug, 'match_strategy' => $strategy]];
}

// === Cas qui DOIVENT rester NON LIES ===

it('ne lie pas AI a l interieur de DeepLearning.AI (variante partial_case_sensitive)', function () {
    [$dom, $root] = glxDomFromHtml('<p>Le cours DeepLearning.AI est tres suivi.</p>');

    $liens = glxWalk($dom, $root, glxTerme('AI', 'ai', 'partial_case_sensitive'), false, 10);

    expect($liens)->toBe(0);
    expect(str_contains($dom->saveHTML(), 'glossary-link'))->toBeFalse();
});

it('ne lie pas google a l interieur de aistudio.google.com', function () {
    [$dom, $root] = glxDomFromHtml('<p>Le service aistudio.google.com propose un essai.</p>');

    $liens = glxWalk($dom, $root, glxTerme('Google', 'google'), false, 10);

    expect($liens)->toBe(0);
    expect(str_contains($dom->saveHTML(), 'glossary-link'))->toBeFalse();
});

it('ne lie pas rl a l interieur de pollen-robotics/microduck_rl', function () {
    [$dom, $root] = glxDomFromHtml('<p>Le modele pollen-robotics/microduck_rl a ete presente.</p>');

    $liens = glxWalk($dom, $root, glxTerme('RL', 'rl'), false, 10);

    expect($liens)->toBe(0);
    expect(str_contains($dom->saveHTML(), 'glossary-link'))->toBeFalse();
});

it('ne lie pas Anthropic a l interieur du mot francais anthropique', function () {
    [$dom, $root] = glxDomFromHtml('<p>La pression anthropique augmente sur ce territoire.</p>');

    $liens = glxWalk($dom, $root, glxTerme('Anthropic', 'anthropic'), false, 10);

    expect($liens)->toBe(0);
    expect(str_contains($dom->saveHTML(), 'glossary-link'))->toBeFalse();
});

// === Cas qui DOIVENT rester LIES (non-regression de l'asymetrie du point) ===

it('lie toujours Node.js en milieu de phrase ET en fin de phrase', function () {
    [$domMilieu, $rootMilieu] = glxDomFromHtml('<p>Ce service tourne sous Node.js et repond vite.</p>');
    $liensMilieu = glxWalk($domMilieu, $rootMilieu, glxTerme('Node.js', 'nodejs'), false, 10);

    [$domFin, $rootFin] = glxDomFromHtml('<p>Cette API tourne sous Node.js.</p>');
    $liensFin = glxWalk($domFin, $rootFin, glxTerme('Node.js', 'nodejs'), false, 10);

    expect($liensMilieu)->toBe(1);
    expect(str_contains($domMilieu->saveHTML(), '/glossaire/nodejs'))->toBeTrue();
    expect($liensFin)->toBe(1);
    expect(str_contains($domFin->saveHTML(), '/glossaire/nodejs'))->toBeTrue();
});

it('lie toujours jan.ai', function () {
    [$dom, $root] = glxDomFromHtml("<p>L'assistant jan.ai fonctionne en local.</p>");

    $liens = glxWalk($dom, $root, glxTerme('jan.ai', 'jan-ai'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/glossaire/jan-ai'))->toBeTrue();
});

it('lie toujours Z.ai en fin de phrase', function () {
    [$dom, $root] = glxDomFromHtml("<p>Cette entreprise s'appelle Z.ai.</p>");

    $liens = glxWalk($dom, $root, glxTerme('Z.ai', 'z-ai'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/glossaire/z-ai'))->toBeTrue();
});

it('lie toujours IA dans une phrase ordinaire', function () {
    [$dom, $root] = glxDomFromHtml("<p>L'IA transforme profondement notre quotidien.</p>");

    $liens = glxWalk($dom, $root, glxTerme('IA', 'ia'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/glossaire/ia'))->toBeTrue();
});

it('lie Anthropic Claude en entier, jamais coupe', function () {
    [$dom, $root] = glxDomFromHtml('<p>Le nouveau modele Anthropic Claude est disponible.</p>');

    $liens = glxWalk($dom, $root, glxTerme('Anthropic Claude', 'anthropic-claude'), false, 10);
    $html = $dom->saveHTML($root);

    expect($liens)->toBe(1);
    expect(str_contains($html, '>Anthropic Claude</a>'))->toBeTrue(
        'Le lien doit envelopper la phrase complete, jamais un fragment coupe.'
    );
    expect(str_contains($html, '>Anthropic</a>'))->toBeFalse();
});

/**
 * 2026-08-31 (mandat #2091) : garde SUFFIXE - GlossaryLinkifier::TOOL_SUFFIX_SAFE_MODIFIERS /
 * buildToolSuffixGuard(). Deux faux rattachements mesures en production (CHANGELOG v1.242.11) :
 * l'outil « Clark » detecte dans le nom propre « Clark Wiethorn » (agent du FBI), l'outil
 * « Ghost » detecte dans le nom de code « Ghost Murmur ». Le nom d'outil est le PREMIER mot
 * d'un nom propre compose sans rapport avec l'outil - symetrique de TOOL_COMPOUND_EXCLUSIONS
 * (prefixe fautif AVANT le nom), mais cette fois le parasite SUIT.
 *
 * Portee volontairement limitee a type='tool' (voir docblock de la constante) : contrairement au
 * cas Composer/TOOL_COMPOUND_EXCLUSIONS (qui depend de loadTerms(), donc teste uniquement via
 * l'API publique dans le module News), cette garde ne depend d'AUCUNE configuration chargee
 * depuis la base - seul $term['type'] pilote son activation. La reflexion bas niveau de ce
 * fichier reste donc un test valide et rapide pour le mecanisme lui-meme ; la preuve de bout en
 * bout (vrais modeles Tool + artisan news:backfill-auto-tools) vit dans
 * Modules/News/tests/Feature/ToolNameProperNounSuffixTest.php.
 */
function glxTermeOutil(string $nom, string $slug): array
{
    return [['name' => $nom, 'slug' => $slug, 'definition' => 'Test',
             'type' => 'tool', 'url' => '/annuaire/'.$slug, 'match_strategy' => 'case_sensitive']];
}

// === Les 2 cas REELS mesures en production : DOIVENT rester NON LIES ===

it('ne lie pas Clark a l interieur du nom propre Clark Wiethorn', function () {
    [$dom, $root] = glxDomFromHtml("<p>L'agent du FBI Clark Wiethorn a confirme l'information.</p>");

    $liens = glxWalk($dom, $root, glxTermeOutil('Clark', 'clark'), false, 10);

    expect($liens)->toBe(0);
    expect(str_contains($dom->saveHTML(), 'glossary-link'))->toBeFalse();
});

it('ne lie pas Ghost a l interieur du nom de code Ghost Murmur', function () {
    [$dom, $root] = glxDomFromHtml('<p>Le programme, nom de code Ghost Murmur, a ete revele hier.</p>');

    $liens = glxWalk($dom, $root, glxTermeOutil('Ghost', 'ghost'), false, 10);

    expect($liens)->toBe(0);
    expect(str_contains($dom->saveHTML(), 'glossary-link'))->toBeFalse();
});

// === Non-regression : la mention SEULE reste legitime (meme outil, texte different) ===

it('lie toujours Clark quand il est mentionne seul', function () {
    [$dom, $root] = glxDomFromHtml('<p>Nous utilisons Clark pour automatiser nos taches.</p>');

    $liens = glxWalk($dom, $root, glxTermeOutil('Clark', 'clark'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/annuaire/clark'))->toBeTrue();
});

it('lie toujours Ghost en fin de phrase', function () {
    [$dom, $root] = glxDomFromHtml('<p>Le meilleur outil de suivi de prix reste Ghost.</p>');

    $liens = glxWalk($dom, $root, glxTermeOutil('Ghost', 'ghost'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/annuaire/ghost'))->toBeTrue();
});

// === Cas legitimes du mandat : DOIVENT continuer de lier (modificateur de produit connu) ===

it('lie ChatGPT dans le nom compose legitime ChatGPT Plus', function () {
    [$dom, $root] = glxDomFromHtml('<p>Les abonnes ChatGPT Plus profitent des derniers modeles.</p>');

    $liens = glxWalk($dom, $root, glxTermeOutil('ChatGPT', 'chatgpt'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/annuaire/chatgpt'))->toBeTrue();
});

it('lie Claude dans le nom compose legitime Claude Code', function () {
    [$dom, $root] = glxDomFromHtml('<p>Claude Code a ete mis a jour cette semaine.</p>');

    $liens = glxWalk($dom, $root, glxTermeOutil('Claude', 'claude'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/annuaire/claude'))->toBeTrue();
});

it('lie Gemini dans le nom compose legitime Gemini Pro', function () {
    [$dom, $root] = glxDomFromHtml('<p>Gemini Pro surpasse les benchmarks precedents.</p>');

    $liens = glxWalk($dom, $root, glxTermeOutil('Gemini', 'gemini'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/annuaire/gemini'))->toBeTrue();
});

it('lie Mistral dans le nom compose legitime Mistral Large', function () {
    [$dom, $root] = glxDomFromHtml('<p>Mistral Large impressionne sur les taches de code.</p>');

    $liens = glxWalk($dom, $root, glxTermeOutil('Mistral', 'mistral'), false, 10);

    expect($liens)->toBe(1);
    expect(str_contains($dom->saveHTML(), '/annuaire/mistral'))->toBeTrue();
});

// === Portee : le glossaire (type != 'tool') n'est PAS soumis a cette garde ===

it('la garde suffixe ne touche pas un terme de glossaire suivi d un mot majuscule', function () {
    // « Mistral » existe reellement comme fiche de glossaire (l'editeur) ; son alias « Mistral
    // AI » doit continuer de fonctionner meme si « AI » est absent de TOOL_SUFFIX_SAFE_MODIFIERS
    // pour un mot qui ne serait PAS un modificateur connu - la garde ne s'applique qu'aux outils.
    [$dom, $root] = glxDomFromHtml('<p>Mistral Zephyr est une conference organisee par l editeur.</p>');

    $liens = glxWalk($dom, $root, glxTerme('Mistral', 'mistral'), false, 10);

    expect($liens)->toBe(1, 'Un terme de type glossaire ne doit jamais etre bloque par la garde suffixe des outils.');
});
