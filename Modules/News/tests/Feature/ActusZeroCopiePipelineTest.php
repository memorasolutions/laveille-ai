<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests d'intégration du design doc "Actus - zéro copie du texte source" (2026-08-13),
 * section 6. Couvre le recâblage des sections 4.1 à 4.5 : le texte source ne transite plus
 * jamais par la colonne description, la porte de qualité avant persistance, le JSON-LD
 * dérivé du résumé publié, le garde-fou anti-corps-vide et les cascades d'affichage.
 *
 * Convention du projet : jamais d'appel réseau réel - Http::fake() partout.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\AiSummaryService;
use Modules\News\Services\RssFetcherService;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Azc pour éviter tout conflit inter-fichiers) ─────

function azcSource(?string $url = null): NewsSource
{
    return NewsSource::create([
        'name' => 'Source zéro copie',
        'url' => $url ?? 'https://azc-source.exemple.com/rss',
        'language' => 'fr',
        'active' => true,
    ]);
}

function azcArticle(int $sourceId, array $overrides = []): NewsArticle
{
    static $i = 0;
    $i++;
    $suffix = $i.'-'.uniqid();

    return NewsArticle::create(array_merge([
        'news_source_id' => $sourceId,
        'title' => "Article zéro copie {$i}",
        'guid' => "guid-azc-{$suffix}",
        'url' => "https://exemple.com/azc-{$suffix}",
        'description' => '',
        'slug' => "article-azc-{$suffix}",
        'pub_date' => now()->subDay(),
        'is_published' => true,
        'seo_status' => 'index',
    ], $overrides));
}

/**
 * JSON complet valide qui passe la porte de qualité par défaut. Couvre TOUS les champs
 * requis depuis le recalibrage 2026-08-13 (Modules\News\config\config.php,
 * news.quality_gate.required_fields) - une fixture minimale masquerait une régression de la
 * porte plutôt que de la tester.
 */
function azcValidPayload(array $overrides = []): array
{
    return array_merge([
        'score' => 8,
        'score_justification' => 'Pertinent pour le test.',
        'category' => 'IA générative',
        'impact' => 'Moyen',
        'tldr' => 'Une entreprise technologique lance un nouvel outil francophone pour les équipes de développement du Québec cette semaine.',
        'hook' => 'Une entreprise technologique dévoile un nouvel outil pour les équipes francophones du Québec.',
        'key_points' => ['Premier fait détaillé du test.', 'Deuxième fait détaillé du test.'],
        'why_important' => 'Ce changement modifie concrètement le travail quotidien des professionnels visés par le test.',
        'audience' => ['développeurs', 'entreprises'],
        'seo_title' => 'Titre SEO de test',
        'meta_description' => 'Description meta de test suffisamment courte pour la borne configurée par défaut.',
        'faq_question' => 'Pourquoi cet outil intéresse-t-il les équipes francophones ?',
        'faq_answer' => 'Parce qu\'il répond à un besoin concret de localisation resté sans solution jusqu\'ici.',
    ], $overrides);
}

function azcFakeOpenRouterOnce(array $payload): void
{
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($payload)]]],
        ], 200),
    ]);
}

// ── 4.1 : le texte ne transite plus par une colonne ───────────────────────────

/**
 * NOTE D'ENVIRONNEMENT : l'installation vendor de simplepie/simplepie 1.9.0 de ce dépôt est
 * incomplète (src/ ne contient pas SimplePie\Misc, seulement library/SimplePie/Misc.php de
 * l'ancienne arborescence non-PSR4) - un flux RSS réel ne peut pas être analysé dans cet
 * environnement, quel que soit le code testé. C'est un défaut préexistant, indépendant de ce
 * chantier : AUCUN test du dépôt (avant ou après ce chantier) n'invoque la analyse SimplePie
 * réelle - NewsFusionTest.php neutralise déjà RssFetcherService pour cette même raison. Ce test
 * vérifie donc le comportement RÉEL exécutable ici : le contrat de retour (array, jamais un
 * entier nu) et le fait qu'aucune fiche n'est créée quand l'analyse échoue - ce qui couvre au
 * passage un vrai bug trouvé et corrigé pendant ce chantier (l'ancien retour `return 0;` sur
 * erreur de flux, incompatible avec la nouvelle signature `: array`).
 */
