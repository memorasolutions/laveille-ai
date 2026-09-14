<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Les entités servent à REGROUPER des fiches en dossiers. Une entité détectée à tort crée un
 * dossier faux, qui rassemble des fiches sans rapport - c'est pire que pas de dossier du tout,
 * et c'est invisible tant qu'on ne lit pas le contenu du regroupement.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function beSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source entités', 'url' => 'https://be.exemple.com/rss',
        'language' => 'fr', 'active' => true,
    ]);
}

function beFiche(int $src, string $titre): NewsArticle
{
    static $i = 0;
    $i++;

    return NewsArticle::create([
        'news_source_id' => $src, 'title' => $titre, 'guid' => "g-be-{$i}-".uniqid(),
        'url' => "https://exemple.com/be-{$i}", 'slug' => "be-{$i}-".uniqid(),
        'pub_date' => now()->subDays(20), 'is_published' => true, 'seo_status' => 'index',
    ]);
}

it('détecte les entités connues dans un titre', function () {
    $f = beFiche(beSource()->id, 'OpenAI et Anthropic rivalisent sur le marché des agents');

    $this->artisan('news:backfill-entities --appliquer')->assertSuccessful();

    $labels = $f->fresh()->entities->pluck('entity_label')->all();
    expect($labels)->toContain('OpenAI');
    expect($labels)->toContain('Anthropic');
});

/**
 * LE test qui compte. Une analyse déléguée avait mesuré « Intel : 85 occurrences » avant de
 * comprendre que le mot vivait dans « intelligence ». Le même piège guette « meta » dans
 * « metadata ». Sans frontière de mot, le regroupement serait faussé dès le départ.
 */
it('ne confond PAS une entité avec une sous-chaîne d\'un autre mot', function () {
    $src = beSource()->id;
    $a = beFiche($src, "L'intelligence artificielle transforme les metadata des entreprises");
    $b = beFiche($src, "Une approche métacognitive de l'apprentissage automatique");

    $this->artisan('news:backfill-entities --appliquer')->assertSuccessful();

    // « intelligence » contient « intel », « metadata » contient « meta ».
    expect($a->fresh()->entities)->toBeEmpty();
    expect($b->fresh()->entities)->toBeEmpty();
});

it('reconnaît les variantes et les ramène au libellé canonique', function () {
    $src = beSource()->id;
    $a = beFiche($src, 'Chat GPT ajoute une fonction de mémoire');
    $b = beFiche($src, 'Grok publie un nouveau modèle');
    $c = beFiche($src, 'Huggingface héberge désormais 2 millions de modèles');

    $this->artisan('news:backfill-entities --appliquer')->assertSuccessful();

    expect($a->fresh()->entities->pluck('entity_label')->all())->toContain('ChatGPT');
    expect($b->fresh()->entities->pluck('entity_label')->all())->toContain('xAI');
    expect($c->fresh()->entities->pluck('entity_label')->all())->toContain('Hugging Face');
});

it("n'écrit rien sans --appliquer", function () {
    $f = beFiche(beSource()->id, 'Google dévoile Gemini 4');

    $this->artisan('news:backfill-entities')->assertSuccessful();

    expect($f->fresh()->entities)->toBeEmpty();
});

// Une fiche déjà curée par /actu2 ne doit jamais voir ses entités écrasées par une détection
// automatique, forcément plus grossière qu'une curation humaine.
it('ne touche JAMAIS une fiche qui a déjà des entités', function () {
    $f = beFiche(beSource()->id, 'OpenAI annonce quelque chose');
    $f->syncEntities(['Entité curée à la main']);

    $this->artisan('news:backfill-entities --appliquer')->assertSuccessful();

    $labels = $f->fresh()->entities->pluck('entity_label')->all();
    expect($labels)->toBe(['Entité curée à la main']);
    expect($labels)->not->toContain('OpenAI');
});

/**
 * Le plafond que le code généré ignorait : chunkById repagine par id et passe outre un limit()
 * posé sur la requête. Avec 5 fiches et --limit=2, le code d'origine en aurait traité 5 - une
 * option qui ment sur ce qu'elle fait est pire qu'une option absente.
 */
it('respecte VRAIMENT --limit malgré chunkById', function () {
    $src = beSource()->id;
    foreach (range(1, 5) as $n) {
        beFiche($src, "OpenAI publie la version {$n}");
    }

    $this->artisan('news:backfill-entities --appliquer --limit=2')->assertSuccessful();

    $avecEntites = NewsArticle::whereHas('entities')->count();
    expect($avecEntites)->toBe(2);
});

it('ignore les fiches retirées', function () {
    $f = beFiche(beSource()->id, 'Anthropic publie un rapport');
    $f->forceFill(['retired_at' => now()])->save();

    $this->artisan('news:backfill-entities --appliquer')->assertSuccessful();

    expect($f->fresh()->entities)->toBeEmpty();
});
