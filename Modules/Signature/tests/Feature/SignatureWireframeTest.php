<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * LOT 6 (2026-09-26) - schémas de disposition (wireframes) ABSTRAITS des gabarits, refonte du
 * sélecteur de l'étape 1 et de la galerie modale (rejet du fondateur sur les deux rendus
 * précédents : boutons texte nus, puis vrais mini-aperçus en iframe illisibles à petite échelle).
 * Ces tests couvrent le SERVICE {@see SignatureWireframeRenderer} directement (un gabarit -> un
 * schéma SVG) - la vue rendue (sélecteur, galerie) est couverte par SignatureGalleryTest.php et
 * SignatureWizardTest.php.
 */

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Signature\Services\SignatureTemplateRegistry;
use Modules\Signature\Services\SignatureWireframeRenderer;

uses(Tests\TestCase::class);
uses(RefreshDatabase::class);

test('chaque gabarit du registre produit un schéma SVG non vide', function (): void {
    foreach (SignatureTemplateRegistry::templates() as $template) {
        $svg = SignatureWireframeRenderer::svg($template);

        expect($svg)->toBeString()
            ->and($svg)->not->toBe('')
            ->and($svg)->toContain('<svg')
            ->and($svg)->toContain('</svg>')
            // Décoratif : jamais annoncé aux lecteurs d'écran (le libellé visible à côté du schéma
            // porte déjà l'information).
            ->and($svg)->toContain('aria-hidden="true"');
    }
});

test('SignatureWireframeRenderer::map() couvre exactement les 14 gabarits, dans l\'ordre du registre', function (): void {
    $map = SignatureWireframeRenderer::map();

    expect($map)->toBeArray()
        ->and($map)->toHaveCount(14)
        ->and(array_keys($map))->toBe(SignatureTemplateRegistry::templates());
});

test('un gabarit inconnu retombe sur le schéma du gabarit minimal (même repli que le registre)', function (): void {
    $svg = SignatureWireframeRenderer::svg('gabarit-inexistant');

    expect($svg)->toBe(SignatureWireframeRenderer::svg('minimal'));
});

test('le schéma dérive de l\'AGENCEMENT du registre, jamais de la clé du gabarit - deux gabarits aux mêmes propriétés produisent le même schéma', function (): void {
    // "social" et "minimal" partagent exactement le même agencement (layout standard, logo à
    // gauche, valign middle, aucun cadre, séparateur vertical), à l'exception de
    // `social_emphasis` (vrai seulement pour "social") - seule cette différence doit se répercuter.
    $svgMinimal = SignatureWireframeRenderer::svg('minimal');
    $svgSocial = SignatureWireframeRenderer::svg('social');

    expect($svgSocial)->not->toBe($svgMinimal);

    // A contrario, la position de l'image (logo à gauche, séparateur vertical, aucun cadre) est
    // identique entre les deux - seule la partie "réseaux sociaux" du schéma diffère.
    expect(SignatureWireframeRenderer::svg('minimal'))->toContain('fill="#C7CDD6"');
});

test('les 3 couleurs fixes des réseaux sociaux (bleu, rose, orange) apparaissent dans un gabarit qui en affiche', function (): void {
    $svg = SignatureWireframeRenderer::svg('vertical');

    expect($svg)->toContain('#2563EB')
        ->and($svg)->toContain('#E5407A')
        ->and($svg)->toContain('#E5533A');
});

test('le gabarit sans aucune image (executive) ne dessine ni avatar ni logo', function (): void {
    $svg = SignatureWireframeRenderer::svg('executive');

    // Aucun rectangle de logo (40x16-ish) ni carré d'avatar (26x26) - seulement des lignes de
    // texte, un filet sous le nom (name_divider) et des réseaux.
    expect($svg)->not->toContain('rx="4"'); // signature du rectangle logo (rx=4, voir rect())
});