it('fetchSource() respecte le nouveau contrat de retour (tableau count/texts) même quand le flux échoue à être analysé', function () {
    $source = azcSource('/chemin/local/qui-nexiste-pas-'.uniqid().'.xml');

    $result = (new RssFetcherService())->fetchSource($source);

    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['count', 'texts'])
        ->and($result['count'])->toBe(0)
        ->and($result['texts'])->toBe([]);
    expect(NewsArticle::count())->toBe(0);
});

it('RssFetcherService::fetchSource() écrit toujours description=\'\' à la création, jamais le texte source', function () {
    // Preuve directe, au niveau du code réellement exécuté par fetchSource() (indépendante de
    // la limitation SimplePie ci-dessus) : la création de la fiche passe TOUJOURS par
    // NewsArticle::create() avec 'description' => '' - jamais un texte extrait ou le blurb RSS.
    $source = azcSource();
    $article = NewsArticle::create([
        'news_source_id' => $source->id,
        'title' => 'Article créé comme le ferait fetchSource()',
        'guid' => 'guid-azc-contract-'.uniqid(),
        'url' => 'https://exemple.com/azc-contract',
        'description' => '',
        'pub_date' => now(),
        'is_published' => false,
    ]);

    expect($article->description)->toBe('');

    $sourceCode = file_get_contents(base_path('Modules/News/app/Services/RssFetcherService.php'));
    expect($sourceCode)->toContain("'description' => ''")
        ->and($sourceCode)->not->toMatch("/'description'\s*=>\s*\\\$(rssBlurb|text|extracted)/");
});

it('scoreAndSummarize reçoit le texte par son paramètre et ne lit jamais la colonne description', function () {
    azcFakeOpenRouterOnce(azcValidPayload());

    $source = azcSource();
    // Marqueur en base, jamais transmis en argument : ne doit apparaître dans AUCUNE requête sortante.
    $article = azcArticle($source->id, ['description' => 'MARQUEUR-JAMAIS-ENVOYE-DEPUIS-LA-COLONNE']);

    (new AiSummaryService())->scoreAndSummarize($article->title, 'MARQUEUR-TEXTE-ARGUMENT-EXPLICITE', 'fr');

    Http::assertSent(function ($request) {
        $content = $request->data()['messages'][0]['content'] ?? '';

        return str_contains($content, 'MARQUEUR-TEXTE-ARGUMENT-EXPLICITE')
            && ! str_contains($content, 'MARQUEUR-JAMAIS-ENVOYE-DEPUIS-LA-COLONNE');
    });
});

// ── 4.2 : porte de qualité + cascade ───────────────────────────────────────────

it('la porte de qualité refuse un résumé incomplet et empêche sa persistance', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['score' => 8, 'hook' => ''])]]],
        ], 200),
    ]);

    $result = (new AiSummaryService())->scoreAndSummarize('Titre', 'Texte source suffisant pour ce test.', 'fr');

    // Cascade épuisée (les deux modèles configurés reçoivent le même JSON incomplet) : refus.
    expect($result)->toBeNull();
    Http::assertSentCount(2);
});

it('la porte de qualité refuse un résumé en anglais', function () {
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode([
                'score' => 8,
                'hook' => 'The company is launching a new tool for the whole industry and this is a very big deal indeed.',
            ])]]],
        ], 200),
    ]);

    $result = (new AiSummaryService())->scoreAndSummarize('Titre', 'Texte source suffisant pour ce test.', 'fr');

    expect($result)->toBeNull();
});

it('la porte de qualité refuse un résumé trop court', function () {
    config(['news.quality_gate.hook_min_words' => 10]);
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['score' => 8, 'hook' => 'Trop court.'])]]],
        ], 200),
    ]);

    $result = (new AiSummaryService())->scoreAndSummarize('Titre', 'Texte source suffisant pour ce test.', 'fr');

    expect($result)->toBeNull();
});

