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

use Illuminate\Support\Facades\DB;
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

// ── Ticket #2524 (2026-09-13, socle glossaire↔actualités) : suggest() attache aussi
// automatiquement (source=auto) les fiches de GLOSSAIRE détectées, dans news_article_term -
// en PARALLÈLE de la détection d'outils ci-dessus, qui doit rester STRICTEMENT inchangée. ──

it('suggest() attache automatiquement (source=auto) un terme de glossaire PUBLIÉ mentionné dans le texte affiché', function () {
    $term = Term::create([
        'name'         => 'Vecteur Latent NTSA',
        'slug'         => 'vecteur-latent-ntsa',
        'definition'   => "Une représentation numérique compacte utilisée par les modèles d'IA.",
        'is_published' => true,
    ]);

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une actualité qui explique le concept de Vecteur Latent NTSA',
        'guid'           => 'guid-ntsa-terme-vecteur',
        'url'            => 'https://exemple.com/ntsa-terme-vecteur',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => 'Les chercheurs détaillent comment le Vecteur Latent NTSA structure les données.',
            'key_points' => [],
            'why_important' => 'Ce concept explique une part du fonctionnement des modèles actuels.',
        ],
        'slug'         => 'article-ntsa-terme-vecteur',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    app(NewsToolSyncAction::class)->suggest($article);

    $pivot = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->source)->toBe('auto');
});

it('suggest() ne lie jamais un terme de glossaire NON publié (témoin négatif)', function () {
    $term = Term::create([
        'name'         => 'Brouillon Glossaire NTSA',
        'slug'         => 'brouillon-glossaire-ntsa',
        'definition'   => 'Une fiche de glossaire encore en brouillon.',
        'is_published' => false,
    ]);

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une actualité qui mentionne Brouillon Glossaire NTSA',
        'guid'           => 'guid-ntsa-terme-brouillon',
        'url'            => 'https://exemple.com/ntsa-terme-brouillon',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => 'Le concept de Brouillon Glossaire NTSA reste à valider par la rédaction.',
            'key_points' => [],
            'why_important' => "Un terme non publié ne doit jamais apparaître comme une source légitime.",
        ],
        'slug'         => 'article-ntsa-terme-brouillon',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    app(NewsToolSyncAction::class)->suggest($article);

    $pivot = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->first();

    expect($pivot)->toBeNull();
});

it('relancer suggest() sur la même actualité ne crée pas de doublon dans news_article_term (idempotence)', function () {
    $term = Term::create([
        'name'         => 'Fenetre De Contexte NTSA',
        'slug'         => 'fenetre-de-contexte-ntsa',
        'definition'   => "La quantité de texte qu'un modèle peut traiter en une seule fois.",
        'is_published' => true,
    ]);

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => "La Fenetre De Contexte NTSA s'agrandit chez plusieurs fournisseurs",
        'guid'           => 'guid-ntsa-terme-idempotent',
        'url'            => 'https://exemple.com/ntsa-terme-idempotent',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => 'La Fenetre De Contexte NTSA permet de traiter des documents plus longs.',
            'key_points' => [],
            'why_important' => 'Cette évolution change la manière dont les modèles sont utilisés.',
        ],
        'slug'         => 'article-ntsa-terme-idempotent',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    app(NewsToolSyncAction::class)->suggest($article);
    app(NewsToolSyncAction::class)->suggest($article);

    $liaisons = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->count();

    expect($liaisons)->toBe(1);
});

// ── Non-régression : la détection des OUTILS ne doit JAMAIS être affectée par l'écriture
// parallèle des termes de glossaire ci-dessus - même texte, un outil ET un terme distincts. ──

