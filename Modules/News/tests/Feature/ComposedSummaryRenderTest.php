<?php

declare(strict_types=1);

/**
 * Richesse v1.188.0 - structure fixe composée (panel de 5 IA, 2026-08-17 soir, design doc "Actus
 * - composition manuelle assistée" 2026-08-15, section "Richesse v1.188.0"). Couvre le rendu
 * public de Modules\News\resources\views\public\show.blade.php pour une fiche COMPOSÉE
 * (structured_summary portant le marqueur `composed: true`) :
 *   - l'ordre FIXE et les libellés publics EXACTS des 9 sections ;
 *   - le rendu des trois blocs nouveaux (À retenir/key_points, Repères datés/reperes_dates) et du
 *     bloc rendu visible pour la première fois (Action concrète/action_concrete, jusqu'ici
 *     seulement dans le texte de partage) ;
 *   - le droit d'omission silencieuse : une section absente ne laisse aucun résidu (ni titre
 *     orphelin, ni espace vide) ;
 *   - la non-régression d'une fiche MACHINE historique (sans composed:true), qui rend exactement
 *     comme avant ce mandat (anciens libellés, ancien format de citation).
 *
 * Fichier dédié, distinct de NewsShowLayoutTest.php/PrimarySourcesAndImageCreditPublicTest.php/
 * Actu2PublicRenderTest.php (autres blocs de la même vue) - helpers locaux préfixés `csr`
 * (Composed Summary Render), autonomes.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Csr pour éviter tout conflit inter-fichiers) ───────────────

function csrSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source rendu composé',
        'url' => 'https://csr-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function csrArticle(int $sourceId, string $slug, array $overrides = []): NewsArticle
{
    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => 'Article rendu composé '.$slug,
        'guid' => 'guid-csr-'.$slug,
        'url' => 'https://csr-source.exemple.com/'.$slug,
        'resolved_url' => 'https://csr-source.exemple.com/'.$slug.'-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli pour '.$slug.'.',
        'slug' => $slug,
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

function csrFullComposedSummary(): array
{
    return [
        'composed' => true,
        'hook' => 'MARQUEUR-ESSENTIEL une accroche autonome de test.',
        'key_points' => ['MARQUEUR-POINT-UN, attribué.', 'MARQUEUR-POINT-DEUX, attribué.'],
        'why_important' => 'MARQUEUR-POURQUOI cette nouvelle compte.',
        'key_number' => 'MARQUEUR-CHIFFRE : 12 millions $, 2026-08-17, ministère.',
        'quote' => ['text' => 'MARQUEUR-CITATION, une phrase.', 'author' => 'MARQUEUR-AUTEUR, porte-parole'],
        'angle_qc_ca' => 'MARQUEUR-QUEBEC, ce que ça change ici.',
        'action_concrete' => 'MARQUEUR-ACTION : consultez le site officiel.',
        'reperes_dates' => [
            ['date' => '2026-06-01', 'texte' => 'MARQUEUR-REPERE-UN.'],
            ['date' => '2026-08-17', 'texte' => 'MARQUEUR-REPERE-DEUX.', 'url' => 'https://exemple.com/repere'],
        ],
    ];
}

// ── Ordre fixe et libellés publics exacts ───────────────────────────────────────────

it('renders the nine composed sections in the exact fixed order with their exact public labels', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'ordre-fixe', [
        'structured_summary' => csrFullComposedSummary(),
        'primary_sources' => [['label' => 'Source primaire de test', 'url' => 'https://exemple.com/primaire']],
    ]);

    $response = $this->get(route('news.show', $article));

    // Piège connu du projet (mémoire "commentaire CSS = servi au navigateur") : le libellé
    // "L'ESSENTIEL" est un `content:` CSS (.nw-tldr::before) TOUJOURS présent dans le <style> de
    // la page, qu'il y ait ou non un encadré "L'essentiel" rendu - l'utiliser dans
    // assertSeeInOrder donnerait un faux positif. On vise donc le CONTENU réellement rendu de la
    // section 1 (le hook composé), jamais le libellé CSS.
    $response->assertOk()->assertSeeInOrder([
        'MARQUEUR-ESSENTIEL une accroche autonome de test.',
        'À retenir',
        'Pourquoi ça compte',
        'Chiffre-clé',
        'Citation',
        'Ce que ça change au Québec',
        'Action concrète',
        'Repères datés',
        'Sources',
    ], false);
});

it('renders the composed hook inside the shared "L\'essentiel" box (section 1, unchanged mechanism)', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'essentiel-hook', [
        'structured_summary' => csrFullComposedSummary(),
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()->assertSee('MARQUEUR-ESSENTIEL une accroche autonome de test.', false);
});

// ── Blocs nouveaux / nouvellement visibles ──────────────────────────────────────────

it('renders key_points as the "À retenir" bulleted list', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'a-retenir', [
        'structured_summary' => csrFullComposedSummary(),
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('À retenir', false)
        ->assertSee('MARQUEUR-POINT-UN, attribué.', false)
        ->assertSee('MARQUEUR-POINT-DEUX, attribué.', false)
        // L'ancien libellé ne doit jamais apparaître pour une fiche composée.
        ->assertDontSee('Que faut-il retenir ?', false);
});

it('renders reperes_dates as the "Repères datés" list, with a link only when a url is provided', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'reperes-dates', [
        'structured_summary' => csrFullComposedSummary(),
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Repères datés', false)
        ->assertSee('MARQUEUR-REPERE-UN.', false)
        ->assertSee('MARQUEUR-REPERE-DEUX.', false)
        ->assertSee('href="https://exemple.com/repere"', false);
});

it('renders action_concrete on the fiche itself (bonus Codex - previously share-text only)', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'action-concrete', [
        'structured_summary' => csrFullComposedSummary(),
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Action concrète', false)
        ->assertSee('MARQUEUR-ACTION : consultez le site officiel.', false);
});

it('renders key_number under the "Chiffre-clé" heading (previously never rendered on the fiche)', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'chiffre-cle', [
        'structured_summary' => csrFullComposedSummary(),
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Chiffre-clé', false)
        ->assertSee('MARQUEUR-CHIFFRE : 12 millions $, 2026-08-17, ministère.', false);
});

it('renders the composed quote {text, author} under a "Citation" heading, author included', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'citation-composee', [
        'structured_summary' => csrFullComposedSummary(),
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Citation', false)
        ->assertSee('MARQUEUR-CITATION, une phrase.', false)
        ->assertSee('MARQUEUR-AUTEUR, porte-parole', false);
});

it('renders angle_qc_ca under the "Ce que ça change au Québec" heading (previously headingless)', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'quebec-heading', [
        'structured_summary' => csrFullComposedSummary(),
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertSee('Ce que ça change au Québec', false)
        ->assertSee('MARQUEUR-QUEBEC, ce que ça change ici.', false);
});

// ── Droit d'omission silencieuse : aucun résidu pour une section absente ────────────

it('omits a composed section silently when its key is absent - no orphan heading, no empty block', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'sections-partielles', [
        'structured_summary' => [
            'composed' => true,
            'hook' => 'Seule l\'essentiel est fourni.',
        ],
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        ->assertDontSee('À retenir', false)
        ->assertDontSee('Pourquoi ça compte', false)
        ->assertDontSee('Chiffre-clé', false)
        ->assertDontSee('Citation', false)
        ->assertDontSee('Ce que ça change au Québec', false)
        ->assertDontSee('Action concrète', false)
        ->assertDontSee('Repères datés', false);
});

// ── Non-régression : une fiche MACHINE historique (sans composed:true) rend comme avant ──

it('renders a historical machine fiche (no composed marker) exactly as before this mandate', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'fiche-machine-historique', [
        'structured_summary' => [
            'hook' => 'Accroche machine historique.',
            'key_points' => ['Point clé machine.'],
            'why_important' => 'Pourquoi machine.',
            'quote' => 'Citation machine (chaîne, pas un objet).',
        ],
    ]);

    $response = $this->get(route('news.show', $article));

    $response->assertOk()
        // Anciens libellés inchangés.
        ->assertSee('Que faut-il retenir ?', false)
        ->assertSee('Pourquoi cette nouvelle compte-t-elle ?', false)
        ->assertSee('Point clé machine.', false)
        ->assertSee('Citation machine (chaîne, pas un objet).', false)
        // Les nouveaux libellés composés n'apparaissent jamais sur une fiche machine.
        ->assertDontSee('À retenir', false)
        ->assertDontSee('Pourquoi ça compte', false)
        ->assertDontSee('Ce que ça change au Québec', false);
});

it('never mixes the composed and machine quote renderers on the same fiche', function () {
    $source = csrSource();
    $article = csrArticle($source->id, 'quote-machine-seule', [
        'structured_summary' => [
            'hook' => 'Accroche.',
            'quote' => 'Citation machine (chaîne).',
        ],
    ]);

    $response = $this->get(route('news.show', $article));

    // Fiche non composée : pas de heading "Citation" (réservé aux fiches composées),
    // mais la citation machine (bloc historique, sans heading) reste visible.
    $response->assertOk()
        ->assertSee('Citation machine (chaîne).', false)
        ->assertDontSee('<h2 class="nw-section-heading">Citation</h2>', false);
});