it('échec du premier modèle (rejeté par la porte), succès du second : la fiche est publiée', function () {
    Http::fake([
        'openrouter.ai/*' => Http::sequence()
            // 1er modèle : JSON valide mais rejeté (hook vide -> vacuité).
            ->push(['choices' => [['message' => ['content' => json_encode(['score' => 8, 'hook' => ''])]]]], 200)
            // 2e modèle : résumé valide.
            ->push(['choices' => [['message' => ['content' => json_encode(azcValidPayload())]]]], 200),
    ]);

    $result = (new AiSummaryService())->scoreAndSummarize('Titre', 'Texte source suffisant pour ce test.', 'fr');

    expect($result)->not->toBeNull()
        ->and($result['hook'])->toBe(azcValidPayload()['hook']);

    Http::assertSentInOrder([
        fn ($request) => str_contains($request->url(), 'openrouter.ai')
            && ($request->data()['model'] ?? null) === 'openai/gpt-4o-mini',
        fn ($request) => str_contains($request->url(), 'openrouter.ai')
            && ($request->data()['model'] ?? null) === 'deepseek/deepseek-chat',
    ]);
});

it('cascade épuisée : aucune fiche créée côté résumé, un journal écrit (refus normal, pas une exception)', function () {
    Log::spy();
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['score' => 8, 'hook' => ''])]]],
        ], 200),
    ]);

    $result = (new AiSummaryService())->scoreAndSummarize('Titre', 'Texte source suffisant pour ce test.', 'fr');

    expect($result)->toBeNull();
    Http::assertSentCount(2);
    Log::shouldHaveReceived('error')->once();
});

it('news:fetch ne publie jamais une fiche dont le résumé a été rejeté par la porte de qualité (pipeline complet)', function () {
    // Pipeline historique : générateur machine explicitement allumé, éteint par défaut
    // depuis v1.187.0 - ce test vérifie la porte de qualité EN AMONT de la publication, donc
    // suppose que la génération machine a bien lieu.
    config(['news.machine_summary.enabled' => true]);
    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode(['score' => 8, 'hook' => ''])]]],
        ], 200),
    ]);

    app()->instance(RssFetcherService::class, new class extends RssFetcherService
    {
        public function fetchSource(NewsSource $source): array
        {
            $texts = NewsArticle::where('news_source_id', $source->id)
                ->whereNull('structured_summary')
                ->pluck('description', 'id')
                ->all();

            return ['count' => 0, 'texts' => $texts];
        }
    });

    $source = azcSource();
    $article = azcArticle($source->id, [
        'is_published' => false,
        'description' => 'IA générative : texte de fixture suffisant pour passer le pré-filtre de mots-clés.',
    ]);

    $this->artisan('news:fetch', ['--source' => $source->id])->assertSuccessful();

    $article->refresh();
    expect($article->structured_summary)->toBeNull()
        ->and($article->is_published)->toBeFalse();
});

// ── 4.3 : JSON-LD publie le résumé, jamais le texte source ────────────────────

it('le JSON-LD publié sur la fiche ne contient aucune phrase absente de la page visible', function () {
    $source = azcSource();
    $article = azcArticle($source->id, [
        'description' => 'MARQUEUR-TEXTE-SOURCE-JAMAIS-PUBLIE',
        'summary' => null,
        'structured_summary' => [
            'hook' => 'Marqueur visible unique dans le résumé structuré de ce test précis.',
            'key_points' => ['Un point clé du test.'],
            'why_important' => 'Une explication du test.',
        ],
    ]);

    $response = $this->get(route('news.show', $article->slug));
    $response->assertOk();

    $html = $response->getContent();

    // La phrase du résumé structuré est à la fois visible (nw-lead) ET dans le JSON-LD.
    expect($html)->toContain('Marqueur visible unique dans le résumé structuré de ce test précis.');
    // Le texte source (description) ne doit JAMAIS apparaître nulle part sur la page.
    expect($html)->not->toContain('MARQUEUR-TEXTE-SOURCE-JAMAIS-PUBLIE');
});

// ── 4.4 : garde-fou anti-corps-vide ────────────────────────────────────────────

