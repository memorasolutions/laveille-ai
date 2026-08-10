<?php

declare(strict_types=1);

/**
 * Tests du clustering pur (Actus 2.0) : union-find déterministe sur
 * DedupService::isSameStoryCluster(), aucun appel réseau, aucune dépendance IA.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\ArticleClusteringService;

uses(Tests\TestCase::class, RefreshDatabase::class);

function acsSource(string $name = 'ACS'): NewsSource
{
    return NewsSource::create(['name' => $name, 'url' => 'https://exemple.com/'.\Illuminate\Support\Str::slug($name).'-'.uniqid(), 'language' => 'fr']);
}

function acsArticle(string $slug, string $title, $pubDate = null): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => acsSource($slug)->id,
        'title' => $title,
        'guid' => 'guid-'.$slug,
        'url' => 'https://exemple.com/'.$slug,
        'description' => 'Description de test pour '.$slug,
        'pub_date' => $pubDate ?? now(),
        'is_published' => false,
    ]);
}

beforeEach(function () {
    config([
        'news.fusion.jaccard_threshold' => 0.30,
        'news.fusion.min_entity_overlap' => 2,
        'news.fusion.min_group_size' => 2,
        'news.fusion.window_hours' => 36,
    ]);
});

it('regroupe deux titres au sujet clairement partage dans une meme composante', function () {
    $a = acsArticle('cl-a', 'Microsoft lance un agent IA generative pour Word et Excel');
    $b = acsArticle('cl-b', 'Microsoft devoile un agent IA generative integre a Word');

    $result = (new ArticleClusteringService())->cluster([$a, $b]);

    expect($result['new_groups'])->toHaveCount(1)
        ->and($result['new_groups'][0])->toHaveCount(2)
        ->and($result['singletons'])->toBeEmpty()
        ->and($result['absorptions'])->toBeEmpty();
});

it('ne regroupe jamais deux titres franchement differents meme avec une entite faible partagee', function () {
    $a = acsArticle('df-a', 'Apple annonce des resultats financiers records ce trimestre');
    $b = acsArticle('df-b', 'Tesla devoile une nouvelle usine de batteries au Nevada');

    $result = (new ArticleClusteringService())->cluster([$a, $b]);

    expect($result['new_groups'])->toBeEmpty()
        ->and($result['singletons'])->toHaveCount(2);
});

it('produit exactement les memes composantes a deux executions successives (deterministe)', function () {
    $a = acsArticle('det-a', 'OpenAI publie une nouvelle version de GPT pour les entreprises');
    $b = acsArticle('det-b', 'OpenAI devoile une nouvelle version de GPT destinee aux entreprises');
    $c = acsArticle('det-c', 'Amazon annonce un nouveau centre de donnees au Canada');

    $service = new ArticleClusteringService();
    $first = $service->cluster([$a, $b, $c]);
    $second = $service->cluster([$a, $b, $c]);

    $sizesFirst = array_map('count', $first['new_groups']);
    $sizesSecond = array_map('count', $second['new_groups']);

    expect($sizesFirst)->toBe($sizesSecond)
        ->and(count($first['singletons']))->toBe(count($second['singletons']))
        ->and($first['new_groups'])->toHaveCount(1)
        ->and($first['singletons'])->toHaveCount(1);
});

it('rattache un candidat en absorption quand une fiche comparative recente correspond deja au sujet', function () {
    $digestSource = acsSource('digest-src');
    $digest = NewsArticle::create([
        'news_source_id' => $digestSource->id,
        'title' => 'Meta lance un nouveau modele IA generative pour les entreprises',
        'guid' => 'guid-existing-digest',
        'url' => 'https://exemple.com/existing-digest',
        'description' => 'Digest existant',
        'pub_date' => now()->subHours(5),
        'is_published' => true,
        'is_comparative_digest' => true,
    ]);

    $late = acsArticle('late-arrival', 'Meta devoile un nouveau modele IA generative destine aux entreprises');

    $result = (new ArticleClusteringService())->cluster([$late]);

    expect($result['absorptions'])->toHaveCount(1)
        ->and($result['absorptions'][0]['digest']->id)->toBe($digest->id)
        ->and($result['new_groups'])->toBeEmpty()
        ->and($result['singletons'])->toBeEmpty();
});

it('n absorbe pas dans une fiche comparative hors de la fenetre window_hours', function () {
    config(['news.fusion.window_hours' => 36]);

    $digestSource = acsSource('old-digest-src');
    $oldDigest = NewsArticle::create([
        'news_source_id' => $digestSource->id,
        'title' => 'Meta lance un nouveau modele IA generative pour les entreprises',
        'guid' => 'guid-old-digest',
        'url' => 'https://exemple.com/old-digest',
        'description' => 'Ancien digest hors fenetre',
        'pub_date' => now()->subDays(5),
        'is_published' => true,
        'is_comparative_digest' => true,
    ]);
    // created_at n'est pas fillable (protection mass-assignment standard) : affectation directe
    // + save() pour simuler une fiche comparative créée hors de la fenêtre window_hours.
    $oldDigest->created_at = now()->subDays(5);
    $oldDigest->save();

    $late = acsArticle('too-late', 'Meta devoile un nouveau modele IA generative destine aux entreprises');

    $result = (new ArticleClusteringService())->cluster([$late]);

    expect($result['absorptions'])->toBeEmpty()
        ->and($result['singletons'])->toHaveCount(1);
});
