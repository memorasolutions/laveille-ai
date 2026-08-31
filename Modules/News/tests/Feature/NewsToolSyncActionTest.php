<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests Pest - NewsToolSyncAction::suggest() : détection automatique d'outils liés.
 *
 * Reproduit le bug rapporté (2026-07-04) : le bouton « Suggérer les outils détectés »
 * renvoyait TOUJOURS 0 résultat pour un article dont le nom de l'outil (ex. « Claude »)
 * n'apparaissait QUE dans structured_summary (hook/key_points/why_important), pas dans
 * title/description/summary - seuls champs scannés avant le correctif.
 *
 * Prouve aussi l'absence de régression : un mot français courant partagé avec un nom
 * d'outil TOOL_NEVER_AUTO (ex. « avec ») ne doit PAS déclencher de faux positif quand il
 * apparaît en minuscule en milieu de phrase (un outil publié « Avec » existe réellement
 * dans l'annuaire - confirmé en production).
 *
 * 2026-08-31 (mandat #2091) : la fixture du premier test citait « Claude Mythos Preview
 * d'Anthropic » - un nom de modèle entièrement fictif, sans rapport avec ce que ce test
 * vérifie (le balayage de structured_summary). Une fois la garde suffixe posée
 * (GlossaryLinkifier::TOOL_SUFFIX_SAFE_MODIFIERS), « Mythos » - mot inventé, absent de
 * tout vocabulaire réel de modificateurs produit - bloquait à tort la recapture de
 * « Claude ». Réduit à « Claude d'Anthropic » : preserve l'intention du test (aucune
 * majuscule ne suit directement le nom), sans dépendre d'un nom de produit qui n'existe pas.
 */

use Modules\Dictionary\Models\Term;
use Modules\Directory\Models\Tool;
use Modules\News\Actions\NewsToolSyncAction;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

// ── Helpers ───────────────────────────────────────────────────────────────────

function ntsaSource(): NewsSource
{
    return NewsSource::create([
        'name'     => 'Source NTSA',
        'url'      => 'https://ntsa-source.exemple.com/rss',
        'language' => 'fr',
        'active'   => true,
    ]);
}

function ntsaTool(string $name, string $slug): Tool
{
    return Tool::withoutEvents(fn () => Tool::create([
        'name'   => ['fr_CA' => $name, 'en' => $name],
        'slug'   => ['fr_CA' => $slug, 'en' => $slug],
        'status' => 'published',
        'pricing' => 'free',
    ]));
}

// ── Régression : outil mentionné UNIQUEMENT dans structured_summary ──────────

it('suggest() détecte un outil TOOL_NEVER_AUTO mentionné uniquement dans le résumé structuré IA', function () {
    // « claude » fait partie de GlossaryLinkifier::TOOL_NEVER_AUTO (mot aussi courant : prénom).
    $tool = ntsaTool('Claude', 'claude');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Explosion des vulnérabilités de sécurité grâce à l\'IA',
        'guid'           => 'guid-ntsa-claude',
        'url'            => 'https://exemple.com/ntsa-claude',
        // Aucune mention de l'outil dans title/description/summary - AVANT le correctif,
        // ce champ était le SEUL scanné par suggest() → 0 détection.
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => "En juin 2026, un record de vulnérabilités a été signalé, principalement grâce à l'utilisation de modèles IA comme Claude d'Anthropic.",
            'key_points' => [
                '1 500 vulnérabilités critiques signalées en juin 2026',
            ],
            'why_important' => "Les modèles d'IA, comme Claude d'Anthropic, permettent de détecter automatiquement les vulnérabilités.",
        ],
        'slug'         => 'article-ntsa-claude',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->toContain($tool->id);
});

// ── Anti-régression : mot français courant NE doit PAS produire de faux positif ──

it('suggest() ignore un outil TOOL_NEVER_AUTO homonyme d\'un mot français courant en minuscule', function () {
    // « avec » fait partie de TOOL_NEVER_AUTO ET un outil publié « Avec » existe réellement
    // dans l'annuaire (confirmé en prod, 2026-07-04) - risque de faux positif si le mot
    // français « avec » (extrêmement courant) était scanné sans distinction de casse.
    $tool = ntsaTool('Avec', 'avec');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une actualité qui n\'a rien à voir avec cet outil',
        'guid'           => 'guid-ntsa-avec',
        'url'            => 'https://exemple.com/ntsa-avec',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => "Cette technologie fonctionne avec plusieurs modules d'intelligence artificielle.",
            'key_points' => [
                'Un point clé quelconque, sans lien avec un outil précis.',
            ],
            'why_important' => "C'est important, mais pas à cause d'un outil nommé ainsi.",
        ],
        'slug'         => 'article-ntsa-avec',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->not->toContain($tool->id);
});

