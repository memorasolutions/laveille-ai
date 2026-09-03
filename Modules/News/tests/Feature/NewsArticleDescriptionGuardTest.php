<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Verrouille la garde posée sur la colonne 'description' au lot 1 du design doc
 * « extension de l'écran de composition des actualités » (2026-09-03, section 4.2).
 *
 * Pourquoi ce fichier existe : le retrait de 'description' hors de $fillable ne pouvait pas
 * être appliqué tel quel - la colonne est NOT NULL sans défaut, et deux écrivains de
 * production la fournissent à la création. La garde a donc pris la forme d'une surcharge de
 * NewsArticle::fill() qui distingue la CRÉATION (valeur acceptée) de la MISE À JOUR d'une
 * fiche déjà persistée (valeur ignorée). Ce comportement est invisible : sans les
 * assertions ci-dessous, un futur nettoyage retirerait la surcharge sans qu'aucun test ne
 * bronche, et la fuite purgée le 2026-08-13 se rouvrirait en silence.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// Helpers préfixés ndg pour éviter tout conflit inter-fichiers (Pest charge tout en un
// seul processus - même convention que ActusZeroCopiePipelineTest).
function ndgSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source garde description',
        'url' => 'https://ndg-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function ndgArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Article garde description {$i}",
        'guid' => "guid-ndg-{$suffix}",
        'url' => "https://exemple.com/ndg-{$suffix}",
        'slug' => "article-ndg-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

it('accepte description a la CREATION - les ecrivains de production ne sont pas casses', function () {
    $source = ndgSource();

    // RssFetcherService::fetchSource() et createManualDraft() fournissent tous deux
    // 'description' par assignation de masse à la création. Ce chemin doit rester ouvert.
    $article = ndgArticle($source->id, ['description' => 'Extrait RSS légitime']);

    expect($article->fresh()->description)->toBe('Extrait RSS légitime');
});

it('IGNORE description sur la mise a jour d une fiche deja persistee, sans bloquer les autres champs', function () {
    $source = ndgSource();
    $article = ndgArticle($source->id, ['description' => 'Valeur initiale']);

    $article->update([
        'description' => 'TEXTE SOURCE INTÉGRAL QUI NE DOIT JAMAIS ÊTRE PERSISTÉ',
        'title' => 'Titre mis à jour',
    ]);

    $frais = $article->fresh();

    // Le coeur de la garde : la description n'a pas bougé...
    expect($frais->description)->toBe('Valeur initiale')
        // ...et la mise à jour n'est PAS bloquée pour autant : les autres champs passent.
        ->and($frais->title)->toBe('Titre mis à jour');
});

it('remplit description a vide quand une creation ne la mentionne pas du tout', function () {
    $source = ndgSource();

    // La colonne est NOT NULL sans défaut (migration 2026_03_29_000000_create_news_tables).
    // Sans le filet posé dans booted()/creating, cette création lèverait une violation de
    // contrainte au lieu d'écrire une valeur vide explicite.
    $article = ndgArticle($source->id);

    expect($article->fresh()->description)->toBe('');
});

it('ignore aussi description via un fill() explicite sur une fiche chargee depuis la base', function () {
    $source = ndgSource();
    $id = ndgArticle($source->id, ['description' => 'Valeur initiale'])->id;

    // Chemin distinct de update() : instance rechargée, fill() appelé à la main puis save().
    $recharge = NewsArticle::findOrFail($id);
    $recharge->fill(['description' => 'Tentative par fill direct'])->save();

    expect($recharge->fresh()->description)->toBe('Valeur initiale');
});
