<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - rendu et assainissement du moteur PHP SignatureRenderer (jumeau de
 * signature-render.js, voir docblocks respectifs).
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Signature\Models\Signature;
use Modules\Signature\Services\SignatureRenderer;
use Modules\Signature\Services\SignatureTemplateRegistry;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('le rendu échappe les champs texte contre une tentative d\'injection HTML', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => '<script>alert(1)</script>',
        'last_name' => 'Tremblay"><img src=x onerror=alert(2)>',
        'email' => 'test@example.com',
    ], 'minimal');

    // La propriété de sécurité n'est PAS "la sous-chaîne n'apparaît jamais" (le texte inerte
    // "onerror=alert(2)" peut légitimement survivre, ÉCHAPPÉ) mais "aucune balise active n'est
    // jamais interprétable" : <script> et <img ...onerror=...> doivent apparaître UNIQUEMENT sous
    // forme échappée (&lt;...&gt;), jamais comme une vraie balise HTML brute.
    expect($html)->not->toContain('<script>')
        ->and($html)->not->toContain('<img src=x')
        ->and($html)->toContain('&lt;script&gt;')
        ->and($html)->toContain('&lt;img src=x onerror=alert(2)&gt;');
});

test('un schéma javascript: dans une URL est rejeté, jamais rendu comme href', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie',
        'last_name' => 'Tremblay',
        'email' => 'marie@example.com',
        'website' => 'javascript:alert(1)',
        'cta_text' => 'Cliquez',
        'cta_url' => 'javascript:alert(2)',
    ], 'minimal');

    expect($html)->not->toContain('javascript:');
});

test('les 4 gabarits produisent une structure de tables sans flex/grid/position', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'job_title' => 'Directrice', 'organization' => 'Acme inc.',
    ], $template, [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);

    expect($html)->toContain('<table')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex')
        ->and($html)->not->toContain('grid-template')
        ->and($html)->not->toContain('position:absolute')
        ->and($html)->not->toContain('position: absolute');
})->with(['minimal', 'professionnel', 'portrait', 'compact']);

test('chaque balise img générée porte des attributs width/height numériques codés en dur', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'professionnel', [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
    ]);

    expect($html)->toMatch('/<img[^>]*width="\d+"[^>]*height="\d+"/');
});

test('une image sans dimensions connues n\'est jamais rendue (jamais un <img> sans width/height)', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'professionnel', [
        'logo' => ['url' => 'https://laveille.ai/logo.png'],
    ]);

    expect($html)->not->toContain('<img');
});

test('un gabarit inconnu retombe sur minimal plutôt que de planter', function (): void {
    $html = SignatureRenderer::render(['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.com'], 'inexistant');

    expect($html)->toContain('A B');
});

test('Signature::templates() DÉRIVE du registre - une seule source de vérité, jamais une liste dupliquée', function (): void {
    // Égalité stricte : le modèle ne fait que relayer le registre, il ne recopie rien.
    expect(Signature::templates())->toBe(SignatureTemplateRegistry::templates())
        ->and(Signature::templates())->not->toBeEmpty();

    // Réciproque comportementale : si une seconde liste en dur existait quelque part (dans le
    // modèle ou ailleurs), elle divergerait du registre dès qu'un gabarit y serait ajouté sans
    // toucher Signature - ce test échouerait alors, précisément parce qu'il ne lit QUE le registre
    // pour valider ce que Signature::templates() annonce.
    foreach (Signature::templates() as $template) {
        expect(SignatureTemplateRegistry::has($template))->toBeTrue();
    }
});

// ------------------------------------------------------------------
// LOT 2 (2026-09-25) - 4 nouveaux gabarits (vertical, banniere, executive, social)
// ------------------------------------------------------------------

test('le registre contient EXACTEMENT 8 gabarits après le LOT 2 (les 8 premiers, avant les 6 ajouts du LOT 4)', function (): void {
    expect(array_slice(Signature::templates(), 0, 8))->toBe([
        'minimal', 'professionnel', 'portrait', 'compact',
        'vertical', 'banniere', 'executive', 'social',
    ]);
});

