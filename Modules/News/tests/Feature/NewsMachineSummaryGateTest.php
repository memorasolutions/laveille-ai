<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests du drapeau de génération machine des résumés (2026-08-17, décision du fondateur,
 * verbatim : « supprime l'automatisation qu'on utilisait pour les anciennes actus, on ne
 * l'utilisera plus »). Le contenu des fiches vient désormais exclusivement du flux /actu2
 * (composition IA supervisée) ; la collecte (titres, liens, dédup, pertinence mots-clés)
 * continue sans interruption. Ce fichier couvre les DEUX comportements du drapeau
 * news.machine_summary.enabled sur news:fetch (chemin non-fusion, seul chemin garanti stable
 * indépendamment de l'état de news.fusion.enabled - même périmètre que
 * NewsAutopublishGateTest.php), ainsi que le refus explicite de news:reprocess drapeau éteint.
 *
 * Convention du projet : jamais d'appel réseau réel - Http::fake() partout. RssFetcherService
 * et ContentExtractor sont neutralisés (contenu pré-semé/fixe), comme dans
 * NewsAutopublishGateTest.php et ActusZeroCopiePipelineTest.php.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\ContentExtractor;
use Modules\News\Services\RssFetcherService;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Nmsg pour éviter tout conflit inter-fichiers) ────

// 2026-09-05 (ticket #2248) : credit d'image par defaut. Depuis le garde-fou image de news:fetch,
// une fiche SANS credit ne peut plus etre publiee automatiquement - un test qui verifie le chemin
// NORMAL doit donc en fournir un, sinon il decrit un monde qui n'existe plus. Passer null pour
// tester explicitement le refus.
function nmsgSource(string $name): NewsSource
{
    return NewsSource::create([
        'name' => $name,
        'url' => 'https://exemple.com/'.\Illuminate\Support\Str::slug($name).'-'.uniqid(),
        'language' => 'fr',
    ]);
}

function nmsgArticle(NewsSource $source, string $slug, string $title): NewsArticle
{
    return NewsArticle::create([
        'image_credit' => 'Photo de test - MEMORA solutions',
        'news_source_id' => $source->id,
        'title' => $title,
        'guid' => 'guid-'.$slug,
        'url' => 'https://exemple.com/'.$slug,
        'description' => $title.' - intelligence artificielle : article de test avec suffisamment de contenu pour passer le pré-filtre de mots-clés du pipeline.',
        'pub_date' => now(),
        'is_published' => false,
    ]);
}

/** Neutralise RssFetcherService (les articles sont déjà pré-semés) : zéro appel réseau réel. */
function nmsgBindFakeRssFetcher(): void
{
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
}

/** Fake la cascade OpenRouter (Http::fake) : succès systématique, score au-dessus du seuil par défaut. */
function nmsgFakeOpenRouterSuccess(): void
{
    $payload = [
        'score' => 8,
        'score_justification' => 'Pertinent pour le test.',
        'category' => 'IA générative',
        'impact' => 'Élevé',
        'tldr' => 'Résumé factuel de test, trente à quarante mots pour respecter le format attendu ici.',
        'hook' => 'Accroche factuelle de test résumant fidèlement le contenu source fourni au modèle.',
        'key_points' => ['Point détaillé un du test.', 'Point détaillé deux du test.'],
        'why_important' => 'Explication de test sur l\'impact concret pour les professionnels visés.',
        'audience' => ['développeurs'],
        'seo_title' => 'Titre SEO de test',
        'meta_description' => 'Description meta de test.',
        'faq_question' => 'Question de test ?',
        'faq_answer' => 'Réponse de test détaillée.',
    ];

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($payload)]]],
        ], 200),
    ]);
}

/** Chemin du fichier daily du jour pour le canal 'fusion' (voir config/logging.php). */
function nmsgFusionLogPath(): string
{
    return storage_path('logs/fusion-'.now()->format('Y-m-d').'.log');
}

function nmsgResetFusionLog(): void
{
    @unlink(nmsgFusionLogPath());
}

/** Neutralise ContentExtractor pour news:reprocess : contenu fixe, sans image, > 200 mots. */
function nmsgBindFakeContentExtractor(): void
{
    app()->instance(ContentExtractor::class, new class extends ContentExtractor
    {
        public function extract(string $url): ?array
        {
            return [
                'title' => 'Titre extrait de test',
                'content' => str_repeat('Contenu de test suffisamment long pour dépasser le seuil de mots requis par la commande de retraitement. ', 30),
                'word_count' => 300,
                'image' => null,
            ];
        }
    });
}

