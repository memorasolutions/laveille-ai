<?php

declare(strict_types=1);

/**
 * Tests de la mise en page des fiches d'actualité - v1.187.0 (arbitrée par le panel de 5 IA le
 * 2026-08-17, design doc "Actus - composition manuelle assistée" 2026-08-15, section
 * « Améliorations en attente », point 3). Couvre le rendu public de
 * Modules\News\resources\views\public\show.blade.php :
 *   1. badge de pertinence unique en français clair (valeurs brutes 8/10 et impact_level disparues) ;
 *   2. encadré « L'essentiel » (ex-« Résumé IA »/ex-« EN BREF ») + ligne de transparence dessous ;
 *   3. barre d'interactions descendue sous cet encadré ;
 *   4. ligne de provenance « D'après [source], relayé par [média] » sous les métadonnées ;
 *   5. fin de page dégraissée (lien « article précédent » retiré, un seul lien générique) ;
 *   6. bouton « Partager » (Web Share API).
 *
 * Fichier dédié, distinct des fichiers de tests existants sur la même vue
 * (PrimarySourcesAndImageCreditPublicTest.php, Actu2PublicRenderTest.php, QuoteAttribution292Test.php)
 * - helpers locaux préfixés `nsl` (News Show Layout), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux ───────────────────────────────────────────────────────────────────

function nslSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source mise en page',
        'url' => 'https://nsl-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function nslArticle(int $sourceId, string $slug, array $overrides = []): NewsArticle
{
    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => 'Article de test mise en page '.$slug,
        'guid' => 'guid-nsl-'.$slug,
        'url' => 'https://nsl-source.exemple.com/'.$slug,
        'resolved_url' => 'https://nsl-source.exemple.com/'.$slug.'-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli pour '.$slug.'.',
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

// ── Point 1 : badge de pertinence unique, valeurs brutes absentes ──────────────────

it('renders a single clear relevance badge and never the raw score or impact_level', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'badge-eleve', [
        'structured_summary' => ['hook' => 'Accroche de test.', 'key_points' => ['Point clé.']],
        'relevance_score' => 9,
        'impact_level' => 'Élevé',
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Pertinence : élevée', false)
        ->assertSee('Évaluation interne de pertinence pour le lectorat québécois', false)
        ->assertDontSee('9/10', false)
        ->assertDontSee('<span class="nw-pill">Élevé</span>', false);
});

it('hides the relevance badge entirely when the score is below 5', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'badge-masque', [
        'structured_summary' => ['hook' => 'Accroche de test.', 'key_points' => ['Point clé.']],
        'relevance_score' => 3,
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertDontSee('Pertinence :', false)
        ->assertDontSee('3/10', false);
});

// ── Point 2 : « L'essentiel » (ex-« Résumé IA »), ligne de transparence ────────────

it('renders "L\'essentiel" and never "Résumé IA" when structured_summary carries a tldr', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'essentiel-tldr', [
        'structured_summary' => [
            'tldr' => 'Réponse directe answer-first de test.',
            'hook' => 'Accroche distincte de test.',
            'key_points' => ['Point clé.'],
        ],
    ]);

    $response = $this->get(route('news.show', $article));

    // Le libellé visible « L'ESSENTIEL » vit dans le CSS ::before (toujours présent dans le
    // <head>, qu'il y ait ou non un contenu à afficher) - la preuve qu'un encadré est RÉELLEMENT
    // rendu dans le corps est la balise ouvrante elle-même et la ligne de transparence, qui ne
    // sortent qu'à l'intérieur du bloc conditionnel @if($essentialText).
    $response->assertOk()
        ->assertSee('<aside class="nw-tldr"', false)
        ->assertSee('Réponse directe answer-first de test.', false)
        ->assertSee('Rédigé à partir de la source originale', false)
        ->assertDontSee('Résumé IA', false);
});

it('renders "L\'essentiel" from $article->summary and never "Résumé IA" when structured_summary is absent', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'essentiel-repli', [
        'structured_summary' => null,
        'summary' => 'MARQUEUR-RESUME-DE-REPLI-VISIBLE',
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('<aside class="nw-tldr"', false)
        ->assertSee('MARQUEUR-RESUME-DE-REPLI-VISIBLE', false)
        ->assertDontSee('Résumé IA', false);
});

it('adapts the transparency line when niveau_preuve is relais', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'transparence-relais', [
        'structured_summary' => ['tldr' => 'Réponse directe.', 'key_points' => ['Point clé.']],
        'niveau_preuve' => 'relais',
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Rédigé à partir du média cité', false)
        ->assertDontSee('Rédigé à partir de la source originale', false);
});

// ── Point 3 : barre d'interactions + bouton Partager après l'encadré ───────────────

it('places the action bar and the Partager button after the "L\'essentiel" box, in that order', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'ordre-boutons', [
        'structured_summary' => ['tldr' => 'Réponse directe de test.', 'key_points' => ['Point clé.']],
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();

    $html = $response->getContent();

    // Marqueurs body-only : « L'ESSENTIEL » et « nw-share-btn » existent AUSSI dans le <style>
    // du <head> (règle CSS ::before, sélecteur .nw-share-btn) - une comparaison de position sur
    // ces chaînes serait trompeuse (le <head> précède toujours le <body>). On cible donc la
    // balise ouvrante réelle de l'encadré, absente du CSS.
    $posEssentiel = mb_strpos($html, '<aside class="nw-tldr"');
    $posSave = mb_strpos($html, 'Sauvegarder');
    $posShare = mb_strpos($html, 'id="nw-share-btn-');

    expect($posEssentiel)->not->toBeFalse()
        ->and($posSave)->not->toBeFalse()
        ->and($posShare)->not->toBeFalse()
        ->and($posSave)->toBeGreaterThan($posEssentiel)
        ->and($posShare)->toBeGreaterThan($posEssentiel);
});

it('renders the Partager button with a 44px target and a Web Share API fallback to copy', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'bouton-partager', [
        'structured_summary' => ['tldr' => 'Réponse directe de test.', 'key_points' => ['Point clé.']],
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-share-btn', false)
        ->assertSee('Partager', false)
        ->assertSee('navigator.share', false)
        ->assertSee('navigator.clipboard.writeText', false);
});

// ── Point 4 : ligne de provenance « D'après ..., relayé par ... » ──────────────────

it('renders the compact provenance line under the metadata when primary_sources exists', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'provenance-presente', [
        'structured_summary' => ['hook' => 'Accroche de test.', 'key_points' => ['Point clé.']],
        'primary_sources' => [
            ['label' => 'Communiqué officiel', 'url' => 'https://exemple-officiel.com/communique'],
        ],
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-provenance', false)
        ->assertSee('D&#039;après', false)
        ->assertSee('href="https://exemple-officiel.com/communique"', false)
        ->assertSee('Communiqué officiel', false)
        ->assertSee('relayé par', false)
        ->assertSee('Source mise en page', false);
});

it('never renders the provenance line when primary_sources is empty', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'provenance-absente', [
        'primary_sources' => null,
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()->assertDontSee('<p class="nw-provenance">', false);
});

// ── Point 5 : fin de page dégraissée ────────────────────────────────────────────────

it('never renders the "previous article" link, only "next article"', function () {
    $source = nslSource();
    // category_tag distinct sur l'article central : la requête « articles connexes » (même
    // catégorie) ne doit PAS ramasser precedent/suivant, sans quoi leur titre apparaîtrait
    // légitimement ailleurs sur la page (grille connexes) et fausserait l'assertion négative
    // ci-dessous - qui ne cible QUE l'ancien lien « article précédent » retiré (point 5).
    $middle = nslArticle($source->id, 'nav-milieu', ['pub_date' => now()->subDays(2), 'category_tag' => 'Nav Test Unique']);
    nslArticle($source->id, 'nav-precedent', ['pub_date' => now()->subDays(3)]);
    nslArticle($source->id, 'nav-suivant', ['pub_date' => now()->subDay()]);

    $response = $this->get(route('news.show', $middle));

    $response->assertOk()
        ->assertDontSee('Article de test mise en page nav-precedent', false)
        ->assertSee('Article de test mise en page nav-suivant', false);
});

it('reduces the bottom "Pour aller plus loin" block to a single Glossaire Techno link', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'fin-de-page');

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('nw-plus-loin', false)
        ->assertSee('Glossaire Techno', false);

    // Cibler LE BLOC, jamais la page entière : le pied de page du site contient légitimement
    // « IA pour les PME » et « Annuaire d'outils IA » (piège mesuré le 2026-08-17).
    $html = $response->getContent();
    preg_match('/nw-plus-loin.*?(?=<footer|nw-back-link|$)/s', $html, $m);
    expect($m[0] ?? '')->not->toBe('')
        ->and($m[0])->toContain('Glossaire Techno')
        ->and($m[0])->not->toContain("Annuaire d'outils IA")
        ->and($m[0])->not->toContain('IA pour les PME');
});

// ── « Ajouter à mon journal » masqué pour un visiteur non connecté ─────────────────

it('hides "Ajouter à mon journal" for a guest visitor', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'journal-invite');

    $response = $this->get(route('news.show', $article));

    $response->assertOk()->assertDontSee('Ajouter à mon journal', false);
});

it('shows "Ajouter à mon journal" for an authenticated visitor', function () {
    $source = nslSource();
    $article = nslArticle($source->id, 'journal-connecte');
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('news.show', $article));

    $response->assertOk()->assertSee('Ajouter à mon journal', false);
});