it('suggest() détecte toujours un outil correctement quand un terme de glossaire est aussi présent dans le même texte (non-régression)', function () {
    $tool = ntsaTool('Redigeo NTSA', 'redigeo-ntsa');
    $term = Term::create([
        'name'         => 'Apprentissage Federe NTSA',
        'slug'         => 'apprentissage-federe-ntsa',
        'definition'   => "Une méthode d'entraînement distribuée qui ne centralise jamais les données.",
        'is_published' => true,
    ]);

    $article = NewsArticle::create([
        'news_source_id' => ntsaSource()->id,
        'title'          => 'Une actualité qui combine outil et concept de glossaire',
        'guid'           => 'guid-ntsa-outil-et-terme',
        'url'            => 'https://exemple.com/ntsa-outil-et-terme',
        'description'    => '',
        'summary'        => '',
        'structured_summary' => [
            'hook' => "Redigeo NTSA s'appuie sur l'Apprentissage Federe NTSA pour améliorer ses suggestions.",
            'key_points' => [
                'Redigeo NTSA a annoncé cette intégration cette semaine.',
            ],
            'why_important' => 'Cette combinaison illustre une tendance de fond.',
        ],
        'slug'         => 'article-ntsa-outil-et-terme',
        'pub_date'     => now()->subDay(),
        'is_published' => true,
        'seo_status'   => 'index',
    ]);

    $suggested = app(NewsToolSyncAction::class)->suggest($article);

    // L'outil est toujours détecté exactement comme avant l'ajout du traitement parallèle
    // des termes de glossaire - c'est la preuve de non-régression exigée par le ticket #2524.
    expect($suggested->all())->toContain($tool->id);

    $pivotTerme = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->first();

    expect($pivotTerme)->not->toBeNull();
    expect($pivotTerme->source)->toBe('auto');
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

// ── attachBySlug() / detachBySlug() - promues depuis NewsApplyCommand::attachRelatedTools()/
// detachRelatedTools() (design doc "extension de l'écran de composition des actualités",
// 2026-09-03, section 2.4, Lot 1 « fondations »). Verrouille le contrat de données que
// NewsApplyCommand::handle() traduit désormais en $this->warn()/$this->info(), et que le
// futur écran de composition (Lot 4) traduira en JSON - même service, deux présentations.

function ntsaArticle(int $sourceId): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $sourceId,
        'title'          => 'Article ntsa attach/detach '.uniqid(),
        'guid'           => 'guid-ntsa-attach-'.uniqid(),
        'url'            => 'https://exemple.com/ntsa-attach-'.uniqid(),
        'description'    => '',
        'slug'           => 'article-ntsa-attach-'.uniqid(),
        'pub_date'       => now()->subDay(),
        'is_published'   => true,
        'seo_status'     => 'index',
    ]);
}

it('attachBySlug() résout un slug publié, l\'attache et rapporte le nom résolu', function () {
    $source = ntsaSource();
    $tool = ntsaTool('Redigeo', 'redigeo');
    $article = ntsaArticle($source->id);

    $result = app(NewsToolSyncAction::class)->attachBySlug($article, ['redigeo']);

    expect($result['module_disabled'])->toBeFalse()
        ->and($result['unknown'])->toBe([])
        ->and($result['attached_count'])->toBe(1)
        ->and($result['attached_names'])->toBe(['Redigeo'])
        ->and($article->tools()->pluck('directory_tools.id')->all())->toBe([$tool->id]);
});

it('attachBySlug() rapporte les slugs introuvables sans rien attacher pour eux', function () {
    $source = ntsaSource();
    $article = ntsaArticle($source->id);

    $result = app(NewsToolSyncAction::class)->attachBySlug($article, ['slug-inexistant']);

    expect($result['unknown'])->toBe(['slug-inexistant'])
        ->and($result['attached_count'])->toBe(0)
        ->and($result['attached_names'])->toBe([])
        ->and($article->tools()->count())->toBe(0);
});