beforeEach(function () {
    // autopublish ON pour isoler le comportement du drapeau testé ici (machine_summary) de
    // celui, déjà couvert, de NewsAutopublishGateTest.php.
    config(['news.fusion.enabled' => false, 'news.dedup_skip_enabled' => true, 'news.autopublish.enabled' => true]);
});

// ── news:fetch ─────────────────────────────────────────────────────────────

it('drapeau OFF (defaut) : la fiche est collectee normalement mais structured_summary reste null et aucun appel au fournisseur n a lieu', function () {
    config(['news.machine_summary.enabled' => false]);
    nmsgBindFakeRssFetcher();
    nmsgFakeOpenRouterSuccess();

    $source = nmsgSource('SourceMachineSummaryOff');
    $article = nmsgArticle($source, 'machine-summary-off', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    $this->artisan('news:fetch')->assertSuccessful();

    $article->refresh();
    expect($article->structured_summary)->toBeNull()
        ->and($article->relevance_score)->toBeNull()
        ->and($article->is_published)->toBeFalse()
        ->and($article->feed_type)->not->toBeNull();

    // Aucun texte d'article n'a transité vers le fournisseur de modèle - point de vigilance
    // Loi 25 de la clôture Actus 2.0, réglé par extinction.
    Http::assertNothingSent();
});

it('drapeau OFF : le bilan de la commande mentionne "resumes machine : desactives"', function () {
    config(['news.machine_summary.enabled' => false]);
    nmsgBindFakeRssFetcher();
    nmsgFakeOpenRouterSuccess();

    $source = nmsgSource('SourceMachineSummaryBilan');
    nmsgArticle($source, 'machine-summary-bilan', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    Artisan::call('news:fetch');
    $output = Artisan::output();

    expect($output)->toContain('résumés machine : désactivés');
});

it('drapeau OFF : le canal fusion recoit la ligne MACHINE-SUMMARY-OFF, meme avec LOG_LEVEL=error simule', function () {
    config([
        'news.machine_summary.enabled' => false,
        'logging.channels.daily.level' => 'emergency',
        'logging.channels.single.level' => 'emergency',
    ]);
    nmsgResetFusionLog();
    nmsgBindFakeRssFetcher();
    nmsgFakeOpenRouterSuccess();

    $source = nmsgSource('SourceMachineSummaryLog');
    nmsgArticle($source, 'machine-summary-log', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    $this->artisan('news:fetch')->assertSuccessful();

    expect(file_exists(nmsgFusionLogPath()))->toBeTrue();
    $content = file_get_contents(nmsgFusionLogPath());
    expect($content)->toContain('MACHINE-SUMMARY-OFF');
});

it('drapeau ON : comportement historique intact - resume genere, publie selon le score, appel au fournisseur effectue', function () {
    config(['news.machine_summary.enabled' => true, 'news.autopublish.enabled' => true]);
    nmsgBindFakeRssFetcher();
    nmsgFakeOpenRouterSuccess();

    $source = nmsgSource('SourceMachineSummaryOn');
    $article = nmsgArticle($source, 'machine-summary-on', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    $this->artisan('news:fetch')->assertSuccessful();

    $article->refresh();
    expect($article->structured_summary)->not->toBeNull()
        ->and($article->relevance_score)->toBe(8)
        ->and($article->is_published)->toBeTrue();

    Http::assertSentCount(1);
});

// ── news:reprocess ────────────────────────────────────────────────────────

it('news:reprocess refuse le resume machine drapeau eteint : aucun appel au fournisseur, message de refus explicite dans la sortie', function () {
    config(['news.machine_summary.enabled' => false]);
    nmsgBindFakeContentExtractor();
    nmsgFakeOpenRouterSuccess();

    $source = nmsgSource('SourceReprocessOff');
    $article = nmsgArticle($source, 'reprocess-off', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    Artisan::call('news:reprocess', ['--limit' => 1]);
    $output = Artisan::output();

    expect($output)->toContain('résumé machine REFUSÉ');

    Http::assertNothingSent();

    $article->refresh();
    expect($article->structured_summary)->toBeNull();
});
