<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ce que ces tests protègent : le seuil de 5 fiches. Sans lui, le regroupement fabriquerait des
 * pages de deux actualités - exactement la page mince que le chantier AdSense cherche à éliminer.
 * On remplacerait un problème par le même problème, avec une URL de plus.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsArticleEntity;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

function dossierSource(): NewsSource
{
    // firstOrCreate, et non create : un test qui monte DEUX dossiers appelle ce helper deux fois,
    // et l'URL de source porte une contrainte d'unicité.
    return NewsSource::firstOrCreate(
        ['url' => 'https://ds.exemple.com/rss'],
        ['name' => 'Source dossiers', 'language' => 'fr', 'active' => true],
    );
}

function dossierFiche(int $src, string $titre, bool $publiee = true, bool $retiree = false): NewsArticle
{
    static $i = 0;
    $i++;

    $a = NewsArticle::create([
        'news_source_id' => $src, 'title' => $titre, 'guid' => "g-ds-{$i}-".uniqid(),
        'url' => "https://exemple.com/ds-{$i}", 'slug' => "ds-{$i}-".uniqid(),
        'pub_date' => now()->subDays(20), 'is_published' => $publiee, 'seo_status' => 'index',
        // Sans résumé, la fiche répond 410 (hasExploitableSummary) : le contrôle du lien de
        // dossier ne verrait jamais la page.
        'summary' => "Résumé de test pour {$titre}, assez fourni pour que la fiche soit servie.",
    ]);

    if ($retiree) {
        $a->forceFill(['retired_at' => now()])->save();
    }

    return $a;
}

function dossierMarquer(NewsArticle $a, string $slug, string $label): void
{
    NewsArticleEntity::create([
        'news_article_id' => $a->id, 'entity_slug' => $slug, 'entity_label' => $label,
    ]);
}

function dossierLot(int $nombre, string $slug = 'openai', string $label = 'OpenAI'): void
{
    $src = dossierSource()->id;

    for ($i = 1; $i <= $nombre; $i++) {
        dossierMarquer(dossierFiche($src, "Fiche {$slug} {$i}"), $slug, $label);
    }
}

it('sert le dossier quand cinq actualités ou plus le composent', function () {
    dossierLot(5);

    $this->get(route('news.dossier', 'openai'))->assertStatus(200)->assertSee('OpenAI');
});

// LE test qui porte la décision : un dossier trop maigre ne doit JAMAIS exister comme page.
it('refuse un dossier de moins de cinq actualités', function () {
    dossierLot(4);

    $this->get(route('news.dossier', 'openai'))->assertStatus(404);
});

// Un slug inventé ne doit pas créer une page vide indexable par un moteur.
it('refuse une entité inconnue', function () {
    $this->get(route('news.dossier', 'entite-qui-nexiste-pas'))->assertStatus(404);
});

// Un brouillon marqué compterait dans le total et servirait un dossier qui n'a pas la matière.
it('ne compte pas les actualités non publiées', function () {
    $src = dossierSource()->id;

    for ($i = 1; $i <= 5; $i++) {
        dossierMarquer(dossierFiche($src, "Fiche non publiée {$i}", $i > 2), 'openai', 'OpenAI');
    }

    $this->get(route('news.dossier', 'openai'))->assertStatus(404);
});

// Une fiche retirée (410) est sortie de toute surface publique : la compter la ferait revenir.
it('ne compte pas les actualités retirées', function () {
    $src = dossierSource()->id;

    for ($i = 1; $i <= 5; $i++) {
        dossierMarquer(dossierFiche($src, "Fiche retirée {$i}", true, $i === 3), 'openai', 'OpenAI');
    }

    $this->get(route('news.dossier', 'openai'))->assertStatus(404);
});

// L'index ne doit pas annoncer un dossier que la page elle-même refuserait de servir.
it("liste seulement les dossiers assez fournis sur l'index", function () {
    dossierLot(5, 'openai', 'OpenAI');
    dossierLot(3, 'mistral', 'Mistral AI');

    $this->get(route('news.dossiers'))->assertStatus(200)
        ->assertSee('OpenAI')->assertDontSee('Mistral AI');
});

// Sans les voisins, le lecteur arrivé par un moteur trouve une impasse au bas de la page.
it('propose les dossiers voisins', function () {
    $src = dossierSource()->id;

    for ($i = 1; $i <= 5; $i++) {
        $a = dossierFiche($src, "Fiche croisée {$i}");
        dossierMarquer($a, 'openai', 'OpenAI');
        dossierMarquer($a, 'microsoft', 'Microsoft');
    }

    $this->get(route('news.dossier', 'openai'))->assertStatus(200)->assertSee('Microsoft');
});