it('attachBySlug() ne résout que contre les outils PUBLIÉS - un outil en brouillon reste introuvable', function () {
    $source = ntsaSource();
    Tool::withoutEvents(fn () => Tool::create([
        'name' => ['fr_CA' => 'Brouillon Outil', 'en' => 'Draft Tool'],
        'slug' => ['fr_CA' => 'brouillon-outil', 'en' => 'brouillon-outil'],
        'status' => 'draft',
        'pricing' => 'free',
    ]));
    $article = ntsaArticle($source->id);

    $result = app(NewsToolSyncAction::class)->attachBySlug($article, ['brouillon-outil']);

    expect($result['unknown'])->toBe(['brouillon-outil'])
        ->and($article->tools()->count())->toBe(0);
});

it('attachBySlug() est un ajout PUR : un second appel sur le même outil déjà lié n\'attache rien de plus', function () {
    $source = ntsaSource();
    ntsaTool('Scriptomax', 'scriptomax');
    $article = ntsaArticle($source->id);

    app(NewsToolSyncAction::class)->attachBySlug($article, ['scriptomax']);
    $second = app(NewsToolSyncAction::class)->attachBySlug($article, ['scriptomax']);

    // Reproduit fidèlement le comportement d'origine de NewsApplyCommand::attachRelatedTools() :
    // attached_count (issu d'attachAuto(), le nombre RÉELLEMENT nouveau) tombe à 0, mais
    // attached_names liste quand même le slug résolu - c'est ce doublon exact que le message
    // console historique ("Fiche X : 0 outil(s) lié(s) (Scriptomax).") affichait déjà.
    expect($second['attached_count'])->toBe(0)
        ->and($second['attached_names'])->toBe(['Scriptomax'])
        ->and($article->tools()->count())->toBe(1);
});

it('detachBySlug() détache un outil réellement lié et rapporte son nom', function () {
    $source = ntsaSource();
    $tool = ntsaTool('Detachable', 'detachable');
    $article = ntsaArticle($source->id);
    $article->tools()->attach($tool->id, ['source' => 'manual']);

    $result = app(NewsToolSyncAction::class)->detachBySlug($article, ['detachable']);

    expect($result['detached_count'])->toBe(1)
        ->and($result['detached_names'])->toBe(['Detachable'])
        ->and($result['not_attached'])->toBe([])
        ->and($article->tools()->count())->toBe(0);
});

it('detachBySlug() signale (avertissement, pas une erreur) un outil résolu mais non attaché à cette fiche', function () {
    $source = ntsaSource();
    ntsaTool('JamaisLie', 'jamais-lie');
    $article = ntsaArticle($source->id);

    $result = app(NewsToolSyncAction::class)->detachBySlug($article, ['jamais-lie']);

    expect($result['not_attached'])->toBe(['JamaisLie'])
        ->and($result['detached_count'])->toBe(0)
        ->and($result['detached_names'])->toBe([]);
});

it('detachBySlug() résout contre TOUS les outils, y compris dépubliés depuis - un outil attaché à tort reste détachable', function () {
    $source = ntsaSource();
    $tool = Tool::withoutEvents(fn () => Tool::create([
        'name' => ['fr_CA' => 'Depublie Depuis', 'en' => 'Unpublished Since'],
        'slug' => ['fr_CA' => 'depublie-depuis', 'en' => 'depublie-depuis'],
        'status' => 'draft',
        'pricing' => 'free',
    ]));
    $article = ntsaArticle($source->id);
    $article->tools()->attach($tool->id, ['source' => 'auto']);

    $result = app(NewsToolSyncAction::class)->detachBySlug($article, ['depublie-depuis']);

    expect($result['detached_count'])->toBe(1)
        ->and($article->tools()->count())->toBe(0);
});

it('detachBySlug() rapporte un slug introuvable dans l\'annuaire sans rien détacher', function () {
    $source = ntsaSource();
    $article = ntsaArticle($source->id);

    $result = app(NewsToolSyncAction::class)->detachBySlug($article, ['jamais-existe']);

    expect($result['unknown'])->toBe(['jamais-existe'])
        ->and($result['detached_count'])->toBe(0);
});