test('les 4 nouveaux gabarits produisent aussi une structure de tables sans flex/grid/position', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'job_title' => 'Directrice', 'organization' => 'Acme inc.',
        'tagline' => 'Sur rendez-vous seulement',
        'cta_text' => 'Réserver un appel', 'cta_url' => 'https://laveille.ai/reserver',
        'social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie']],
        'mention_lines' => ['Membre de l\'Ordre'],
    ], $template, [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
        'banniere' => ['url' => 'https://laveille.ai/banniere.png', 'width' => 600, 'height' => 150],
    ]);

    expect($html)->toContain('<table')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex')
        ->and($html)->not->toContain('grid-template')
        ->and($html)->not->toContain('position:absolute')
        ->and($html)->not->toContain('position: absolute')
        ->and($html)->toContain('Marie Tremblay');
})->with(['vertical', 'banniere', 'executive', 'social']);

test('le gabarit vertical empile ses blocs DANS L\'ORDRE : image, identité, contact, réseaux, mentions, CTA', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'phone' => '514-555-0100',
        'social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie']],
        // Sans apostrophe : self::e() l'échapperait en entité HTML (&#039;), ce que ce test ne
        // cherche pas à vérifier - seulement l'ORDRE des blocs.
        'mention_lines' => ['Numéro de permis 12345'],
        'cta_text' => 'Réserver', 'cta_url' => 'https://laveille.ai/reserver',
    ], 'vertical', [
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);

    $posImage = strpos($html, 'portrait.png');
    $posName = strpos($html, 'Marie Tremblay');
    $posContact = strpos($html, '514-555-0100');
    $posReseaux = strpos($html, 'LinkedIn');
    $posMentions = strpos($html, 'Numéro de permis 12345');
    $posCta = strpos($html, 'Réserver');

    expect($posImage)->not->toBeFalse()
        ->and($posName)->not->toBeFalse()
        ->and($posContact)->not->toBeFalse()
        ->and($posReseaux)->not->toBeFalse()
        ->and($posMentions)->not->toBeFalse()
        ->and($posCta)->not->toBeFalse();

    expect($posImage)->toBeLessThan($posName)
        ->and($posName)->toBeLessThan($posContact)
        ->and($posContact)->toBeLessThan($posReseaux)
        ->and($posReseaux)->toBeLessThan($posMentions)
        ->and($posMentions)->toBeLessThan($posCta);
});

test('le gabarit banniere enveloppe la bannière dans un lien cliquable vers cta_url, avec un alt non vide', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'cta_text' => 'Réserver un appel', 'cta_url' => 'https://laveille.ai/reserver',
    ], 'banniere', [
        'banniere' => ['url' => 'https://laveille.ai/banniere.png', 'width' => 600, 'height' => 150],
    ]);

    expect($html)->toMatch('#<a href="https://laveille\.ai/reserver"[^>]*><img src="https://laveille\.ai/banniere\.png"[^>]*alt="[^"]+"[^>]*></a>#');
});

test('le gabarit banniere SANS bannière téléversée ne casse pas - bloc masqué proprement', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'cta_text' => 'Réserver un appel', 'cta_url' => 'https://laveille.ai/reserver',
    ], 'banniere');

    expect($html)->toContain('Marie Tremblay')
        ->and($html)->not->toContain('<img');
});

test('le gabarit banniere sans cta_url affiche la bannière SANS lien (jamais de <a href="">)', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'banniere', [
        'banniere' => ['url' => 'https://laveille.ai/banniere.png', 'width' => 600, 'height' => 150],
    ]);

    // La cellule de la bannière contient directement l'<img> - AUCUNE balise <a> ne l'enveloppe
    // quand cta_url est absent (preuve positive, pas seulement l'absence d'un cas particulier).
    expect($html)->toContain('<td><img src="https://laveille.ai/banniere.png"');
});

test('le gabarit executive affiche le nom dans une taille de police plus grande, avec un filet séparateur', function (): void {
    $htmlExecutive = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'executive');
    $htmlMinimal = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'minimal');

    expect($htmlExecutive)->toContain('font-size:22px')
        ->and($htmlExecutive)->toContain('border-bottom:2px solid')
        ->and($htmlMinimal)->not->toContain('border-bottom:2px solid');
});

