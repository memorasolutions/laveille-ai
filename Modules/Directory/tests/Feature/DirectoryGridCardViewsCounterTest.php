<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Test Pest - compteur de vues sur les cartes principales de la grille /annuaire (S142,
 * 2026-08-28). Reprend le format déjà affiché sur les cartes "Ajoutés récemment"/"Les plus
 * populaires" (public/partials/_highlight_card.blade.php : icône œil + nombre formaté +
 * infobulle "N vues"), transposé en binding Alpine puisque la grille principale est templatée
 * côté client (x-for sur $toolsJson), pas en @foreach Blade.
 *
 * Corrigé 2026-08-28 (incident 2026-08-13, recoupement GA4 - clicks_count comptait les robots,
 * jusqu'à 652x le trafic humain réel selon la fiche) : la source du badge bascule de clicks_count
 * vers clicks_count_verified, le compteur "propre" filtré anti-robot + dédupliqué via
 * Modules\Core\Services\ViewCounterService (voir migration
 * 2026_08_28_100000_add_clicks_count_verified_to_directory_tools.php). Sous le seuil configurable
 * directory.views_verified_min_display (10 par défaut, Modules/Settings/database/seeders/
 * SettingsDefaultsSeeder.php), le badge reste masqué plutôt que d'afficher un chiffre dérisoire.
 *
 * Rendu DIRECT de la vue (pas de GET HTTP sur route('directory.index')) : le bloc "plus
 * votés" de PublicDirectoryController::index() fait un ->having('community_votes_count', '>', 0)
 * sur une colonne de sous-requête, refusé par le SQLite :memory: de la suite de tests
 * (« HAVING clause on a non-aggregate query »). Déjà documenté et contourné de la même façon
 * dans PublicListCachePurgeOnPublishTest.php (2026-08-27) : limitation PRÉ-EXISTANTE, sans
 * rapport avec ce correctif. Rendre la vue directement isole exactement le périmètre modifié
 * ici (le bloc $toolsJson + le badge Alpine) sans dépendre de ce bloc du contrôleur.
 *
 * Note format : $toolsJson->toJson() est embarqué via {{ }} dans l'attribut HTML x-data="..."
 * (ligne ~300 de la vue) - Blade y échappe donc les guillemets (&quot;) pour rester du HTML
 * valide. html_entity_decode() normalise avant d'y chercher le JSON brut.
 */

use Illuminate\Support\Facades\View;
use Modules\Directory\Models\Tool;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

/**
 * Construction directe (pas de ToolFactory dans ce module - même convention que
 * PublicListCachePurgeOnPublishTest). $clicksCountLegacy (clicks_count) est délibérément TRÈS
 * différent de $clicksCountVerified (clicks_count_verified) dans tous les tests ci-dessous : si
 * le gabarit régressait un jour vers l'ancienne colonne, ces tests le détecteraient immédiatement
 * (999999 apparaîtrait dans le HTML là où seul 1234 est attendu).
 */
function gridViewsCounterTool(string $suffixe, int $clicksCountVerified, int $clicksCountLegacy = 999999): Tool
{
    config(['app.locale' => 'fr_CA']);

    $tool = new Tool();
    $tool->url = 'https://grid-views-'.$suffixe.'.example';
    $tool->pricing = 'free';
    $tool->status = 'published';
    $tool->is_featured = false;
    $tool->clicks_count = $clicksCountLegacy;
    $tool->clicks_count_verified = $clicksCountVerified;
    $tool->setTranslation('name', 'fr_CA', 'Outil grille '.$suffixe);
    $tool->setTranslation('slug', 'fr_CA', 'outil-grille-'.$suffixe.'-'.uniqid());
    $tool->setTranslation('description', 'fr_CA', 'Description de test.');
    $tool->setTranslation('short_description', 'fr_CA', 'Résumé de test.');
    $tool->save();

    // Eager-load categories : Model::preventLazyLoading() est actif hors production
    // (app/Providers/AppServiceProvider.php) et le bloc $toolsJson de la vue accède à
    // $tool->categories sans eager-load préalable si on ne le fait pas ici.
    return $tool->fresh(['categories']);
}

/** Rend directement directory::public.index avec le jeu minimal de variables qu'exige la vue (voir compact() de PublicDirectoryController::index()). */
function renderDirectoryIndexView($tools): string
{
    return View::make('directory::public.index', [
        'tools' => $tools,
        'categories' => collect(),
        'pricingOptions' => \Modules\Directory\Support\PricingCategories::optionsWithEducation(),
        'featuredTools' => collect(),
        'recentTools' => collect(),
        'popularTools' => collect(),
        'topVoted' => collect(),
        'userCollections' => collect(),
        'showArchived' => false,
        'archivedCount' => 0,
        'ecosystemCounts' => [],
        'ecosystemLabels' => [],
    ])->render();
}

test('la carte principale affiche le compteur VÉRIFIÉ (clicks_count_verified), jamais l\'historique pollué (clicks_count), au-dessus du seuil', function () {
    $tool = gridViewsCounterTool('avec-vues', clicksCountVerified: 1234, clicksCountLegacy: 999999);

    $html = html_entity_decode(renderDirectoryIndexView(collect([$tool])), ENT_QUOTES);

    // Donnée transmise à Alpine (le pipeline serveur -> JSON est correct, même format que
    // number_format(..., 0, ',', ' ') déjà utilisé par _highlight_card.blade.php).
    expect($html)->toContain('"clicksCount":1234');
    expect($html)->toContain('"clicksCountFormatted":"1 234"');
    // Preuve négative : l'historique pollué (clicks_count = 999999) ne fuit nulle part.
    expect($html)->not->toContain('999999');
    expect($html)->not->toContain('999 999');

    // Mécanisme d'affichage présent dans le gabarit (même garde x-if que les autres badges
    // conditionnels de cette carte : hasEduPricing, launchYear, tutorialsCount).
    expect($html)->toContain('tool.clicksCount > 0');
    expect($html)->toContain('👁');
    expect($html)->toContain("tool.clicksCountFormatted + ' vues'");
});

test('le badge reste masqué sous le seuil directory.views_verified_min_display, même avec un compteur vérifié non nul', function () {
    // Seuil par défaut = 10 (SettingsDefaultsSeeder, non exécuté dans ce test -> valeur de repli
    // du Settings::get() dans index.blade.php). 5 vues vérifiées doit donc rester invisible :
    // un badge "5 vues" sur une fiche dont l'historique pollué affichait peut-être des milliers
    // de clics robots serait plus trompeur qu'aucun badge du tout.
    $tool = gridViewsCounterTool('sous-seuil', clicksCountVerified: 5);

    $html = html_entity_decode(renderDirectoryIndexView(collect([$tool])), ENT_QUOTES);

    expect($html)->toContain('"clicksCount":0');
    expect($html)->toContain('"clicksCountFormatted":"0"');
    // Le chiffre réel (5) ne doit fuiter dans AUCUNE des deux clés transmises au client.
    expect($html)->not->toContain('"clicksCount":5');
});

test('le compteur reste transmis à zéro sans casser le gabarit (garde-fou évite un "0 vues" trompeur côté client)', function () {
    $tool = gridViewsCounterTool('sans-vues', clicksCountVerified: 0);

    $html = html_entity_decode(renderDirectoryIndexView(collect([$tool])), ENT_QUOTES);

    expect($html)->toContain('"clicksCount":0');
    // Le garde x-if="tool.clicksCount > 0" (vérifié ci-dessus) est le seul mécanisme qui
    // masque le badge côté client pour ce cas - identique au traitement déjà en place pour
    // hasEduPricing/tutorialsCount sur la même carte.
});
