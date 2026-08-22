<?php

/**
 * Garde-fou de l'affichage des SOURCES en bas des articles.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * POURQUOI CE TEST EXISTE (défaut réel du 2026-08-22, signalé par le fondateur).
 * La charte posait `display: block` sur TOUS les liens de `.sources-section`. Une
 * référence bibliographique citant deux termes de glossaire et un lien externe se
 * retrouvait donc découpée en quatre ou cinq blocs empilés, chacun souligné d'un
 * pointillé, avec des fragments orphelins du type « . ( » puis « source primaire »
 * puis « ) ». Le HTML produit était pourtant parfaitement correct : le défaut était
 * entièrement dans la feuille de style.
 *
 * La règle large venait d'un format HÉRITÉ où une source était un paragraphe ne
 * contenant QUE son lien, présenté en bloc avec un tiret en puce. Ce cas reste
 * légitime et supporté, mais il doit être ciblé par `p > a:only-child`, jamais par
 * un sélecteur qui attrape aussi les liens insérés au milieu d'une phrase.
 *
 * CE TEST EST UN INVARIANT, pas une vérification ponctuelle : le fondateur a demandé
 * que le rendu soit « toujours parfait sans être obligé de le voir chaque fois ». Il
 * échoue donc dès que quelqu'un réintroduit un sélecteur trop large, sans qu'aucune
 * inspection visuelle ne soit nécessaire.
 */

declare(strict_types=1);

/**
 * Extrait le corps d'une règle CSS pour un sélecteur donné, tel qu'il est réellement
 * écrit dans le fichier. Retourne null si le sélecteur est absent.
 */
function lvSourcesCssRule(string $css, string $selector): ?string
{
    // Le sélecteur doit être seul devant l'accolade (éventuellement précédé d'une
    // virgule ou d'un saut de ligne), pour ne pas confondre « .sources-section a »
    // avec « .sources-section a:hover » ou « .sources-section li a ».
    $pattern = '/(?:^|[\n,])\s*'.preg_quote($selector, '/').'\s*\{([^}]*)\}/m';

    return preg_match($pattern, $css, $m) === 1 ? $m[1] : null;
}

beforeEach(function () {
    // Chemin construit depuis __DIR__ plutôt que par public_path() : ce test lit un
    // fichier statique et ne doit dépendre ni du conteneur applicatif ni de la
    // configuration, pour rester exécutable dans n'importe quel contexte.
    $this->cssPath = dirname(__DIR__, 4).'/public/css/charte.css';
    expect(file_exists($this->cssPath))->toBeTrue('charte.css doit exister : '.$this->cssPath);
    $this->css = (string) file_get_contents($this->cssPath);
});

it('garde les liens des sources en ligne, jamais en bloc', function () {
    $rule = lvSourcesCssRule($this->css, '.sources-section a');

    expect($rule)->not->toBeNull('la règle .sources-section a doit exister');

    // Le coeur du défaut : un display:block ici fragmente chaque référence.
    expect($rule)->not->toMatch('/display\s*:\s*block/i',
        'REGRESSION : .sources-section a repasse en display:block. '
        .'Cela recoupe chaque source en autant de blocs qu\'elle contient de liens '
        .'(glossaire, lien externe...). Un lien au milieu d\'une phrase reste INLINE.');

    expect($rule)->toMatch('/display\s*:\s*inline/i',
        '.sources-section a doit déclarer explicitement display:inline');
});

it('réserve le style en bloc au cas hérité du lien seul dans son paragraphe', function () {
    $rule = lvSourcesCssRule($this->css, '.sources-section p > a:only-child');

    expect($rule)->not->toBeNull(
        'Le format hérité (une source = un paragraphe contenant UNIQUEMENT son lien) '
        .'doit rester supporté, via p > a:only-child.');

    expect($rule)->toMatch('/display\s*:\s*block/i',
        'le cas hérité s\'affiche bien en bloc');
});

it('ne pose aucun sélecteur large qui remettrait les liens en bloc', function () {
    // Tout sélecteur de .sources-section visant les liens SANS la garde :only-child
    // et déclarant display:block est une régression, quelle qu'en soit la forme.
    preg_match_all('/(?:^|[\n,])\s*(\.sources-section[^{,]*\ba\b[^{,]*)\{([^}]*)\}/m',
        $this->css, $matches, PREG_SET_ORDER);

    $fautifs = [];
    foreach ($matches as $m) {
        $selecteur = trim($m[1]);
        $corps = $m[2];

        if (preg_match('/display\s*:\s*block/i', $corps) === 1
            && ! str_contains($selecteur, ':only-child')) {
            $fautifs[] = $selecteur;
        }
    }

    expect($fautifs)->toBeEmpty(
        'Ces sélecteurs remettent des liens de sources en bloc sans la garde '
        .':only-child, ce qui fragmente les références : '.implode(' | ', $fautifs));
});

it('habille la liste numérotée de références', function () {
    // Le format courant des sources est un <ol><li>. Sans style dédié, le rendu est
    // serré et les retraits incohérents - c'était l'autre moitié du défaut signalé.
    expect($this->css)->toMatch('/\.sources-section\s+li\s*\{/',
        'la liste de sources doit avoir un style dédié (espacement, interligne)');
});