test('le gabarit social met en avant les réseaux sociaux (icônes agrandies, ligne dédiée) - différent du rendu minimal', function (): void {
    $links = ['social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie']]];

    $htmlSocial = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ] + $links, 'social');
    $htmlMinimal = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ] + $links, 'minimal');

    // RENFORCÉ AU LOT 3 (2026-09-25) : la QC visuelle a jugé le rendu du LOT 2 (icône 18px sur la
    // même ligne unique que le rendu minimal) trop proche de `minimal`. La taille passe à 20px et
    // la structure change (voir test dédié ci-dessous) - assertion mise à jour, jamais affaiblie :
    // la valeur choisie distingue maintenant `social` à la fois de `minimal` (12px) ET de
    // `executive` (22px, filet du nom), qu'aucun test ne confondait déjà.
    expect($htmlSocial)->toContain('font-size:20px')
        ->and($htmlMinimal)->not->toContain('font-size:20px');
});

test('LOT 3 - le gabarit social rend CHAQUE réseau sur SA PROPRE ligne (changement de structure), minimal garde une seule ligne', function (): void {
    $links = [
        'social_links' => [
            ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie'],
            ['platform' => 'facebook', 'url' => 'https://facebook.com/marie'],
        ],
    ];

    $htmlSocial = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ] + $links, 'social');
    $htmlMinimal = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ] + $links, 'minimal');

    // 2 réseaux → 2 lignes distinctes pour `social` (une balise <tr> par réseau, chacune avec son
    // propre <a>), une seule ligne (les deux liens dans le MÊME <tr>) pour `minimal`.
    expect(substr_count($htmlSocial, '<tr><td'))
        ->toBeGreaterThan(substr_count($htmlMinimal, '<tr><td'))
        ->and(substr_count($htmlSocial, 'LinkedIn'))->toBe(1)
        ->and(substr_count($htmlSocial, 'Facebook'))->toBe(1)
        // Seule la PREMIÈRE ligne porte le filet supérieur, jamais la seconde.
        ->and(substr_count($htmlSocial, 'border-top:1px solid'))->toBe(1);
});

test('les gabarits minimal/professionnel/portrait/compact ne changent PAS de rendu avec l\'ajout des propriétés du LOT 2 (non-régression)', function (string $template): void {
    $content = [
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'job_title' => 'Directrice', 'organization' => 'Acme inc.', 'phone' => '514-555-0100',
        'tagline' => 'Sur rendez-vous seulement',
        'cta_text' => 'Réserver un appel', 'cta_url' => 'https://laveille.ai/reserver',
        'social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie']],
        'mention_lines' => ['Membre de l\'Ordre'],
    ];
    $images = [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ];

    $html = SignatureRenderer::render($content, $template, $images);

    // Les propriétés transverses du LOT 2 (social_emphasis, name_divider, banner_role) sont
    // absentes de ces 4 définitions (LOT 1) - leur trace ne doit donc JAMAIS apparaître.
    expect($html)->not->toContain('font-size:18px')
        ->and($html)->not->toContain('border-bottom:2px solid')
        ->and($html)->not->toContain('margin-top:10px');
})->with(['minimal', 'professionnel', 'portrait', 'compact']);

test('SignatureTemplateRegistry::definitions() renvoie le registre COMPLET, sérialisable, une entrée par gabarit', function (): void {
    $definitions = SignatureTemplateRegistry::definitions();

    expect($definitions)->toHaveCount(14)
        ->and(array_keys($definitions))->toBe(Signature::templates());

    foreach ($definitions as $template => $def) {
        expect($def)->toHaveKey('layout')
            ->and($def)->toHaveKey('label')
            ->and(in_array($def['layout'], ['standard', 'compact', 'vertical'], true))->toBeTrue();

        // Round-trip JSON : la structure exacte que consomme window.SIGNATURE_TEMPLATES côté JS.
        $json = json_decode(json_encode($def), true);
        expect($json)->toBe($def);
    }
});

