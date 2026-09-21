<?php

declare(strict_types=1);

/**
 * Méga-menus : ouverture au CLIC (tickets #2561 et #2562, 2026-09-14).
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 *
 * Ces contrôles portent sur la SOURCE plutôt que sur un rendu HTTP : le gabarit du menu n'est
 * rendu qu'à l'intérieur d'un layout complet qui tire des dizaines de requêtes, et la nuance à
 * verrouiller ici est structurelle, pas visuelle.
 *
 * ATTENTION à la frontière lue par ces tests : le fichier contient AUSSI trois anciens
 * méga-menus (Ressources, Jouer, Pages) enfermés dans un `@if(false)` depuis le ticket #200.
 * Ils ne sont jamais rendus et ne sont volontairement PAS corrigés. Un contrôle naïf qui
 * compterait toutes les occurrences du fichier les inclurait et échouerait à tort - c'est
 * exactement l'erreur commise pendant l'implémentation, où un grep a fait conclure « six
 * méga-menus » alors que trois seulement sont vivants.
 */

uses(Tests\TestCase::class);

function megaMenuSourceVivante(): string
{
    $chemin = base_path('Modules/FrontTheme/resources/views/partials/header.blade.php');
    $lignes = file($chemin);

    // On s'arrête au premier `@if(false)` : au-delà commence le code mort du ticket #200.
    $fin = null;
    foreach ($lignes as $i => $ligne) {
        if (str_contains($ligne, '@if(false)')) {
            $fin = $i;
            break;
        }
    }

    expect($fin)->not->toBeNull('Le repère @if(false) a disparu du gabarit : ces tests lisent la mauvaise zone.');

    return implode('', array_slice($lignes, 0, $fin));
}

it('ouvre les méga-menus au clic et jamais au survol', function () {
    $source = megaMenuSourceVivante();

    // Le survol instantané était la cause du défaut signalé : un panneau large déborde sous les
    // entrées voisines, donc toute trajectoire en diagonale traversait un voisin qui refermait le
    // premier panneau.
    expect($source)->not->toContain('@mouseenter');
    expect($source)->not->toContain('@mouseleave');
    expect(substr_count($source, "x-data=\"megaMenu('"))->toBe(3);
});

it("remplace le lien qui n'allait nulle part par un vrai bouton", function () {
    $source = megaMenuSourceVivante();

    // Un <a href> portant @click.prevent ment sur sa nature : il se présente comme un lien,
    // se comporte comme un bouton, et n'amène nulle part.
    expect($source)->not->toContain('@click.prevent="megaOpen');
    expect(substr_count($source, 'button type="button" class="lv-mega-declencheur"'))->toBe(3);
    expect(substr_count($source, ':aria-expanded="open"'))->toBe(3);
    expect(substr_count($source, 'aria-controls="lv-mega-'))->toBe(3);
});

it('contient chaque panneau dans la hauteur de la fenêtre', function () {
    $source = megaMenuSourceVivante();

    // Mesuré au navigateur avant correctif : le panneau « Outils » débordait de 34 px sous une
    // fenêtre de 700 px, ce qui poussait hors de l'écran sa barre « Voir tous les outils »,
    // seule sortie vers la page d'index.
    expect(substr_count($source, 'max-height:calc(100vh - 170px)'))->toBe(3);
    expect(substr_count($source, 'overflow-y:auto'))->toBe(3);
});

it("n'annonce plus un patron de menu applicatif qu'il n'implémente pas", function () {
    $source = megaMenuSourceVivante();

    // Le W3C APG réserve role="menu"/"menuitem" aux menus d'APPLICATION : ils promettent au
    // lecteur d'écran une navigation aux flèches, une fermeture par Échap et un focus piégé.
    // Pour une navigation de site, le patron est « disclosure ».
    expect($source)->not->toContain('role="menu"');
    expect($source)->not->toContain('role="menuitem"');
});

/**
 * LE test qui compte le plus, parce que son absence coûte une page blanche silencieuse.
 * Alpine n'est pas chargé par le site : il est embarqué par Livewire, EN BAS du body. Un
 * composant enregistré après le démarrage d'Alpine ne s'enregistre jamais, et le menu cascade
 * en ReferenceError sans qu'aucun test de présence ne le voie. C'est exactement le défaut du
 * ticket #2210 sur l'écran objectif-vidéo.
 */
it('charge le composant AVANT le script Livewire qui démarre Alpine', function () {
    $layout = file_get_contents(base_path('Modules/FrontTheme/resources/views/layouts/master.blade.php'));

    // Les commentaires Blade sont retirés AVANT de chercher : le layout mentionne
    // « @livewireScripts » dans deux commentaires explicatifs situés plus haut que la directive
    // elle-même. Chercher la chaîne nue comparait donc la position du composant à celle d'un
    // commentaire, et faisait échouer un ordre pourtant correct. Le piège est le même que celui
    // que ce test existe pour attraper : on croit mesurer le chargement, on mesure du texte.
    $sansCommentaires = preg_replace('#\{\{--.*?--\}\}#s', '', $layout);

    $posComposant = strpos((string) $sansCommentaires, 'mega-menu.js');
    $posLivewire = strpos((string) $sansCommentaires, '@livewireScripts');

    expect($posComposant)->not->toBeFalse('Le composant mega-menu.js n\'est plus chargé par le layout.');
    expect($posLivewire)->not->toBeFalse();
    expect($posComposant)->toBeLessThan($posLivewire);
});