// ── Cas général : outil NON TOOL_NEVER_AUTO mentionné uniquement dans structured_summary ──

it('suggest() détecte un outil ordinaire mentionné uniquement dans le résumé structuré IA', function () {
    $tool = ntsaTool('Notion', 'notion');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une actualité sur la productivité en entreprise',
        'guid'           => 'guid-ntsa-notion',
        'url'            => 'https://exemple.com/ntsa-notion',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => 'Des équipes migrent leur documentation vers Notion pour centraliser leurs connaissances.',
            'key_points' => [],
            'why_important' => "L'adoption de Notion illustre une tendance de fond.",
        ],
        'slug'         => 'article-ntsa-notion',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->toContain($tool->id);
});

// ── Régression : outil homonyme d'une fiche de glossaire (masqué par la priorité de
// GlossaryLinkifier, cf. Modules/Core/app/Services/GlossaryLinkifier.php ~389-392) ──

it('suggest() détecte un outil dont le nom est aussi une fiche de glossaire', function () {
    // « Redacto » existe à la fois comme fiche de glossaire ET comme outil publié - avant le
    // correctif du 2026-08-27, le glossaire "prenait" ce nom en premier (GlossaryLinkifier::
    // loadTerms(), $takenLower) et l'outil homonyme n'était donc JAMAIS ajouté à $terms avec
    // type='tool' : suggest() ne pouvait plus jamais le proposer, quel que soit le texte.
    Term::create([
        'name'         => 'Redacto',
        'slug'         => 'redacto',
        'definition'   => 'Un terme de test qui partage son nom avec un outil de l\'annuaire.',
        'is_published' => true,
    ]);

    $tool = ntsaTool('Redacto', 'redacto-outil');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une actualité sur les outils de rédaction assistée',
        'guid'           => 'guid-ntsa-redacto',
        'url'            => 'https://exemple.com/ntsa-redacto',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => 'Plusieurs rédactions utilisent désormais Redacto pour accélérer la production.',
            'key_points' => [
                'Redacto automatise une partie de la relecture éditoriale.',
            ],
            'why_important' => 'Redacto illustre une tendance de fond dans les salles de rédaction.',
        ],
        'slug'         => 'article-ntsa-redacto',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->toContain($tool->id);
});

// ── Faille fermée le 2026-08-28 : un nom de TOOL_NEVER_RECAPTURE ne doit JAMAIS être
// recapturé par suggest(), même en majuscule initiale ──