/**
 * Mesuré en production le 2026-09-14 : sur 43 entités au-dessus du seuil, 8 étaient des MÉDIAS
 * (TechCrunch 21 fiches, The Verge 14, Wired 12...). Un dossier « TechCrunch » réunirait des
 * actualités dont le seul point commun est le relayeur - la page creuse que ce chantier veut
 * supprimer, avec une URL indexable de plus.
 */
it('refuse un dossier qui porte le nom d\'un média', function () {
    dossierLot(6, 'techcrunch', 'TechCrunch');

    $this->get(route('news.dossier', 'techcrunch'))->assertStatus(404);
});

it('n\'annonce pas un média sur l\'index des dossiers', function () {
    dossierLot(6, 'techcrunch', 'TechCrunch');
    dossierLot(5, 'openai', 'OpenAI');

    $this->get(route('news.dossiers'))->assertStatus(200)
        ->assertSee('OpenAI')->assertDontSee('TechCrunch');
});

// Le nom d'un flux n'est jamais un sujet non plus, même absent de la liste éditoriale : c'est
// la moitié dynamique de l'exclusion, celle qui couvre les flux ajoutés après coup.
it('refuse un dossier qui porte le nom d\'un flux de veille', function () {
    NewsSource::create([
        'name' => 'Gazette Synthétique', 'url' => 'https://gazette.exemple.com/rss',
        'language' => 'fr', 'active' => true,
    ]);
    dossierLot(6, 'gazette-synthetique', 'Gazette Synthétique');

    $this->get(route('news.dossier', 'gazette-synthetique'))->assertStatus(404);
});

/**
 * Le maillage depuis la fiche est ce qui porte le bénéfice réel : 311 fiches qui pointent vers
 * les dossiers, plutôt que deux pages atteignables par le seul plan de site. Sans ce lien, les
 * dossiers existent sans que personne n'y arrive - le défaut que ce chantier vient de corriger
 * ailleurs, rejoué une fois de plus.
 */
it('affiche le lien vers le dossier depuis une fiche qui en fait partie', function () {
    dossierLot(5, 'openai', 'OpenAI');

    $fiche = NewsArticleEntity::query()->where('entity_slug', 'openai')->first()->newsArticle;

    $this->get(route('news.show', $fiche->slug))->assertStatus(200)->assertSee('Tout sur OpenAI');
});

// Un lien vers un dossier que la page refuserait de servir serait un lien vers un 404.
it("n'affiche aucun lien de dossier quand l'entité n'atteint pas le seuil", function () {
    dossierLot(3, 'openai', 'OpenAI');

    $fiche = NewsArticleEntity::query()->where('entity_slug', 'openai')->first()->newsArticle;

    $this->get(route('news.show', $fiche->slug))->assertStatus(200)->assertDontSee('Tout sur OpenAI');
});

// Trois mois commencent par une voyelle : la formule à trous la plus naturelle écrit « de avril »
// une fois sur quatre, et la faute est sous les yeux de tous les lecteurs.
it('élide la période quand le mois commence par une voyelle', function () {
    $src = dossierSource()->id;

    for ($i = 1; $i <= 5; $i++) {
        $a = dossierFiche($src, "Fiche période {$i}");
        $a->forceFill(['pub_date' => now()->setDate(2026, 4, 10)])->save();
        dossierMarquer($a, 'openai', 'OpenAI');
    }

    // Blade échappe l'apostrophe en &#039; : on contrôle donc la faute elle-même, qui est ce
    // que le lecteur verrait, plutôt qu'une forme encodée qui dépend du moteur de rendu.
    $this->get(route('news.dossier', 'openai'))->assertStatus(200)
        ->assertSee('avril 2026')->assertDontSee('de avril 2026');
});

// ── Fil d'Ariane et BreadcrumbList JSON-LD (aucun des 13 tests ci-dessus ne les couvrait) ──
//
// Ce que ces tests protègent : le fil d'Ariane visuel (nav Modules/FrontTheme partials/breadcrumb)
// et le JSON-LD BreadcrumbList qui l'accompagne (Modules/SEO JsonLdService::breadcrumbs) sont
// deux rendus distincts du même $breadcrumbItems déclaré dans dossiers-index.blade.php et
// dossier.blade.php. Rien ne garantissait qu'ils restent synchronisés ni que les liens de retour
// pointent vers les bonnes routes - une régression sur l'un des deux passerait inaperçue tant
// qu'elle ne casse ni le statut HTTP ni le texte affiché en page, ce qu'aucun des 13 tests plus
// haut ne contrôle.

/**
 * Extrait tous les blocs <script type="application/ld+json">...</script> d'un HTML rendu et
 * les json_decode() en tableaux associatifs. Même mécanique que MachineMarkupEscapingTest
 * (Modules/News), préfixée "dossier" plutôt que "mme" pour ne jamais redéclarer une fonction
 * globale du même nom entre deux fichiers de test chargés dans la même exécution Pest.
 *
 * @return array<int, array<string, mixed>>
 */