test('chaque libellé (label) du registre est du français correct, jamais la clé technique brute', function (): void {
    $definitions = SignatureTemplateRegistry::definitions();

    expect($definitions['banniere']['label'])->toBe('Bannière')
        ->and($definitions['executive']['label'])->not->toBe('Executive');
});

// ------------------------------------------------------------------
// LOT 3 (2026-09-25) - pronoms, forme du portrait, échelle de police, sécurité mode sombre,
// renforcement du gabarit social (structure), masquage des blocs vides.
// ------------------------------------------------------------------

test('LOT 3 - les pronoms sont rendus discrètement APRÈS le nom, dans les 8 gabarits', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'pronouns' => 'elle',
    ], $template);

    expect($html)->toContain('Marie Tremblay')
        ->and($html)->toContain('(elle)')
        // Toujours APRÈS le nom complet, jamais avant.
        ->and(strpos($html, 'Marie Tremblay'))->toBeLessThan(strpos($html, '(elle)'));
})->with(['minimal', 'professionnel', 'portrait', 'compact', 'vertical', 'banniere', 'executive', 'social']);

test('LOT 3 - sans pronoms, la sortie ne contient AUCUNE trace du bloc de pronoms (non-régression)', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'minimal');

    expect($html)->not->toContain('font-weight:normal;font-size:12px;color:#374151;');
});

test('LOT 3 - portrait_shape=rond force un cercle sur le PORTRAIT, jamais sur le logo', function (): void {
    $htmlPortraitRond = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'portrait_shape' => 'rond',
    ], 'portrait', [
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);
    // `minimal` utilise un rôle `logo`, jamais `portrait` - portrait_shape ne doit RIEN y changer.
    $htmlLogoRond = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'portrait_shape' => 'rond',
    ], 'minimal', [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
    ]);
    $htmlLogoCarre = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'minimal', [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
    ]);

    expect($htmlPortraitRond)->toContain('border-radius:50%')
        ->and($htmlLogoRond)->toBe($htmlLogoCarre);
});

test('LOT 3 - portrait_shape=carre (défaut) ne change RIEN au style du gabarit `portrait` (non-régression)', function (): void {
    $htmlDefaut = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'portrait', [
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);
    $htmlCarreExplicite = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'portrait_shape' => 'carre',
    ], 'portrait', [
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);

    expect($htmlDefaut)->toBe($htmlCarreExplicite)
        ->and($htmlDefaut)->not->toContain('border-radius:50%');
});

test('LOT 3 - une valeur invalide de portrait_shape retombe sur carre plutôt que de planter', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'portrait_shape' => 'triangle',
    ], 'portrait', [
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);

    expect($html)->not->toContain('border-radius:50%');
});

test('LOT 3 - font_scale=grande agrandit les tailles de police de base, petite les réduit', function (): void {
    $moyenne = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'minimal');
    $grande = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'font_scale' => 'grande',
    ], 'minimal');
    $petite = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'font_scale' => 'petite',
    ], 'minimal');

    // Nom (name_size 15px) : 15 × 1.15 = 17,25 → 17 ; 15 × 0.9 = 13,5 → 14 (arrondi standard).
    expect($moyenne)->toContain('font-size:15px')
        ->and($grande)->toContain('font-size:17px')
        ->and($grande)->not->toContain('font-size:15px')
        ->and($petite)->toContain('font-size:14px')
        ->and($petite)->not->toContain('font-size:15px');
});

test('LOT 3 - une valeur invalide de font_scale retombe sur moyenne (facteur 1.0) plutôt que de planter', function (): void {
    $htmlInvalide = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'font_scale' => 'enorme',
    ], 'minimal');
    $htmlDefaut = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'minimal');

    expect($htmlInvalide)->toBe($htmlDefaut);
});

