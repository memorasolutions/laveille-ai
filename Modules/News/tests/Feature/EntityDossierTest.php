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
