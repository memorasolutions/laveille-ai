<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Tests du drapeau de publication automatique (2026-08-14) : le site cesse de publier
 * automatiquement une fiche d'actualité, mais la collecte (scoring, résumé IA, porte de
 * qualité, fusion, déduplication) continue sans interruption. Ce fichier couvre les DEUX
 * comportements du drapeau news.autopublish.enabled sur le chemin non-fusion, seul chemin
 * garanti stable indépendamment de l'état du drapeau news.fusion.enabled.
 *
 * Convention du projet : jamais d'appel réseau réel - Http::fake() partout. RssFetcherService
 * est neutralisé (les articles sont pré-semés directement en base), comme dans
 * NewsFusionTest.php et ActusZeroCopiePipelineTest.php.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use Modules\News\Services\RssFetcherService;
use Modules\Settings\Facades\Settings;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Napg pour éviter tout conflit inter-fichiers) ────

function napgSource(string $name): NewsSource
{
    return NewsSource::create([
        'name' => $name,
        'url' => 'https://exemple.com/'.\Illuminate\Support\Str::slug($name).'-'.uniqid(),
        'language' => 'fr',
    ]);
}

// 2026-09-05 (ticket #2248) : le parametre $imageCredit est arrive avec le garde-fou image de
// news:fetch. Depuis, une fiche SANS credit ne peut plus etre publiee automatiquement - le cas
// NORMAL d'un test « le drapeau publie » doit donc fournir un credit, sinon il decrit un monde
// qui n'existe plus. Defaut non vide pour que les tests existants gardent leur intention.
function napgArticle(NewsSource $source, string $slug, string $title, ?string $imageCredit = 'Photo de test - MEMORA solutions'): NewsArticle
{
    return NewsArticle::create([
        'image_credit' => $imageCredit,
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
function napgBindFakeRssFetcher(): void
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
function napgFakeOpenRouterSuccess(): void
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
function napgFusionLogPath(): string
{
    return storage_path('logs/fusion-'.now()->format('Y-m-d').'.log');
}

function napgResetFusionLog(): void
{
    @unlink(napgFusionLogPath());
}

beforeEach(function () {
    // Pipeline historique : générateur machine explicitement allumé, éteint par défaut
    // depuis v1.187.0 - ce fichier teste le drapeau autopublish, qui suppose que la génération
    // machine (résumé structuré, score) a bien eu lieu en amont.
    config(['news.fusion.enabled' => false, 'news.dedup_skip_enabled' => true, 'news.machine_summary.enabled' => true]);
});

it('drapeau ON : une fiche qui depasse le seuil de pertinence est publiee', function () {
    config(['news.autopublish.enabled' => true]);
    napgBindFakeRssFetcher();
    napgFakeOpenRouterSuccess();

    $source = napgSource('SourceAutopubOn');
    $article = napgArticle($source, 'autopub-on', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    $this->artisan('news:fetch')->assertSuccessful();

    $article->refresh();
    expect($article->is_published)->toBeTrue()
        ->and($article->structured_summary)->not->toBeNull()
        ->and($article->relevance_score)->toBe(8);
});

it('drapeau OFF (defaut) : la meme fiche est collectee en brouillon, is_published reste false', function () {
    config(['news.autopublish.enabled' => false]);
    napgBindFakeRssFetcher();
    napgFakeOpenRouterSuccess();

    $source = napgSource('SourceAutopubOff');
    $article = napgArticle($source, 'autopub-off', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    $this->artisan('news:fetch')->assertSuccessful();

    $article->refresh();
    // La collecte continue integralement : scoring, resume structure et porte de qualite ne
    // sont PAS court-circuites par le drapeau - seule l'ecriture is_published change.
    expect($article->is_published)->toBeFalse()
        ->and($article->structured_summary)->not->toBeNull()
        ->and($article->relevance_score)->toBe(8)
        ->and($article->category_tag)->not->toBeNull();
});

it('drapeau OFF : le canal fusion recoit la ligne de suspension, meme avec LOG_LEVEL=error simule', function () {
    config([
        'news.autopublish.enabled' => false,
        'logging.channels.daily.level' => 'emergency',
        'logging.channels.single.level' => 'emergency',
    ]);
    napgResetFusionLog();
    napgBindFakeRssFetcher();
    napgFakeOpenRouterSuccess();

    $source = napgSource('SourceAutopubLog');
    napgArticle($source, 'autopub-log', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    $this->artisan('news:fetch')->assertSuccessful();

    expect(file_exists(napgFusionLogPath()))->toBeTrue();
    $content = file_get_contents(napgFusionLogPath());
    expect($content)->toContain('AUTOPUBLISH-OFF');
});

it('drapeau ON : aucune ligne de suspension AUTOPUBLISH-OFF n est journalisee', function () {
    config(['news.autopublish.enabled' => true]);
    napgResetFusionLog();
    napgBindFakeRssFetcher();
    napgFakeOpenRouterSuccess();

    $source = napgSource('SourceAutopubOnLog');
    napgArticle($source, 'autopub-on-log', 'OpenAI lance un nouveau modele IA generative pour les entreprises');

    $this->artisan('news:fetch')->assertSuccessful();

    $content = file_exists(napgFusionLogPath()) ? file_get_contents(napgFusionLogPath()) : '';
    expect($content)->not->toContain('AUTOPUBLISH-OFF');
});

// ── Correctif 2026-08-14 (effet de bord round 1) : le bilan ne doit jamais mentir sur ce qui
// s'est reellement passe, et les quotas quotidiens (todayIa/todayTech) ne doivent JAMAIS etre
// consommes par des brouillons - sinon le jour ou la publication sera reactivee, le quota sera
// deja entame par des fiches jamais parues (piege differe signale par le superviseur). ─────

it('drapeau OFF : le bilan annonce zero publie et les quotas quotidiens IA ne sont pas consommes par des brouillons', function () {
    config(['news.autopublish.enabled' => false]);
    // Quota volontairement tres bas (1/jour) : si un brouillon consommait le quota comme le
    // ferait une VRAIE publication, le 2e article n'obtiendrait jamais son appel IA (la boucle
    // s'arreterait a "Quota IA atteint"). Prouver que les DEUX articles sont scores malgre ce
    // quota de 1 prouve que todayIa reste a 0 tout au long de l'execution.
    Settings::set('news.max_ia_articles_per_day', 1, 'integer');
    napgBindFakeRssFetcher();
    napgFakeOpenRouterSuccess();

    // Nom de source contenant 'ia' : feed_type='ia' (FetchNewsCommand::detectFeedType()),
    // le seul feed_type gouverne par max_ia_articles_per_day dans ce test.
    $source = napgSource('SourceQuotaIA');
    $articleUn = napgArticle($source, 'quota-ia-un', 'OpenAI lance un nouveau modele IA generative pour les entreprises quebecoises');
    $articleDeux = napgArticle($source, 'quota-ia-deux', 'Google devoile un modele IA generative concurrent pour les entreprises canadiennes');

    // ACTION : Artisan::call()/Artisan::output() plutôt que $this->artisan()->expectsOutputToContain()
    // (piège vérifié empiriquement 2026-08-14) : deux expectsOutputToContain() dont les
    // substrings apparaissent sur la MÊME ligne de sortie ne sont pas tous les deux honorés -
    // Mockery ne fait consommer qu'UNE seule expectation par appel doWrite() réel, la seconde
    // reste non satisfaite même si le texte est bel et bien présent. Assertion directe sur la
    // chaîne de sortie capturée : robuste, sans dépendre du mécanisme de mock interne.
    // MCP: SELF (<5 lignes)
    // RAISON: preuve directe et sans ambiguïté du contenu réel du bilan.
    Artisan::call('news:fetch');
    $output = Artisan::output();

    expect($output)->toContain('0 publiés')
        ->and($output)->toContain('2 admissibles non publiés (drapeau désactivé)');

    // Les DEUX articles ont recu un appel IA : le quota de 1 n'a jamais ete atteint puisque
    // aucune publication reelle n'a eu lieu (todayIa reste a 0 tout au long de l'execution).
    Http::assertSentCount(2);

    $articleUn->refresh();
    $articleDeux->refresh();
    expect($articleUn->is_published)->toBeFalse()
        ->and($articleDeux->is_published)->toBeFalse()
        ->and($articleUn->structured_summary)->not->toBeNull()
        ->and($articleDeux->structured_summary)->not->toBeNull();
});

it('drapeau ON : le meme scenario de quota bas publie normalement et le bilan ne mentionne aucun admissible non publie', function () {
    config(['news.autopublish.enabled' => true]);
    Settings::set('news.max_ia_articles_per_day', 1, 'integer');
    napgBindFakeRssFetcher();
    napgFakeOpenRouterSuccess();

    $source = napgSource('SourceQuotaOnIA');
    napgArticle($source, 'quota-on-ia-un', 'OpenAI lance un nouveau modele IA generative pour les entreprises quebecoises');
    napgArticle($source, 'quota-on-ia-deux', 'Google devoile un modele IA generative concurrent pour les entreprises canadiennes');

    // Voir le commentaire du test précédent : Artisan::call()/output() plutôt que
    // $this->artisan()->expectsOutputToContain().
    Artisan::call('news:fetch');
    $output = Artisan::output();

    expect($output)->toContain('1 publiés')
        ->and($output)->not->toContain('admissibles non publiés');

    // Drapeau ON : le quota REEL de 1/jour s'applique bel et bien - un seul appel IA, le second
    // article est retenu par le quota (comportement inchangé, non lié à ce chantier).
    Http::assertSentCount(1);
});

// ── 2026-09-05, ticket #2248 : le garde-fou image ferme la porte de news:fetch ────────────────
//
// LE TROU, mesure ce jour : news:fetch ecrit is_published sans jamais passer par
// publishReadinessCheck(), ou vit le garde-fou image de #2244. Les trois autres portes de
// publication sont couvertes, celle-ci ne l'etait pas. Le drapeau NEWS_AUTOPUBLISH_ENABLED est
// eteint en production aujourd'hui - mais « dormant » n'est pas « ferme » (lecon #2244) : le jour
// ou il se rallume, la recidive que #2244 devait rendre impossible redevenait possible.
//
// LE TEMOIN EST REEL, pas une paraphrase : les DEUX tests « drapeau ON » de ce fichier sont
// tombes des l'application du correctif, parce que leurs articles n'avaient aucun credit d'image.
// C'etait le bon signal - un garde-fou qui ne casse AUCUN test existant merite qu'on se demande
// s'il mord vraiment.
it('drapeau ON mais AUCUNE image curatee : la fiche reste en brouillon et le bilan nomme la vraie cause', function () {
    config(['news.autopublish.enabled' => true]);
    napgBindFakeRssFetcher();
    napgFakeOpenRouterSuccess();

    $source = napgSource('SourceSansImage');
    // Credit VIDE : c'est exactement l'etat d'une fiche dont l'image est la carte de repli generee.
    $article = napgArticle($source, 'autopub-sans-image', 'OpenAI lance un nouveau modele IA generative pour les entreprises', null);

    Artisan::call('news:fetch');
    $output = Artisan::output();

    $article->refresh();
    expect($article->is_published)->toBeFalse();

    // Le bilan doit nommer la cause REELLE. Sans cette assertion, un refus pour la mauvaise raison
    // (le drapeau, le quota) passerait pour un succes du garde-fou.
    expect($output)->toContain("refuse(s) faute d'image curatee")
        ->and($output)->not->toContain('admissibles non publiés');
});

it('drapeau ON AVEC image curatee : la publication automatique fonctionne toujours', function () {
    config(['news.autopublish.enabled' => true]);
    napgBindFakeRssFetcher();
    napgFakeOpenRouterSuccess();

    $source = napgSource('SourceAvecImage');
    $article = napgArticle($source, 'autopub-avec-image', 'OpenAI lance un nouveau modele IA generative pour les entreprises', 'Photo : MEMORA solutions');

    Artisan::call('news:fetch');
    $output = Artisan::output();

    // Le SECOND sens du temoin : le garde-fou ne mord QUE sur l'absence d'image, il n'a pas
    // ferme la porte pour tout le monde.
    expect($article->refresh()->is_published)->toBeTrue()
        ->and($output)->not->toContain("refuse(s) faute d'image curatee");
});