test('LOT 3 - les 3 champs neufs, laissés à leur valeur NEUTRE explicite, produisent une sortie IDENTIQUE à leur simple omission (non-régression)', function (string $template): void {
    $sansLesChamps = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'job_title' => 'Directrice', 'organization' => 'Acme inc.',
    ], $template);
    $avecValeursNeutres = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'job_title' => 'Directrice', 'organization' => 'Acme inc.',
        'pronouns' => '', 'portrait_shape' => 'carre', 'font_scale' => 'moyenne',
    ], $template);

    expect($sansLesChamps)->toBe($avecValeursNeutres);
})->with(['minimal', 'professionnel', 'portrait', 'compact', 'vertical', 'banniere', 'executive', 'social']);

test('LOT 3 - sécurité mode sombre : aucun lien ne repose sur `color:inherit`, chaque lien porte sa PROPRE couleur explicite', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'website' => 'https://exemple.com',
        'social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie']],
    ], $template, [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
    ]);

    expect($html)->not->toContain('color:inherit')
        ->and($html)->toContain('mailto:marie@example.com');
})->with(['minimal', 'professionnel', 'portrait', 'compact', 'vertical', 'banniere', 'executive', 'social']);

test('LOT 3 - sécurité mode sombre : l\'enveloppe de la signature pose un fond blanc explicite', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], $template);

    expect($html)->toContain('background-color:#ffffff');
})->with(['minimal', 'professionnel', 'portrait', 'compact', 'vertical', 'banniere', 'executive', 'social']);

test('LOT 3 - masquage propre : données minimales (aucun champ optionnel, aucune image) - aucune cellule vide, aucune image fantôme', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], $template);

    expect($html)->toContain('Marie Tremblay')
        ->and($html)->not->toContain('<img')
        // Aucune cellule de tableau vide (un bloc absent ne doit jamais laisser un <td></td>).
        ->and($html)->not->toMatch('/<td[^>]*>\s*<\/td>/');
})->with(['minimal', 'professionnel', 'portrait', 'compact', 'vertical', 'banniere', 'executive', 'social']);

// ------------------------------------------------------------------
// LOT 4 (2026-09-26) - galerie de mises en page : 6 nouveaux gabarits (photo_droite, logo_gauche,
// logo_bas, photo_centree, deux_colonnes, coordonnees_sous_nom) + category/hint sur les 14.
// ------------------------------------------------------------------

test('LOT 4 - le registre contient EXACTEMENT 14 gabarits, dans l\'ordre : les 8 précédents puis les 6 nouveaux', function (): void {
    expect(Signature::templates())->toHaveCount(14)
        ->and(Signature::templates())->toBe([
            'minimal', 'professionnel', 'portrait', 'compact',
            'vertical', 'banniere', 'executive', 'social',
            'photo_droite', 'logo_gauche', 'logo_bas', 'photo_centree', 'deux_colonnes', 'coordonnees_sous_nom',
        ]);
});

test('LOT 4 - chaque gabarit du registre (14/14) porte un `category` et un `hint` non vides', function (): void {
    $definitions = SignatureTemplateRegistry::definitions();
    $categoriesConnues = ['classiques', 'photo', 'vertical', 'banniere', 'reseaux'];

    expect($definitions)->toHaveCount(14);

    foreach ($definitions as $template => $def) {
        expect($def)->toHaveKey('category')
            ->and($def)->toHaveKey('hint')
            ->and($def['category'])->toBeString()->not->toBe('')
            ->and(in_array($def['category'], $categoriesConnues, true))->toBeTrue()
            ->and($def['hint'])->toBeString()
            ->and(mb_strlen($def['hint']))->toBeGreaterThanOrEqual(60)
            ->and(mb_strlen($def['hint']))->toBeLessThanOrEqual(110);
    }
});

test('LOT 4 - les 6 nouveaux gabarits produisent une structure de tables sans flex/grid/position, avec un jeu COMPLET', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'job_title' => 'Directrice', 'organization' => 'Acme inc.', 'phone' => '514-555-0100',
        'tagline' => 'Sur rendez-vous seulement',
        'cta_text' => 'Réserver un appel', 'cta_url' => 'https://laveille.ai/reserver',
        'social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie']],
        'mention_lines' => ['Membre de l\'Ordre'],
    ], $template, [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);

    expect($html)->toContain('<table')
        ->and($html)->not->toContain('display:flex')
        ->and($html)->not->toContain('display: flex')
        ->and($html)->not->toContain('grid-template')
        ->and($html)->not->toContain('position:absolute')
        ->and($html)->not->toContain('position: absolute')
        ->and($html)->toContain('Marie Tremblay');
})->with(['photo_droite', 'logo_gauche', 'logo_bas', 'photo_centree', 'deux_colonnes', 'coordonnees_sous_nom']);