// ── Ticket #2524 (2026-09-13) : commande de rattrapage news:backfill-auto-terms, jumelle
// exacte de news:backfill-auto-tools (voir BackfillAutoToolDetectionCommandTest.php) - pour le
// pivot news_article_term. Smoke-test de bout en bout de la commande elle-même (au-delà de
// suggest()/suggestGlossaryTermIds() déjà couverts ci-dessus). ──

it('news:backfill-auto-terms en --dry-run ne fait aucune écriture', function () {
    Term::create([
        'name'         => 'Distillation De Modele NTSA',
        'slug'         => 'distillation-de-modele-ntsa',
        'definition'   => "Le transfert des connaissances d'un grand modèle vers un plus petit.",
        'is_published' => true,
    ]);

    $source = ntsaSource();
    $article = NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $source->id,
        'title'          => 'La Distillation De Modele NTSA gagne en popularité',
        'guid'           => 'guid-ntsa-backfill-terme-dryrun',
        'url'            => 'https://exemple.com/ntsa-backfill-terme-dryrun',
        'description'    => '',
        'summary'        => "La Distillation De Modele NTSA réduit les coûts d'inférence.",
        'slug'           => 'article-ntsa-backfill-terme-dryrun',
        'pub_date'       => now()->subDay(),
        'is_published'   => true,
        'seo_status'     => 'index',
    ]));

    $this->artisan('news:backfill-auto-terms', ['--limit' => 50, '--dry-run' => true])
        ->assertExitCode(0);

    expect(DB::table('news_article_term')->where('news_article_id', $article->id)->count())->toBe(0);
});

it('news:backfill-auto-terms hors simulation attache réellement (source=auto) le terme mentionné', function () {
    $term = Term::create([
        'name'         => 'Distillation De Modele Reelle NTSA',
        'slug'         => 'distillation-de-modele-reelle-ntsa',
        'definition'   => "Le transfert des connaissances d'un grand modèle vers un plus petit.",
        'is_published' => true,
    ]);

    $source = ntsaSource();
    $article = NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $source->id,
        'title'          => 'La Distillation De Modele Reelle NTSA gagne en popularité',
        'guid'           => 'guid-ntsa-backfill-terme-reel',
        'url'            => 'https://exemple.com/ntsa-backfill-terme-reel',
        'description'    => '',
        'summary'        => "La Distillation De Modele Reelle NTSA réduit les coûts d'inférence.",
        'slug'           => 'article-ntsa-backfill-terme-reel',
        'pub_date'       => now()->subDay(),
        'is_published'   => true,
        'seo_status'     => 'index',
    ]));

    $this->artisan('news:backfill-auto-terms', ['--limit' => 50])
        ->assertExitCode(0);

    $pivot = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->first();

    expect($pivot)->not->toBeNull();
    expect($pivot->source)->toBe('auto');

    // Relancer sans nouvelle mention ne crée aucun doublon (idempotence, cf. whereDoesntHave
    // ('terms') dans la commande - l'article n'apparaît plus dans son propre périmètre).
    $this->artisan('news:backfill-auto-terms', ['--limit' => 50])
        ->assertExitCode(0);

    expect(DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->count())->toBe(1);
});

// ── Correctif ticket #2525 : une fiche examinée sans correspondance ne revient plus ────────────
//
// Avant #2525, la sélection reposait sur whereDoesntHave('terms') : une fiche sans AUCUNE
// mention réelle de glossaire (absence normale) n'obtenait jamais de liaison, restait donc pour
// toujours dans ce périmètre et se faisait retraiter à CHAQUE exécution - mesuré en production
// sur cinq lots consécutifs : 400 fiches traitées par lot, seulement 303 puis 249 puis 189 puis
// 149 réellement évacuées (coût par résultat qui double toutes les deux exécutions).

