<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Volet D du design doc « extension de l'écran de composition des actualités » (2026-09-03) :
 * la ligne de provenance compacte, en haut de fiche, cite DEUX sources primaires quand deux
 * existent, là où elle était limitée à la première.
 *
 * Ce que ces tests verrouillent, et pourquoi chacun compte :
 *   1. deux sources -> deux liens et la conjonction : le gain demandé ;
 *   2. une seule source -> un seul lien, sans conjonction : pas de « et » orphelin ;
 *   3. aucune source -> aucune ligne : la garde d'origine est préservée (sans elle,
 *      $primarySources[0]['url'] émet un avertissement PHP au lieu de ne rien afficher) ;
 *   4. une deuxième entrée à l'URL VIDE -> traitée comme une absence. C'est le cas que le
 *      design justifie explicitement : tester l'URL, pas count($primarySources), parce
 *      qu'une donnée historique peut porter une entrée d'indice 1 sans URL utilisable.
 *
 * Piège mesuré en écrivant ces tests : les libellés de source NE CONTIENNENT AUCUNE APOSTROPHE,
 * volontairement. Blade échappe `'` en `&#039;`, donc un `toContain("l'arrêt...")` ne peut jamais
 * correspondre - et son jumeau `not->toContain(...)` passe alors pour la mauvaise raison, ce qui
 * est bien pire qu'un échec. Les accents, eux, traversent intacts (htmlspecialchars ne les touche
 * pas) : « européenne » et « Sénat » sont sûrs.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Helpers préfixés npv pour éviter tout conflit inter-fichiers (Pest charge tout en un
// seul processus - même convention que ActusZeroCopiePipelineTest).
function npvSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source provenance',
        'url' => 'https://npv-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function npvArticle(array $primarySources): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create([
        'news_source_id' => npvSource()->id,
        'title' => "Fiche provenance {$i}",
        'guid' => "guid-npv-{$suffix}",
        'url' => "https://exemple.com/npv-{$suffix}",
        'description' => '',
        'slug' => "fiche-npv-{$suffix}",
        'summary' => 'Un résumé publié, suffisant pour que la page se rende.',
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
        'primary_sources' => $primarySources,
    ]);
}

it('cite les DEUX sources primaires quand deux existent', function () {
    $article = npvArticle([
        ['url' => 'https://commission.exemple.eu/communique', 'label' => 'la Commission européenne'],
        ['url' => 'https://senat.exemple.fr/rapport', 'label' => 'le rapport du Sénat'],
    ]);

    $reponse = $this->get('/actualites/'.$article->slug);
    $reponse->assertOk();

    // ATTENTION - assertion volontairement PORTÉE SUR LA LIGNE COMPACTE SEULE, jamais sur
    // la page entière : la section « Sources » du bas liste DÉJÀ toutes les sources
    // primaires, donc un assertSee() global passerait même sans ce correctif. Mesuré :
    // la première version de ce test restait verte avec le correctif retiré.
    $ligne = npvLigneProvenance($reponse->getContent());

    expect($ligne)->not->toBeNull()
        ->and(substr_count((string) $ligne, '<a '))->toBe(2)
        ->and($ligne)->toContain('la Commission européenne')
        ->and($ligne)->toContain('le rapport du Sénat')
        ->and($ligne)->toContain('https://senat.exemple.fr/rapport');
});

it('ne cite qu une source et n affiche aucune conjonction quand il n y en a qu une', function () {
    $article = npvArticle([
        ['url' => 'https://commission.exemple.eu/communique', 'label' => 'la Commission européenne'],
    ]);

    $reponse = $this->get('/actualites/'.$article->slug);

    $reponse->assertOk()->assertSee('la Commission européenne');

    // La ligne compacte ne doit pas se terminer par un « et » sans second lien.
    $ligne = npvLigneProvenance($reponse->getContent());
    expect($ligne)->not->toBeNull()
        ->and(substr_count((string) $ligne, '<a '))->toBe(1);
});