function dossierJsonLdBlocks(string $html): array
{
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);
    $blocks = [];
    foreach ($matches[1] as $raw) {
        $decoded = json_decode($raw, true);
        expect(json_last_error())->toBe(JSON_ERROR_NONE, 'JSON-LD invalide : '.json_last_error_msg()." - fragment : {$raw}");
        $blocks[] = $decoded;
    }

    return $blocks;
}

// Retrouve, parmi une liste de schémas JSON-LD, le premier dont '@type' correspond.
function dossierFindSchema(array $blocks, string $type): ?array
{
    foreach ($blocks as $block) {
        $candidates = array_is_list($block) ? $block : [$block];
        foreach ($candidates as $schema) {
            if (($schema['@type'] ?? null) === $type) {
                return $schema;
            }
        }
    }

    return null;
}

// Extrait le seul <nav aria-label="..."> de la page (le fil d'Ariane visuel), pour contrôler
// ses liens sans dépendre du reste du gabarit qui répète parfois les mêmes mots ailleurs.
function dossierBreadcrumbNav(string $html): string
{
    preg_match('#<nav aria-label="[^"]*">(.*?)</nav>#s', $html, $m);
    expect($m)->toHaveCount(2, 'Aucun <nav> de fil d\'Ariane trouvé dans le HTML rendu');

    return $m[1];
}

it("le fil d'Ariane de l'index des dossiers rend Accueil > Actualités > Dossiers thématiques, avec un BreadcrumbList JSON-LD à 3 éléments", function () {
    dossierLot(5, 'openai', 'OpenAI');

    $response = $this->get(route('news.dossiers'));
    $response->assertStatus(200);
    $html = $response->getContent();

    $nav = dossierBreadcrumbNav($html);

    // Accueil et Actualités sont des liens de retour ; Dossiers thématiques est le maillon
    // courant, donc un <span>, jamais un lien vers lui-même.
    expect($nav)->toContain('href="'.route('home').'"');
    expect($nav)->toContain('href="'.route('news.index').'"');
    expect($nav)->toContain('>'.__('Actualités').'</a>');
    expect($nav)->toContain('<span>'.__('Dossiers thématiques').'</span>');

    $blocks = dossierJsonLdBlocks($html);
    $breadcrumb = dossierFindSchema($blocks, 'BreadcrumbList');
    expect($breadcrumb)->not->toBeNull('Aucun schéma BreadcrumbList trouvé dans le JSON-LD rendu');

    $items = $breadcrumb['itemListElement'];
    expect($items)->toHaveCount(3, "L'index des dossiers doit porter Accueil + Actualités + Dossiers thématiques, jamais plus ni moins.");
    expect(array_column($items, 'name'))->toBe([__('Accueil'), __('Actualités'), __('Dossiers thématiques')]);
    expect($items[1]['item'])->toBe(route('news.index'));
});

it("le fil d'Ariane d'un dossier rend 4 niveaux avec deux liens de retour (Actualités et Dossiers thématiques), et un BreadcrumbList JSON-LD à 4 éléments", function () {
    dossierLot(5, 'openai', 'OpenAI');

    $response = $this->get(route('news.dossier', 'openai'));
    $response->assertStatus(200);
    $html = $response->getContent();

    $nav = dossierBreadcrumbNav($html);

    // Trois liens de retour visuels : Accueil, Actualités, Dossiers thématiques. Seul le
    // dernier maillon (le dossier courant) reste un <span> non cliquable.
    expect($nav)->toContain('href="'.route('home').'"');
    expect($nav)->toContain('href="'.route('news.index').'"');
    expect($nav)->toContain('href="'.route('news.dossiers').'"');
    expect($nav)->toContain('>'.__('Actualités').'</a>');
    expect($nav)->toContain('>'.__('Dossiers thématiques').'</a>');
    expect($nav)->toContain('<span>'.__('Tout sur :entite', ['entite' => 'OpenAI']).'</span>');

    $blocks = dossierJsonLdBlocks($html);
    $breadcrumb = dossierFindSchema($blocks, 'BreadcrumbList');
    expect($breadcrumb)->not->toBeNull('Aucun schéma BreadcrumbList trouvé dans le JSON-LD rendu');

    $items = $breadcrumb['itemListElement'];
    expect($items)->toHaveCount(4, "La page de dossier doit porter Accueil + Actualités + Dossiers thématiques + le dossier courant, jamais plus ni moins.");
    expect(array_column($items, 'name'))->toBe([
        __('Accueil'), __('Actualités'), __('Dossiers thématiques'), __('Tout sur :entite', ['entite' => 'OpenAI']),
    ]);
    expect($items[1]['item'])->toBe(route('news.index'));
    expect($items[2]['item'])->toBe(route('news.dossiers'));
});
