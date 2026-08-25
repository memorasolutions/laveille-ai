<?php

declare(strict_types=1);

// Les tests de Modules/ n heritent pas automatiquement du TestCase de l application :
// sans cette ligne, base_path() n existe pas.
uses(Tests\TestCase::class);

// Demande du fondateur (2026-08-25) : la fenetre de recadrage montrait la vignette 1200x630 en
// entier, alors que la fiche publique d'un outil n'en affiche qu'une bande. Elle pose l'image en
// `width: 100%` sous une hauteur plafonnee avec `object-fit: cover`, et le navigateur rogne
// l'exces en haut ET en bas. Mesure du 2026-08-25 sur laveille.ai : cadre 1146 px, plafond 400 px,
// donc 66,5 % visibles seulement.
//
// Le repere ne peut PAS etre une constante ecrite dans le composant : sur une fiche d'actualite
// (cadre 740 px, plafond 420 px) la vignette passe ENTIERE, comme sur un affichage mobile etroit.
// Il est donc calcule a l'ouverture, a partir de la largeur mesuree du cadre et de sa variable
// CSS --fc-apercu-hauteur-max. Ces tests verrouillent la chaine qui rend ce calcul possible :
// si un maillon casse, le repere disparait SILENCIEUSEMENT, ce qui est exactement le defaut
// d'origine. Le calcul lui-meme est couvert par tests/js/focal-cropper-math.test.cjs.

function ficheOutilBlade(): string
{
    return file_get_contents(
        base_path('Modules/Directory/resources/views/public/show.blade.php')
    );
}

it('declare le plafond de hauteur une seule fois, en variable CSS sur le cadre de la vignette', function () {
    $vue = ficheOutilBlade();

    expect($vue)->toContain('--fc-apercu-hauteur-max: 400px');

    // L'image doit CONSOMMER la variable plutot que de redeclarer la valeur : deux declarations
    // du meme plafond finiraient par diverger, et le repere mentirait sans que rien ne casse.
    expect($vue)->toContain('max-height: var(--fc-apercu-hauteur-max)');
    expect($vue)->not->toContain('max-height: 400px; object-fit: cover');
});

it('vise un cadre qui existe reellement dans le markup de la fiche', function () {
    $vue = ficheOutilBlade();

    preg_match_all("/apercuSelector[\"']?\s*[:=]\s*[\"']([^\"']+)[\"']/", $vue, $m);

    expect($m[1])->not->toBeEmpty(
        'La fiche doit transmettre un apercuSelector au recadrage, sinon aucun repere ne peut '
        .'etre dessine et la fenetre retombe sur son defaut d origine.'
    );

    foreach (array_unique($m[1]) as $selecteur) {
        $classe = ltrim($selecteur, '.');

        // toContain() de Pest accepte PLUSIEURS aiguilles : un message passe en second argument
        // y serait cherche comme du texte. D'ou le passage par un booleen explicite.
        expect(str_contains($vue, 'class="'.$classe.'"'))->toBeTrue(
            "Le selecteur {$selecteur} ne correspond a aucun element de la fiche : le repere ".
            'serait absent sans qu aucune erreur ne soit levee.'
        );
    }
});

it('couvre aussi le cadre affiche quand l outil n a pas encore de capture', function () {
    $vue = ficheOutilBlade();

    // Le recadrage s'ouvre aussi juste apres une premiere capture, alors que la fiche affiche
    // encore son cadre de repli en degrade. Sans la variable sur CE cadre-la, la premiere capture
    // (le cas le plus courant) serait le seul moment ou le repere manquerait.
    expect(substr_count($vue, '--fc-apercu-hauteur-max: 400px'))->toBeGreaterThanOrEqual(
        2,
        'Les deux cadres de la vignette (avec capture et sans capture) doivent declarer le plafond.'
    );
});

it('garde le repere masque par defaut dans le composant partage', function () {
    $composant = file_get_contents(
        base_path('Modules/Core/resources/views/components/focal-cropper.blade.php')
    );

    // Le composant sert aussi au module News, ou la vignette passe entiere. Les elements du repere
    // doivent donc naitre caches : c'est le calcul qui les revele, jamais le markup.
    expect($composant)->toContain('data-fc-cut hidden');
    expect($composant)->toContain('data-fc-safe hidden');

    // Aucune valeur de rognage ne doit etre ecrite en dur dans le CSS du composant.
    expect($composant)->toContain('--fc-cut: 0%');
    expect($composant)->not->toContain('--fc-cut: 16.75%');
});