it('news:backfill-auto-terms - une fiche examinée sans correspondance reçoit son horodatage et ne revient pas dans le lot suivant', function () {
    $source = ntsaSource();
    // Aucun terme de glossaire ne correspond à ce texte : absence normale, pas un défaut.
    $normale = NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $source->id,
        'title'          => 'Le gouvernement dépose un projet de loi sur la vie privée',
        'guid'           => 'guid-ntsa-backfill-terme-normal',
        'url'            => 'https://exemple.com/ntsa-backfill-terme-normal',
        'description'    => '',
        'summary'        => 'Aucune fiche de glossaire ne devrait matcher ce texte.',
        'slug'           => 'article-ntsa-backfill-terme-normal',
        'pub_date'       => now()->subDay(),
        'is_published'   => true,
        'seo_status'     => 'index',
    ]));

    expect(DB::table('news_articles')->where('id', $normale->id)->value('terms_examined_at'))->toBeNull();

    $this->artisan('news:backfill-auto-terms', ['--limit' => 50])->assertExitCode(0);

    // L'horodatage d'examen est posé MÊME sans liaison - c'est le coeur du correctif.
    expect(DB::table('news_articles')->where('id', $normale->id)->value('terms_examined_at'))->not->toBeNull();
    expect(DB::table('news_article_term')->where('news_article_id', $normale->id)->count())->toBe(0);

    // Relancer la commande ne la retraite plus : elle est déjà examinée, donc exclue du lot -
    // c'est précisément le test qui rougirait si l'on revenait à whereDoesntHave('terms').
    $this->artisan('news:backfill-auto-terms', ['--limit' => 50])
        ->expectsOutputToContain('Aucune actualité publiée en attente d\'examen pour le glossaire')
        ->assertExitCode(0);
});

it('news:backfill-auto-terms - une fiche examinée avec correspondance reçoit son horodatage et sa liaison', function () {
    $term = Term::create([
        'name'         => 'Distillation De Modele Horodatage NTSA',
        'slug'         => 'distillation-de-modele-horodatage-ntsa',
        'definition'   => "Le transfert des connaissances d'un grand modèle vers un plus petit.",
        'is_published' => true,
    ]);

    $source = ntsaSource();
    $article = NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $source->id,
        'title'          => 'La Distillation De Modele Horodatage NTSA gagne en popularité',
        'guid'           => 'guid-ntsa-backfill-terme-horodatage',
        'url'            => 'https://exemple.com/ntsa-backfill-terme-horodatage',
        'description'    => '',
        'summary'        => "La Distillation De Modele Horodatage NTSA réduit les coûts d'inférence.",
        'slug'           => 'article-ntsa-backfill-terme-horodatage',
        'pub_date'       => now()->subDay(),
        'is_published'   => true,
        'seo_status'     => 'index',
    ]));

    expect(DB::table('news_articles')->where('id', $article->id)->value('terms_examined_at'))->toBeNull();

    $this->artisan('news:backfill-auto-terms', ['--limit' => 50])->assertExitCode(0);

    expect(DB::table('news_articles')->where('id', $article->id)->value('terms_examined_at'))->not->toBeNull();

    $pivot = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $term->id)
        ->first();
    expect($pivot)->not->toBeNull();
    expect($pivot->source)->toBe('auto');
});

