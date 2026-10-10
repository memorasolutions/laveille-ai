<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Régression : une seule entrée du menu « Outils » affichait « Calculatrice taxes QC » (le libellé
 * de Tool#8, slug calculatrice-taxes) mais pointait vers /outils/simulateur-fiscal (Tool#15). Les
 * deux outils existent réellement et étaient tous les deux actifs en base - le vrai défaut n'était
 * pas un lien inversé mais une entrée de menu MANQUANTE pour la calculatrice, qui laissait le
 * simulateur usurper son libellé. Corrigé en v1.237.8 : chaque outil a désormais sa propre entrée,
 * dans les trois zones vivantes du menu (mega-menu desktop, repli mobile de ce mega-menu, et la
 * barre latérale mobile). Le bloc « Jouer » (#181) contient la même paire orpheline mais est mort
 * de codé, encadré par @if(false) depuis la fusion #200 - il est volontairement HORS de portée ici.
 */

declare(strict_types=1);

uses(Tests\TestCase::class);

beforeEach(function () {
    // Depuis le ticket #3013 la navigation vient d'une source unique (HeaderNavService) et les
    // gabarits partials/nav/* la rendent : on contrôle donc le HTML RENDU, plus la source Blade.
    // Compteurs pré-remplis en cache pour ne dépendre d'aucune base de données.
    foreach (['directory_tools_count', 'dictionary_terms_count', 'acronyms_count'] as $cle) {
        cache()->put($cle, 1, 60);
    }
    $nav = app(\Modules\FrontTheme\Services\HeaderNavService::class)->tree();

    // Zone 1 : mega-menu « Outils » desktop + son repli <ul class="sub-menu"> mobile (même <li>).
    $html = view('fronttheme::partials.nav.items', ['nav' => $nav])->render();
    $start = mb_strpos($html, "megaMenu('outils')");
    $end = mb_strpos($html, "megaMenu('annuaire')");
    expect($start)->not->toBeFalse();
    expect($end)->not->toBeFalse();
    $this->outilsMenu = mb_substr($html, $start, $end - $start);

    // Zone 2 : widget « Outils » de la barre latérale mobile (hamburger).
    $sidebar = view('fronttheme::partials.nav.sidebar', ['nav' => $nav])->render();
    $sidebarStart = mb_strpos($sidebar, '<h3>Outils</h3>');
    $sidebarEnd = mb_strpos($sidebar, '<h3>Annuaire</h3>');
    expect($sidebarStart)->not->toBeFalse();
    expect($sidebarEnd)->not->toBeFalse();
    $this->outilsSidebar = mb_substr($sidebar, $sidebarStart, $sidebarEnd - $sidebarStart);
});

/**
 * Isole le fragment de menu qui suit IMMÉDIATEMENT le lien vers /outils/{$slug} - jusqu'au
 * prochain lien /outils/ (ou 400 caractères, largement assez pour icône + titre + sous-titre
 * d'une seule entrée) - pour vérifier que CE lien précis affiche bien le bon libellé, plutôt que
 * de chercher le libellé n'importe où dans toute la zone.
 */
function fenetreApresLienOutil(string $section, string $slug): string
{
    $needle = "/outils/{$slug}\"";
    $pos = mb_strpos($section, $needle);
    expect($pos)->not->toBeFalse("Lien vers /outils/{$slug} introuvable dans cette zone du menu.");

    $nextPos = mb_strpos($section, '/outils/', $pos + mb_strlen($needle));

    return $nextPos !== false
        ? mb_substr($section, $pos, $nextPos - $pos)
        : mb_substr($section, $pos, 400);
}

it('mega-menu desktop Outils : l\'entrée /outils/calculatrice-taxes affiche « Calculatrice taxes QC », jamais le libellé du simulateur', function () {
    $fenetre = fenetreApresLienOutil($this->outilsMenu, 'calculatrice-taxes');

    expect($fenetre)->toContain('Calculatrice taxes QC');
    expect($fenetre)->not->toContain('Simulateur fiscal Québec');
});

it('mega-menu desktop Outils : l\'entrée /outils/simulateur-fiscal affiche « Simulateur fiscal Québec », jamais le libellé de la calculatrice', function () {
    $fenetre = fenetreApresLienOutil($this->outilsMenu, 'simulateur-fiscal');

    expect($fenetre)->toContain('Simulateur fiscal Québec');
    expect($fenetre)->not->toContain('Calculatrice taxes QC');
});

it('repli mobile <ul class="sub-menu"> du mega-menu Outils : les deux outils fiscaux ont chacun leur propre lien', function () {
    // Portée : uniquement le <ul class="sub-menu"> (repli), pas le <div> mega-menu qui précède.
    $ulPos = mb_strpos($this->outilsMenu, '<ul class="sub-menu">');
    expect($ulPos)->not->toBeFalse();
    $sousMenu = mb_substr($this->outilsMenu, $ulPos);

    $fenetreCalc = fenetreApresLienOutil($sousMenu, 'calculatrice-taxes');
    expect($fenetreCalc)->toContain('Calculatrice taxes QC');
    expect($fenetreCalc)->not->toContain('Simulateur fiscal Québec');

    $fenetreSim = fenetreApresLienOutil($sousMenu, 'simulateur-fiscal');
    expect($fenetreSim)->toContain('Simulateur fiscal Québec');
    expect($fenetreSim)->not->toContain('Calculatrice taxes QC');
});

it('barre latérale mobile (hamburger) : les deux outils fiscaux ont chacun leur propre lien', function () {
    $fenetreCalc = fenetreApresLienOutil($this->outilsSidebar, 'calculatrice-taxes');
    expect($fenetreCalc)->toContain('Calculatrice taxes QC');
    expect($fenetreCalc)->not->toContain('Simulateur fiscal Québec');

    $fenetreSim = fenetreApresLienOutil($this->outilsSidebar, 'simulateur-fiscal');
    expect($fenetreSim)->toContain('Simulateur fiscal Québec');
    expect($fenetreSim)->not->toContain('Calculatrice taxes QC');
});

it('aucune zone vivante du menu ne recolle l\'icône 💰 du lien simulateur-fiscal au libellé de la calculatrice (signature exacte du bug historique)', function () {
    $signatureBug = '/outils/simulateur-fiscal">💰 Calculatrice taxes QC';

    expect($this->outilsMenu)->not->toContain($signatureBug);
    expect($this->outilsSidebar)->not->toContain($signatureBug);
});
