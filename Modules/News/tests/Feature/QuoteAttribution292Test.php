<?php

declare(strict_types=1);

/**
 * Attribution complète de la citation verbatim (article 29.2 Loi sur le droit d'auteur
 * canadienne, L.R.C. 1985, ch. C-42) : la fiche d'actualité doit mentionner le nom du
 * journaliste ET le nom du média sous la citation, lorsque l'auteur figure dans la
 * source - sinon le repli conforme (média seul, sans ponctuation orpheline) s'applique.
 *
 * Couvre le composant réutilisable x-news::quote-attribution
 * (Modules/News/resources/views/components/quote-attribution.blade.php), unique appelant
 * du bloc <cite> sous la citation dans public/show.blade.php.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés qa292* pour éviter toute collision avec les autres
//    fichiers de tests News chargés dans le même process Pest). ────────────────────

function qa292Source(string $name = 'The Verge AI'): NewsSource
{
    return NewsSource::create([
        'name' => $name,
        'url' => 'https://qa292.exemple.com/rss',
        'language' => 'en',
        'active' => true,
    ]);
}

function qa292Article(int $sourceId, string $slug, array $overrides = []): NewsArticle
{
    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => 'Article de test attribution '.$slug,
        'guid' => 'guid-'.$slug,
        'url' => 'https://qa292.exemple.com/'.$slug,
        'resolved_url' => 'https://qa292.exemple.com/'.$slug.'-resolu',
        'description' => '',
        'summary' => 'Résumé de repli pour '.$slug,
        'structured_summary' => [
            'hook' => 'Accroche de test.',
            'quote' => 'Ceci est une citation verbatim tirée de la source, entre quinze et vingt-cinq mots pour respecter le format habituel du site.',
            'key_points' => ['Point clé de test.'],
        ],
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

it('affiche l\'attribution complète (journaliste, média, date, lien) quand l\'auteur est connu', function () {
    $source = qa292Source('The Verge AI');
    $article = qa292Article($source->id, 'avec-auteur', [
        'author' => 'Jane Doe',
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-quote-attribution', false)
        // Ordre imposé : journaliste puis média, sur le même segment.
        ->assertSee('Jane Doe, The Verge AI', false)
        // Lien vers l'article original (résolu en priorité sur l'URL brute).
        ->assertSee('href="https://qa292.exemple.com/avec-auteur-resolu"', false)
        ->assertSee('Voir l&#039;article original', false);

    // Aucune ponctuation orpheline devant le nom du journaliste.
    expect($response->getContent())->not->toContain('- , Jane Doe');
    expect($response->getContent())->not->toContain(' ,Jane Doe');
});

it('affiche le média seul, sans ponctuation orpheline, quand l\'auteur manque dans le flux', function () {
    $source = qa292Source('Le Monde Informatique');
    $article = qa292Article($source->id, 'sans-auteur', [
        'author' => null,
    ]);

    $response = $this->get(route('news.show', $article));
    $content = $response->getContent();

    $response->assertOk()
        ->assertSee('nw-quote-attribution', false)
        ->assertSee('Le Monde Informatique', false)
        ->assertSee('href="https://qa292.exemple.com/sans-auteur-resolu"', false);

    // Isoler le seul bloc d'attribution (le reste de la page - meta llm:keywords,
    // JSON-LD - contient légitimement des virgules autour du nom du média ailleurs).
    $citeStart = mb_strpos($content, '<cite class="nw-quote-attribution">');
    expect($citeStart)->not->toBeFalse();
    $citeEnd = mb_strpos($content, '</cite>', $citeStart);
    $citeExcerpt = mb_substr($content, $citeStart, $citeEnd - $citeStart + mb_strlen('</cite>'));

    // Repli conforme article 29.2 : la mention du média seule reste valide - donc
    // jamais de tiret/virgule/espace orphelin autour du nom quand l'auteur manque.
    expect($citeExcerpt)->not->toContain(', Le Monde Informatique');
    expect($citeExcerpt)->not->toContain('-  Le Monde Informatique'); // double espace = séparateur orphelin
    expect($citeExcerpt)->not->toContain(' · · '); // deux séparateurs collés = segment vide
    // Le nom du média apparaît immédiatement après le tiret d'introduction du <cite>.
    expect($citeExcerpt)->toContain('- Le Monde Informatique');
});

it('affiche toujours un lien fonctionnel vers l\'article original', function () {
    $source = qa292Source();
    $article = qa292Article($source->id, 'lien-original', [
        'author' => 'John Smith',
        'resolved_url' => null,
        'url' => 'https://qa292.exemple.com/url-brute-seulement',
    ]);

    $response = $this->get(route('news.show', $article));

    // Sans URL résolue, repli sur l'URL brute captée à l'ingestion (jamais de lien vide).
    $response->assertOk()
        ->assertSee('href="https://qa292.exemple.com/url-brute-seulement"', false)
        ->assertSee('target="_blank"', false)
        ->assertSee('rel="noopener"', false);
});

it('le composant x-news::quote-attribution est le SEUL appelant du bloc cite sous la citation', function () {
    $blade = file_get_contents(module_path('News', 'resources/views/public/show.blade.php'));

    expect($blade)->toContain('<x-news::quote-attribution :article="$article" />');
    // DRY : plus de balisage <cite> recopié en dur pour l'attribution de citation.
    expect($blade)->not->toMatch('/<cite>-\s*\{\{\s*\$article->source/');
});