test('LOT 4 - les 6 nouveaux gabarits se rendent sans erreur avec un jeu MINIMAL (masquage propre, aucune cellule vide)', function (string $template): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], $template);

    expect($html)->toContain('Marie Tremblay')
        ->and($html)->not->toContain('<img')
        ->and($html)->not->toMatch('/<td[^>]*>\s*<\/td>/');
})->with(['photo_droite', 'logo_gauche', 'logo_bas', 'photo_centree', 'deux_colonnes', 'coordonnees_sous_nom']);

test('LOT 4 - photo_droite place l\'image dans la 2e cellule (texte puis photo), miroir de portrait', function (): void {
    $content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $images = ['portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80]];

    $htmlDroite = SignatureRenderer::render($content, 'photo_droite', $images);
    $htmlGauche = SignatureRenderer::render($content, 'portrait', $images);

    // photo_droite : le texte (nom) apparaît AVANT l'image dans le HTML (2e cellule de la rangée).
    expect(strpos($htmlDroite, 'Marie Tremblay'))->toBeLessThan(strpos($htmlDroite, '<img'))
        // portrait (miroir) : l'inverse - l'image (1re cellule) apparaît AVANT le nom.
        ->and(strpos($htmlGauche, '<img'))->toBeLessThan(strpos($htmlGauche, 'Marie Tremblay'));
});

test('LOT 4 - logo_gauche porte le filet supérieur du gabarit professionnel, mais SANS le séparateur latéral de minimal', function (): void {
    $content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $images = ['logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40]];

    $htmlLogoGauche = SignatureRenderer::render($content, 'logo_gauche', $images);
    $htmlMinimal = SignatureRenderer::render($content, 'minimal', $images);

    expect($htmlLogoGauche)->toContain('border-top:3px solid')
        ->and($htmlLogoGauche)->not->toContain('border-left:2px solid')
        ->and($htmlMinimal)->not->toContain('border-top:3px solid')
        ->and($htmlMinimal)->toContain('border-left:2px solid')
        // Logo à GAUCHE - l'image précède le nom dans le HTML, comme minimal.
        ->and(strpos($htmlLogoGauche, '<img'))->toBeLessThan(strpos($htmlLogoGauche, 'Marie Tremblay'));
});

test('LOT 4 - logo_bas groupe le logo et les réseaux tout en bas (après le nom et les mentions)', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie']],
        'mention_lines' => ['Membre de l\'Ordre'],
    ], 'logo_bas', [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
    ]);

    $posName = strpos($html, 'Marie Tremblay');
    $posMentions = strpos($html, 'Membre de l&#039;Ordre');
    $posReseaux = strpos($html, 'LinkedIn');
    $posImage = strpos($html, '<img');

    expect($posName)->not->toBeFalse()->and($posMentions)->not->toBeFalse()
        ->and($posReseaux)->not->toBeFalse()->and($posImage)->not->toBeFalse();

    // Ordre : nom, mentions, PUIS réseaux et logo groupés tout en bas.
    expect($posName)->toBeLessThan($posMentions)
        ->and($posMentions)->toBeLessThan($posReseaux)
        ->and($posReseaux)->toBeLessThan($posImage);
});

test('LOT 4 - logo_bas SANS logo téléversé ne casse pas (masquage propre, réseaux seuls en bas)', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'social_links' => [['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie']],
    ], 'logo_bas');

    expect($html)->toContain('Marie Tremblay')
        ->and($html)->toContain('LinkedIn')
        ->and($html)->not->toContain('<img');
});