it('news:backfill-auto-terms - loption rescanner ramène une fiche déjà examinée et lui permet une nouvelle liaison', function () {
    $source = ntsaSource();
    // Aucune fiche de glossaire "Zorglubulator Terminologique" ne publiée pour l'instant :
    // absence normale au 1er passage.
    $article = NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $source->id,
        'title'          => 'Le nouveau Zorglubulator Terminologique change la donne',
        'guid'           => 'guid-ntsa-backfill-terme-rescanner',
        'url'            => 'https://exemple.com/ntsa-backfill-terme-rescanner',
        'description'    => '',
        'summary'        => 'Le nouveau Zorglubulator Terminologique change la donne.',
        'slug'           => 'article-ntsa-backfill-terme-rescanner',
        'pub_date'       => now()->subDay(),
        'is_published'   => true,
        'seo_status'     => 'index',
    ]));

    $this->artisan('news:backfill-auto-terms', ['--limit' => 50])->assertExitCode(0);
    expect(DB::table('news_article_term')->where('news_article_id', $article->id)->count())->toBe(0);
    expect(DB::table('news_articles')->where('id', $article->id)->value('terms_examined_at'))->not->toBeNull();

    // Le glossaire "s'améliore" en cours de route (le terme est publié APRÈS le 1er passage).
    // Sans --rescanner, rien ne reconsidère la fiche déjà examinée.
    $terme = Term::create([
        'name'         => 'Zorglubulator Terminologique',
        'slug'         => 'zorglubulator-terminologique',
        'definition'   => 'Terme fictif utilisé uniquement pour ce test.',
        'is_published' => true,
    ]);
    $this->artisan('news:backfill-auto-terms', ['--limit' => 50])->assertExitCode(0);
    expect(DB::table('news_article_term')->where('news_article_id', $article->id)->count())->toBe(0);

    // Avec --rescanner : la fiche redevient éligible et obtient désormais sa liaison.
    $this->artisan('news:backfill-auto-terms', ['--limit' => 50, '--rescanner' => true])->assertExitCode(0);
    $pivot = DB::table('news_article_term')
        ->where('news_article_id', $article->id)
        ->where('term_id', $terme->id)
        ->first();
    expect($pivot)->not->toBeNull();
});

it("news:backfill-auto-terms - une fiche DEJA liee n'est pas comptee comme sans correspondance", function () {
    // MESURE DU 2026-09-13 qui a motivé ce test (#2525) : un lot de 400 fiches déjà traitées a
    // annoncé « 400 n'avaient aucune correspondance », alors que 323 d'entre elles portaient
    // déjà des liaisons. La cause : le compte se faisait sur le DELTA avant/après, qui vaut
    // évidemment zéro pour une fiche déjà liée. Le delta mesure ce que CE passage a ajouté ;
    // le total, lui, mesure si la fiche a des correspondances. Les deux ne se confondent pas.
    $term = Term::create([
        'name'         => 'Fenetre De Contexte Deja Liee NTSA',
        'slug'         => 'fenetre-de-contexte-deja-liee-ntsa',
        'definition'   => "La quantité de texte qu'un modèle peut prendre en compte d'un coup.",
        'is_published' => true,
    ]);

    $source = ntsaSource();
    $article = NewsArticle::withoutEvents(fn () => NewsArticle::create([
        'news_source_id' => $source->id,
        'title'          => 'La Fenetre De Contexte Deja Liee NTSA double chez les fournisseurs',
        'guid'           => 'guid-ntsa-deja-liee',
        'url'            => 'https://exemple.com/ntsa-deja-liee',
        'description'    => '',
        'summary'        => 'La Fenetre De Contexte Deja Liee NTSA change les usages.',
        'slug'           => 'article-ntsa-deja-liee',
        'pub_date'       => now()->subDay(),
        'is_published'   => true,
        'seo_status'     => 'index',
    ]));

    // Premier passage : la liaison est posée et la fiche est marquée examinée.
    $this->artisan('news:backfill-auto-terms', ['--limit' => 50])->assertExitCode(0);
    expect(DB::table('news_article_term')->where('news_article_id', $article->id)->count())->toBe(1);

    // Second passage FORCÉ sur la même fiche : aucune liaison NOUVELLE (le delta est nul), mais
    // la fiche a bel et bien une correspondance. Le message ne doit donc pas la ranger parmi
    // celles qui n'en ont aucune.
    $this->artisan('news:backfill-auto-terms', ['--limit' => 50, '--rescanner' => true])
        ->expectsOutputToContain('1 ont au moins un terme de glossaire')
        ->assertExitCode(0);

    // Et rien n'a été dupliqué au passage.
    expect(DB::table('news_article_term')->where('news_article_id', $article->id)->count())->toBe(1);
});