it("s'enregistre par alpine:init, jamais par un appel direct", function () {
    $js = file_get_contents(public_path('js/mega-menu.js'));

    expect($js)->toContain("addEventListener('alpine:init'");
    expect($js)->toContain("Alpine.data('megaMenu'");

    // Aucun survol ne doit revenir par la porte de derrière. On interdit l'ÉCOUTE, pas le mot :
    // « mouseleave » est légitime dans le commentaire qui explique pourquoi il a été retiré.
    // Un contrôle sur le mot nu échouerait sur sa propre documentation.
    $sansCommentaires = preg_replace('#/\*.*?\*/#s', '', $js);
    $sansCommentaires = preg_replace('#//[^\n]*#', '', (string) $sansCommentaires);

    expect($sansCommentaires)->not->toContain('mouseenter');
    expect($sansCommentaires)->not->toContain('mouseleave');
});

it('neutralise le panneau large sous le point de rupture mobile', function () {
    $css = file_get_contents(public_path('css/charte.css'));

    // 780 px de panneau dans une fenêtre de 375 px provoquent un défilement horizontal de toute
    // la page. Sous 992 px, c'est le .sub-menu du menu hamburger qui prend le relais.
    expect($css)->toContain('@media (max-width: 991.98px)');
    expect($css)->toContain('.wpo-site-header .has-mega-menu > [id^="lv-mega-"]');
});

/**
 * Le garde-fou qui MANQUAIT, et dont l'absence a coûté une régression visible en production
 * (signalée par le fondateur avec capture : « les menus sont décalés et semblent ne pas avoir la
 * même taille de police »).
 *
 * J'avais reproduit le style MESURÉ sur le lien que je remplaçais, et conclu que rien ne bougeait
 * parce que la boîte faisait la même hauteur. Mais la mesure venait du site local : en production
 * les liens voisins sont à 16px/24px/6px, pas 18px/27px/10px. Les boutons sortaient 3 px plus bas.
 *
 * Aucune mesure ISOLÉE ne pouvait révéler ça. Seule la COMPARAISON avec le voisin resté intact le
 * pouvait. Ce test fige donc les quatre valeurs qui doivent rester identiques.
 */
it('donne aux boutons de méga-menu exactement la typographie des liens voisins', function () {
    $css = file_get_contents(public_path('css/charte.css'));

    // Le repérage ne vise PLUS la première occurrence dans une fenêtre de 900 octets : ce
    // mécanisme s'est cassé le 2026-09-15, quand le correctif v1.287.3 a ajouté une règle
    // groupée « > li > a, > li > button.lv-mega-declencheur » AVANT la vraie règle. strpos()
    // s'arrêtait alors sur une règle qui ne porte que la police, et la fenêtre n'atteignait
    // plus font-size. Le CSS n'avait pas régressé - seule la sonde du test était fautive.
    // On collecte donc TOUS les blocs racine du sélecteur (l'ancre ^ écarte les copies
    // indentées des @media), du « { » jusqu'à sa « } ».
    preg_match_all(
        '/^\.wpo-site-header \.navigation \.navbar-nav > li > button\.lv-mega-declencheur \{/m',
        $css,
        $occurrences,
        PREG_OFFSET_CAPTURE
    );
    expect($occurrences[0])->not->toBeEmpty('La règle du déclencheur a disparu de charte.css.');

    $bloc = '';
    foreach ($occurrences[0] as [$selecteur, $position]) {
        $ouvrante = $position + strlen($selecteur) - 1;
        $fermante = strpos($css, '}', $ouvrante + 1);
        expect($fermante)->not->toBeFalse('Bloc du déclencheur jamais refermé dans charte.css.');
        $bloc .= substr($css, $ouvrante, $fermante - $ouvrante + 1)."\n";
    }

    // Les valeurs relevées en production sur « Accueil » et « Livres », qui sont restés des <a>.
    expect($bloc)->toContain('font-size: 16px');
    expect($bloc)->toContain('line-height: 24px');
    expect($bloc)->toContain('padding: 18px 6px');
    expect($bloc)->toContain('font-weight: 500');

    // Les valeurs fautives de la première version ne doivent pas revenir.
    expect($bloc)->not->toContain('font-size: 18px');
    expect($bloc)->not->toContain('line-height: 27px');
    expect($bloc)->not->toContain('padding: 18px 10px');
});