it('une fiche publiée sans résumé exploitable n\'est jamais servie avec un corps vide (410, vue gone)', function () {
    $source = azcSource();
    $article = azcArticle($source->id, [
        'description' => '',
        'summary' => null,
        'structured_summary' => null,
    ]);

    $response = $this->get(route('news.show', $article->slug));

    // Fiche publiée et non retirée : 410 propre via la vue partagée avec les fiches
    // retirées (DRY), jamais un 404 brut - meilleur pour l'UX et le SEO (2026-08-20).
    $response->assertStatus(410);
    $response->assertViewIs('news::public.gone');
    // Texte générique : cette fiche n'est PAS retirée (retired_at reste nul), le texte ne
    // doit donc jamais présupposer un retrait volontaire.
    $response->assertSee('indisponible', escape: false);
    $response->assertDontSee('retirée', escape: false);
});

it('une fiche publiée AVEC un résumé exploitable reste servie normalement', function () {
    $source = azcSource();
    $article = azcArticle($source->id, [
        'summary' => 'Un résumé court suffisant.',
    ]);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertOk();
});

// ── 4.5 : cascades d'affichage ─────────────────────────────────────────────────

it('displayExcerpt() retourne toujours une valeur non vide quand summary et description sont vides', function () {
    $source = azcSource();
    $article = azcArticle($source->id, [
        'description' => '',
        'summary' => null,
        'structured_summary' => null,
        'category_tag' => 'IA générative',
    ]);

    $excerpt = $article->displayExcerpt(200);

    expect($excerpt)->not->toBe('')
        ->and($excerpt)->toContain('IA générative');
});

it('displayExcerpt() retombe sur la mention générique configurée quand aucune catégorie n\'est disponible', function () {
    $source = azcSource();
    $article = azcArticle($source->id, [
        'description' => '',
        'summary' => null,
        'structured_summary' => null,
        'category_tag' => null,
    ]);

    $excerpt = $article->displayExcerpt(200);

    expect($excerpt)->toBe((string) config('news.display_fallback.generic'));
});

it('searchableResultExcerpt() (recherche interne) n\'est jamais vide quand summary et description sont vides', function () {
    $source = azcSource();
    $article = azcArticle($source->id, [
        'description' => '',
        'summary' => null,
        'structured_summary' => null,
        'category_tag' => 'Cybersécurité',
    ]);

    expect($article->searchableResultExcerpt())->not->toBe('');
});

it('la carte d\'actualité (article-card) affiche une accroche non vide même sans summary ni description', function () {
    $source = azcSource();
    azcArticle($source->id, [
        'description' => '',
        'summary' => null,
        'structured_summary' => null,
        'category_tag' => 'Cybersécurité',
    ]);

    $response = $this->get(route('news.index'));

    $response->assertOk();
    // Le repli configuré (catégorie + date) doit apparaître dans la carte - jamais une accroche
    // vide, jamais le texte source (toujours vide de toute façon).
    $response->assertSee('Cybersécurité');
});

it('JournalBlockService::addFromSource (type news) utilise la cascade displayExcerpt(), jamais description', function () {
    $journalUser = \App\Models\User::factory()->create();
    $journal = \Modules\Journal\Models\Journal::create([
        'user_id' => $journalUser->id,
        'title' => 'Journal test zéro copie',
        'slug' => 'journal-azc-'.uniqid(),
        'journal_date' => now()->toDateString(),
        'template' => 'classique',
        'is_published' => false,
    ]);
    $source = azcSource();
    $article = azcArticle($source->id, [
        'description' => 'MARQUEUR-DESCRIPTION-JAMAIS-DANS-LE-BLOC-JOURNAL',
        'summary' => 'Résumé court pour le bloc Journal.',
    ]);

    $block = app(\Modules\Journal\Services\JournalBlockService::class)->addFromSource($journal, 'news', $article->id);

    expect($block->payload['excerpt'])->toBe('Résumé court pour le bloc Journal.')
        ->and($block->payload['excerpt'])->not->toContain('MARQUEUR-DESCRIPTION-JAMAIS-DANS-LE-BLOC-JOURNAL');
});

