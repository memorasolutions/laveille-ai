<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * MESURÉ en production le 2026-08-30 (fiche 39486, terme « firmware ») : un même terme du
 * glossaire était auto-lié CINQ fois sur une seule fiche actualité, dont DEUX fois dans la
 * même section « À retenir ». Cause confirmée par lecture du code (pas une hypothèse) : les 15
 * appels `@glossarize()` de Modules\News\resources\views\public\show.blade.php ne passaient
 * AUCUNE option - donc `max_occ` valait par défaut GlossaryLinkifier::MAX_OCCURRENCES_PER_TERM
 * (10), et `per_section` valait `false`. Comme `self::$seenThisRequest` est un compteur STATIQUE
 * partagé entre TOUS les appels `@glossarize()` de la même requête HTTP (intentionnel - voir son
 * docblock, « on veut first-occurrence GLOBAL »), un terme cité dans le hook, deux fois dans
 * key_points, dans why_important ET dans la citation s'est retrouvé lié 5 fois avant d'atteindre
 * le plafond de 10.
 *
 * Mesure indépendante sur 73 fiches réelles de production (échantillon réparti, sitemap.xml,
 * 2026-08-30) : 11 liens/page en médiane (31 au maximum), et 61/73 fiches (83,6 %) portaient au
 * moins un terme répété 2 fois ou plus DANS LA MÊME section - jusqu'à 6 fois pour un seul terme
 * dans une seule section dans le pire cas. Le mode « per_section » (tâche #1350) n'est PAS
 * applicable ici même s'il était passé : chaque `@glossarize()` de ce fichier reçoit un fragment
 * de texte SANS `<h2>` (les titres de section sont rendus par Blade, hors de l'appel), donc
 * `$currentSection` resterait à 0 pour les 15 appels - un `per_section => true` y serait inerte.
 * Correctif retenu : `max_occ => 1`, MÊME mécanisme déjà en place pour le glossaire
 * (Modules/Dictionary/resources/views/public/show.blade.php, tâche #300, « éviter saturation
 * visuelle ») et pour le blog (Modules/FrontTheme/resources/views/blog/show.blade.php, tâche
 * #1350) - aucune abstraction nouvelle, la même option déjà éprouvée ailleurs.
 *
 * Reproduit la distribution EXACTE de la fiche 39486 (1 avant le premier h2, 2 dans « À
 * retenir », 1 dans « Pourquoi ça compte », 1 dans « Citation ») avec un terme de test créé en
 * base pour ne dépendre d'aucune donnée de production.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Services\GlossaryLinkifier;
use Modules\Dictionary\Models\Term;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixe Gld = Glossary Link Density) ────────────────────────────

function gldTerm(): Term
{
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $slug = 'firmware-test-'.uniqid();

    return Term::create([
        'name' => [$locale => 'firmware', 'fr' => 'firmware'],
        'slug' => [$locale => $slug, 'fr' => $slug],
        'definition' => [$locale => 'Logiciel embarqué dans un composant matériel.', 'fr' => 'Logiciel embarqué dans un composant matériel.'],
        'is_published' => true,
        'match_strategy' => 'loose',
    ]);
}

function gldSource(): NewsSource
{
    return NewsSource::create([
        'name' => 'Source densité glossaire',
        'url' => 'https://gld-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function gldArticle(int $sourceId): NewsArticle
{
    // Distribution IDENTIQUE à la fiche 39486 mesurée en production : le terme apparaît
    // 1x avant le premier h2 (hook -> encadré "L'essentiel"), 2x dans "À retenir", 1x dans
    // "Pourquoi ça compte", 1x dans "Citation" = 5 occurrences du même terme sur la page.
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title' => 'Article test densité glossaire',
        'guid' => 'guid-gld-'.uniqid(),
        'url' => 'https://gld-source.exemple.com/article',
        'resolved_url' => 'https://gld-source.exemple.com/article-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli.',
        'slug' => 'densite-glossaire-'.uniqid(),
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
        'structured_summary' => [
            'composed' => true,
            'hook' => 'Le nouveau firmware corrige plusieurs failles critiques.',
            'key_points' => [
                'Le firmware est désormais signé numériquement.',
                'Le firmware se déploie automatiquement en arrière-plan.',
            ],
            'why_important' => 'Sans ce firmware, les appareils restent vulnérables.',
            'quote' => ['text' => 'Ce firmware est le plus important déployé cette année.', 'author' => 'Porte-parole test'],
        ],
    ]);
}

beforeEach(function () {
    GlossaryLinkifier::resetState();
    GlossaryLinkifier::flushCache();
});

it('ne lie le même terme QU\'UNE SEULE FOIS sur toute la fiche actualité, même cité dans 4 sections distinctes', function () {
    $source = gldSource();
    $term = gldTerm();
    $article = gldArticle($source->id);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();

    $html = $response->getContent();

    // Les 5 occurrences textuelles de "firmware" doivent toutes rester visibles (aucune perte
    // de contenu - seul le NOMBRE DE LIENS doit changer, jamais le texte).
    expect(substr_count(mb_strtolower($html), 'firmware'))->toBeGreaterThanOrEqual(5);

    // Mais un seul de ces 5 doit être encapsulé dans un lien glossaire : c'est le seul terme
    // créé dans cette fiche de test, donc TOUT <a class="glossary-link"> présent le cible.
    $linkCount = substr_count($html, 'class="glossary-link"');
    expect($linkCount)->toBe(1)
        ->and($linkCount)->not->toBeGreaterThan(1);
});

it('garde EXACTEMENT un lien par terme quand DEUX termes distincts sont chacun cités plusieurs fois (le plafond ne doit jamais tomber à zéro)', function () {
    config(['app.locale' => 'fr_CA']);
    $locale = app()->getLocale();
    $source = gldSource();

    // Deux termes distincts, chacun cité 2 fois dans des sections différentes - contrôle que
    // max_occ => 1 s'applique par TERME (jamais un seul lien pour toute la page) et que le
    // premier lien légitime de CHAQUE terme survit bien (ni 0, ni 2).
    $slugFirmware = 'firmware-multi-'.uniqid();
    Term::create([
        'name' => [$locale => 'firmware', 'fr' => 'firmware'],
        'slug' => [$locale => $slugFirmware, 'fr' => $slugFirmware],
        'definition' => [$locale => 'Logiciel embarqué.', 'fr' => 'Logiciel embarqué.'],
        'is_published' => true,
        'match_strategy' => 'loose',
    ]);
    $slugRouteur = 'routeur-multi-'.uniqid();
    Term::create([
        'name' => [$locale => 'routeur', 'fr' => 'routeur'],
        'slug' => [$locale => $slugRouteur, 'fr' => $slugRouteur],
        'definition' => [$locale => 'Équipement réseau.', 'fr' => 'Équipement réseau.'],
        'is_published' => true,
        'match_strategy' => 'loose',
    ]);

    $article = NewsArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Article test deux termes',
        'guid' => 'guid-gld-multi-'.uniqid(),
        'url' => 'https://gld-source.exemple.com/article-multi',
        'resolved_url' => 'https://gld-source.exemple.com/article-multi-resolu',
        'description' => '',
        'summary' => 'Résumé court de repli.',
        'slug' => 'densite-glossaire-multi-'.uniqid(),
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
        'structured_summary' => [
            'composed' => true,
            'hook' => 'Le nouveau firmware du routeur corrige plusieurs failles.',
            'key_points' => [
                'Le firmware se déploie automatiquement.',
                'Le routeur redémarre seul après la mise à jour.',
            ],
            'why_important' => 'Sans ce firmware, le routeur reste vulnérable.',
        ],
    ]);

    $response = $this->get(route('news.show', $article));
    $response->assertOk();
    $html = $response->getContent();

    expect(substr_count($html, 'href="/glossaire/'.$slugFirmware.'"'))->toBe(1)
        ->and(substr_count($html, 'href="/glossaire/'.$slugRouteur.'"'))->toBe(1)
        ->and(substr_count($html, 'class="glossary-link"'))->toBe(2);
});