it('n affiche aucune ligne de provenance quand la fiche n a aucune source primaire', function () {
    $article = npvArticle([]);

    $reponse = $this->get('/actualites/'.$article->slug);

    $reponse->assertOk();
    expect(npvLigneProvenance($reponse->getContent()))->toBeNull();
});

it('traite une deuxieme entree sans URL comme une absence, pas comme une source', function () {
    // Donnée historique plausible : l'entrée existe, mais son URL est vide.
    $article = npvArticle([
        ['url' => 'https://commission.exemple.eu/communique', 'label' => 'la Commission européenne'],
        ['url' => '', 'label' => 'une entrée héritée sans lien'],
    ]);

    $reponse = $this->get('/actualites/'.$article->slug);

    $reponse->assertOk();

    $ligne = npvLigneProvenance($reponse->getContent());

    expect($ligne)->not->toBeNull()
        ->and(substr_count((string) $ligne, '<a '))->toBe(1)
        ->and($ligne)->toContain('la Commission européenne')
        ->and($ligne)->not->toContain('une entrée héritée sans lien');
});

it('plafonne la ligne compacte a DEUX liens meme avec trois sources, et garde la 3e en bas de fiche', function () {
    // Non-objectif explicite du design (section 5.2) : la ligne compacte ne construit JAMAIS
    // « D'après X, Y et Z ». Ce test verrouille ce plafond - sans lui, une « généralisation »
    // en boucle sur $primarySources passerait pour une amélioration.
    $article = npvArticle([
        ['url' => 'https://commission.exemple.eu/communique', 'label' => 'la Commission européenne'],
        ['url' => 'https://senat.exemple.fr/rapport', 'label' => 'le rapport du Sénat'],
        ['url' => 'https://tribunal.exemple.eu/arret', 'label' => 'le jugement du Tribunal'],
    ]);

    $reponse = $this->get('/actualites/'.$article->slug);
    $reponse->assertOk();
    $html = $reponse->getContent();

    $ligne = npvLigneProvenance($html);
    expect($ligne)->not->toBeNull()
        ->and(substr_count((string) $ligne, '<a '))->toBe(2)
        ->and($ligne)->not->toContain('le jugement du Tribunal');

    // ...mais la 3e reste lisible : la section « Sources » du bas est exhaustive.
    $bas = npvSectionSources($html);
    expect($bas)->not->toBeNull()
        ->and($bas)->toContain('le jugement du Tribunal');
});

it('affiche les DEUX sources dans la section Sources du bas, sans changement de code', function () {
    // Second volet du ticket #1860 (« citer les deux, deux boutons ») : le design (section 5.1)
    // affirme que cette section boucle DÉJÀ sur toutes les sources primaires, donc qu'elle
    // n'exige aucun correctif. Cette affirmation est ici prouvée plutôt que crue - c'est la
    // seule façon de savoir que le ticket est réellement clos, et non à moitié.
    $article = npvArticle([
        ['url' => 'https://commission.exemple.eu/communique', 'label' => 'la Commission européenne'],
        ['url' => 'https://senat.exemple.fr/rapport', 'label' => 'le rapport du Sénat'],
    ]);

    $bas = npvSectionSources($this->get('/actualites/'.$article->slug)->assertOk()->getContent());

    expect($bas)->not->toBeNull()
        ->and($bas)->toContain('la Commission européenne')
        ->and($bas)->toContain('le rapport du Sénat')
        ->and($bas)->toContain('https://commission.exemple.eu/communique')
        ->and($bas)->toContain('https://senat.exemple.fr/rapport');
});

/** Extrait la liste « Sources » de fin de fiche, ou null si elle est absente. */
function npvSectionSources(string $html): ?string
{
    return preg_match('#<ul class="nw-sources-list">(.*?)</ul>#s', $html, $m) === 1 ? $m[0] : null;
}

/** Extrait la ligne compacte de provenance du HTML rendu, ou null si elle est absente. */
function npvLigneProvenance(string $html): ?string
{
    return preg_match('#<p class="nw-provenance">(.*?)</p>#s', $html, $m) === 1 ? $m[0] : null;
}