it('suggest() ne suggère jamais l\'outil « Local » à partir de « Local AI » en tête de titre', function () {
    // Défaut mesuré en production le 2026-08-28 : un backfill d'auto-détection a créé 33 liens
    // outil↔actualité, dont 4 faux (12 %), tous par le même mécanisme - NewsToolSyncAction::
    // suggest() parcourait GlossaryLinkifier::TOOL_NEVER_AUTO et RECAPTURAIT tout nom présent
    // avec une majuscule initiale dans le texte, sans distinguer un début de titre d'une vraie
    // mention. « local » fait partie de TOOL_NEVER_AUTO (mot français courant), donc protégé de
    // l'auto-lien du corps de texte - mais « Local » (majuscule) en tête de titre d'actualité
    // ("Local AI...") était quand même recapturé et suggéré à tort.
    //
    // Ce test doit échouer (rouge) si GlossaryLinkifier::TOOL_NEVER_RECAPTURE est retiré du
    // filtre ->reject() de NewsToolSyncAction::suggest() - vérifié manuellement en retirant
    // temporairement cette ligne (voir rapport de la tâche).
    $tool = ntsaTool('Local', 'local');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Local AI transforme la manière dont les entreprises protègent leurs données',
        'guid'           => 'guid-ntsa-local-ai',
        'url'            => 'https://exemple.com/ntsa-local-ai',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => 'De plus en plus de PME choisissent un déploiement sur site plutôt que le nuage public.',
            'key_points' => [
                'Le traitement sur site réduit la latence et les coûts récurrents.',
            ],
            'why_important' => 'Cette tendance répond à des enjeux de souveraineté des données.',
        ],
        'slug'         => 'article-ntsa-local-ai',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->not->toContain($tool->id);
});

// ── Décision mesurée le 2026-08-31 : ne chercher QUE dans le texte RÉELLEMENT affiché au
// lecteur (titre optimisé, à défaut le titre + corps affiché), jamais dans le titre BRUT de
// la source - voir le docblock de NewsToolSyncAction::suggest() pour la mesure complète
// (350 fiches, 217 liens exploitables, 0,5 % de perte, 0 perte sur les vraies mentions). ──

it('suggest() ignore un outil mentionné SEULEMENT dans le titre brut de la source, absent du texte affiché', function () {
    // Le titre BRUT (source) mentionne l'outil ; ni le titre optimisé (réellement affiché au
    // lecteur), ni le corps affiché ne le mentionnent - un lecteur ne trouverait donc aucune
    // justification dans le texte sous ses yeux. Ce test doit échouer (rouge) si suggest() se
    // remet un jour à scanner le titre brut plutôt que le titre affiché.
    $tool = ntsaTool('Nimbolt', 'nimbolt');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Le moment Nimbolt de la robotique agricole, selon les analystes',
        'seo_title'      => 'La robotique agricole franchit une étape selon les analystes',
        'guid'           => 'guid-ntsa-titre-brut-seul',
        'url'            => 'https://exemple.com/ntsa-titre-brut-seul',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => "Des analystes constatent une progression rapide de l'automatisation agricole.",
            'key_points' => [
                "L'automatisation réduit les coûts de main-d'oeuvre saisonnière.",
            ],
            'why_important' => 'Cette évolution touche directement la souveraineté alimentaire.',
        ],
        'slug'         => 'article-ntsa-titre-brut-seul',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->not->toContain($tool->id);
});

// ── Test symétrique : une mention présente dans le texte affiché DOIT produire un lien ──

it('suggest() détecte un outil mentionné SEULEMENT dans le titre optimisé (affiché), absent du titre brut', function () {
    // Symétrique du test précédent : le titre optimisé (seo_title, réellement affiché au
    // lecteur) mentionne l'outil ; ni le titre brut ni le corps ne le mentionnent. Un lecteur
    // trouve la justification directement dans le titre sous ses yeux - la suggestion doit
    // rester produite. Preuve que seo_title est désormais scanné (il ne l'était pas avant).
    $tool = ntsaTool('Solandra', 'solandra');

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une avancée majeure saluée par les analystes du secteur',
        'seo_title'      => 'Solandra bouleverse la logistique agricole selon les analystes',
        'guid'           => 'guid-ntsa-titre-optimise-seul',
        'url'            => 'https://exemple.com/ntsa-titre-optimise-seul',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => "Des analystes constatent une progression rapide de l'automatisation agricole.",
            'key_points' => [
                "L'automatisation réduit les coûts de main-d'oeuvre saisonnière.",
            ],
            'why_important' => 'Cette évolution touche directement la souveraineté alimentaire.',
        ],
        'slug'         => 'article-ntsa-titre-optimise-seul',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    expect($suggested->all())->toContain($tool->id);
});