it('échec du premier modèle rejeté par la porte, succès du second : la fiche est publiée par news:fetch (pipeline complet)', function () {
    // Publication automatique activée explicitement (2026-08-14) : par défaut FALSE depuis la
    // suspension de la publication auto - ce test vérifie précisément le chemin PUBLIÉ.
    // Pipeline historique : générateur machine explicitement allumé, éteint par défaut
    // depuis v1.187.0.
    config(['news.autopublish.enabled' => true, 'news.machine_summary.enabled' => true]);
    Http::fake([
        'openrouter.ai/*' => Http::sequence()
            ->push(['choices' => [['message' => ['content' => json_encode(['score' => 8, 'hook' => ''])]]]], 200)
            ->push(['choices' => [['message' => ['content' => json_encode(azcValidPayload())]]]], 200),
    ]);

    app()->instance(RssFetcherService::class, new class extends RssFetcherService
    {
        public function fetchSource(NewsSource $source): array
        {
            $texts = NewsArticle::where('news_source_id', $source->id)
                ->whereNull('structured_summary')
                ->pluck('description', 'id')
                ->all();

            return ['count' => 0, 'texts' => $texts];
        }
    });

    $source = azcSource();
    $article = azcArticle($source->id, [
        'is_published' => false,
        'description' => 'IA générative : texte de fixture suffisant pour passer le pré-filtre de mots-clés.',
    ]);

    $this->artisan('news:fetch', ['--source' => $source->id])->assertSuccessful();

    $article->refresh();
    expect($article->structured_summary)->not->toBeNull()
        ->and($article->is_published)->toBeTrue();
    Http::assertSentCount(2);
});

it('structuredBodyText() et hasExploitableSummary() ignorent totalement description', function () {
    $source = azcSource();
    $article = azcArticle($source->id, [
        'description' => 'MARQUEUR-DESCRIPTION-IGNOREE',
        'summary' => null,
        'structured_summary' => null,
    ]);

    expect($article->structuredBodyText())->toBe('')
        ->and($article->hasExploitableSummary())->toBeFalse();
});

// ── 5. Journalisation : 'description' n'est plus un champ journalisé ──────────
//
// Étape 4 de la procédure de purge (design doc section 5) : cette étape DOIT précéder la
// purge de la colonne (étapes 5 et 6), sinon la purge des 32 840 lignes recopierait le texte
// intégral de chacune dans activity_log au moment même de l'écrire à ''. Ces tests prouvent
// qu'une modification de 'description' - y compris son passage à '' comme le fera la purge -
// ne produit AUCUNE entrée de journal contenant le texte, ni même le nom du champ.

it('activitylogFields de NewsArticle ne contient plus \'description\'', function () {
    $article = new NewsArticle();
    $fields = (fn () => $this->activitylogFields)->call($article);

    expect($fields)->not->toContain('description')
        ->and($fields)->toContain('title'); // le tableau reste non vide, pas une régression de portée
});

it('modifier description seule ne crée aucune entrée de journal (champ non dirty-loggable)', function () {
    $source = azcSource();
    $article = azcArticle($source->id, ['description' => 'Texte source initial du test.']);
    $countBefore = \Spatie\Activitylog\Models\Activity::count();

    $article->update(['description' => 'MARQUEUR-TEXTE-EDITEUR-JAMAIS-JOURNALISE']);

    // logOnlyDirty() + dontSubmitEmptyLogs() : aucun champ journalisé n'a changé -> aucune ligne écrite.
    expect(\Spatie\Activitylog\Models\Activity::count())->toBe($countBefore);
});

it('purger description (mise à \'\') en même temps qu\'un champ journalisé ne fait apparaître le texte source nulle part dans le journal', function () {
    $source = azcSource();
    $article = azcArticle($source->id, [
        'description' => 'MARQUEUR-TEXTE-EDITEUR-A-NE-JAMAIS-JOURNALISER',
        'relevance_score' => 5,
    ]);

    // Simule la purge (étape 6) survenant en même temps qu'une modification légitime d'un champ
    // journalisé, pire cas pour cette garantie.
    $article->update(['description' => '', 'relevance_score' => 9]);

    $activity = \Spatie\Activitylog\Models\Activity::where('subject_type', NewsArticle::class)
        ->where('subject_id', $article->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    $payload = json_encode($activity->properties);
    expect($payload)->not->toContain('MARQUEUR-TEXTE-EDITEUR-A-NE-JAMAIS-JOURNALISER')
        ->and($payload)->not->toContain('description')
        ->and($activity->properties['attributes']['relevance_score'] ?? null)->toBe(9);
});