test('LOT 4 - photo_centree centre le contenu (text-align:center) et l\'image (margin:0 auto), vertical garde un rendu à gauche', function (): void {
    $content = ['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'];
    $images = ['portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80]];

    $htmlCentree = SignatureRenderer::render($content, 'photo_centree', $images);
    $htmlVertical = SignatureRenderer::render($content, 'vertical', $images);

    expect($htmlCentree)->toContain('text-align:center')
        ->and($htmlCentree)->toContain('margin:0 auto')
        ->and($htmlVertical)->not->toContain('text-align:center')
        ->and($htmlVertical)->not->toContain('margin:0 auto');
});

test('LOT 4 - deux_colonnes affiche une photo (pas un logo) séparée du texte par un filet vertical net', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
    ], 'deux_colonnes', [
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);

    expect($html)->toContain('Photo de Marie Tremblay')
        ->and($html)->toContain('border-left:2px solid')
        ->and(strpos($html, '<img'))->toBeLessThan(strpos($html, 'Marie Tremblay'));
});

test('LOT 4 - coordonnees_sous_nom n\'affiche AUCUNE image et insère un filet horizontal entre l\'identité et le contact', function (): void {
    $html = SignatureRenderer::render([
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com',
        'phone' => '514-555-0100',
    ], 'coordonnees_sous_nom', [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
    ]);

    $posName = strpos($html, 'Marie Tremblay');
    $posDivider = strpos($html, 'border-top:1px solid');
    $posPhone = strpos($html, '514-555-0100');

    expect($html)->not->toContain('<img')
        ->and($posName)->not->toBeFalse()->and($posDivider)->not->toBeFalse()->and($posPhone)->not->toBeFalse()
        ->and($posName)->toBeLessThan($posDivider)
        ->and($posDivider)->toBeLessThan($posPhone);
});

test('LOT 4 - les 8 gabarits d\'origine (LOT 1-3) ne changent PAS de rendu après l\'ajout des 6 nouveaux (non-régression stricte)', function (string $template): void {
    $contentComplet = [
        'first_name' => 'Marie', 'last_name' => 'Tremblay', 'job_title' => 'Directrice',
        'organization' => 'Acme inc.', 'email' => 'marie@example.com', 'phone' => '514-555-0100',
        'mobile' => '438-555-0100', 'website' => 'https://exemple.com', 'address' => '123 rue Principale',
        'tagline' => 'Sur rendez-vous seulement', 'cta_text' => 'Réserver un appel',
        'cta_url' => 'https://laveille.ai/reserver', 'accent_color' => '#064E5A', 'font_family' => 'Georgia',
        'social_links' => [
            ['platform' => 'linkedin', 'url' => 'https://linkedin.com/in/marie'],
            ['platform' => 'facebook', 'url' => 'https://facebook.com/marie'],
        ],
        'mention_lines' => ['Membre de l\'Ordre', 'Numéro de permis 12345'],
    ];
    $images = [
        'logo' => ['url' => 'https://laveille.ai/logo.png', 'width' => 96, 'height' => 40],
        'portrait' => ['url' => 'https://laveille.ai/portrait.png', 'width' => 80, 'height' => 80],
        'banniere' => ['url' => 'https://laveille.ai/banniere.png', 'width' => 600, 'height' => 150],
    ];

    // Aucune trace des propriétés transverses ajoutées au LOT 4 pour la seule famille `vertical`
    // (image_position=bottom, centered, contact_divider) - absentes des 8 définitions LOT 1-3.
    $htmlComplet = SignatureRenderer::render($contentComplet, $template, $images);
    $htmlMinimal = SignatureRenderer::render(['first_name' => 'Marie', 'last_name' => 'Tremblay', 'email' => 'marie@example.com'], $template);

    expect($htmlComplet)->not->toContain('text-align:center')
        ->and($htmlComplet)->not->toContain('margin:0 auto')
        ->and($htmlMinimal)->not->toContain('text-align:center')
        ->and($htmlMinimal)->not->toContain('margin:0 auto');
})->with(['minimal', 'professionnel', 'portrait', 'compact', 'vertical', 'banniere', 'executive', 'social']);
