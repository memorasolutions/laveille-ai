<?php

declare(strict_types=1);

/**
 * Tests Actus 2.0 : fusion multi-sources des actualités (design doc
 * docs/specs/2026-08-09-actus-fusion-design.md). Couvre les critères d'acceptation section 12.
 *
 * RssFetcherService est remplacé par un double qui ne fait rien (les articles sont pré-semés
 * directement en base, comme s'ils venaient d'être récupérés par le flux RSS) : aucun appel
 * réseau réel, conformément à la consigne. Les appels OpenRouter sont interceptés via
 * Http::fake() - aucun appel réseau réel non plus.
 *
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project laveille.ai
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Modules\News\Jobs\AutoDetectNewsToolsJob;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsDedupLog;
use Modules\News\Models\NewsSource;
use Modules\News\Services\RssFetcherService;

uses(Tests\TestCase::class, RefreshDatabase::class);

// ── Helpers locaux (préfixés Nfus pour éviter tout conflit inter-fichiers) ────

// ── Correctif 2026-08-09 : borne mémoire de la file (news.fetch_backlog_hours) ────

it('borne de 48 h : un article plus vieux que la fenetre n est plus retraite par news:fetch', function () {
    config(['news.fusion.enabled' => false, 'news.fetch_backlog_hours' => 48]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess();

    $source = nfusSource('SourceBacklog');
    $recent = nfusArticle($source, 'backlog-recent', 'OpenAI annonce un nouveau modele IA generative pour le grand public');
    $vieux = nfusArticle($source, 'backlog-vieux', 'Ancienne annonce IA generative jamais traitee par le pipeline');
    $vieux->timestamps = false;
    $vieux->forceFill(['created_at' => now()->subDays(3)])->saveQuietly();

    $this->artisan('news:fetch')->assertSuccessful();

    $recent->refresh();
    $vieux->refresh();

    expect($recent->structured_summary)->not->toBeNull()
        ->and($vieux->structured_summary)->toBeNull()
        ->and($vieux->summary)->toBeNull();
    Http::assertSentCount(1);
});

function nfusSource(string $name): NewsSource
{
    return NewsSource::create([
        'name' => $name,
        'url' => 'https://exemple.com/'.\Illuminate\Support\Str::slug($name).'-'.uniqid(),
        'language' => 'fr',
    ]);
}

function nfusArticle(NewsSource $source, string $slug, string $title, $pubDate = null): NewsArticle
{
    return NewsArticle::create([
        'news_source_id' => $source->id,
        'title' => $title,
        'guid' => 'guid-'.$slug,
        'url' => 'https://exemple.com/'.$slug,
        'description' => $title.' - intelligence artificielle : article de test avec suffisamment de contenu pour passer le pré-filtre de mots-clés du pipeline.',
        'pub_date' => $pubDate ?? now(),
        'is_published' => false,
    ]);
}

/**
 * Neutralise RssFetcherService (les articles sont déjà pré-semés) : zéro appel réseau réel.
 *
 * Contrat mis à jour (design doc "Actus - zéro copie du texte source", 2026-08-13, section
 * 4.1) : fetchSource() retourne désormais ['count' => int, 'texts' => array<id, string>], le
 * texte source n'étant plus jamais lu depuis la colonne description en production. Ce double
 * simule ce que RssFetcherService aurait "extrait" en restituant la description de fixture de
 * chaque article non encore résumé de la source - AUCUN appel réseau, réel ou faké : la
 * colonne description ne sert ici QUE de stockage de fixture, jamais de véhicule en production.
 */
function nfusBindFakeRssFetcher(): void
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

/** Fake la cascade OpenRouter (Http::fake) : succès systématique sur le premier modèle. */
function nfusFakeOpenRouterSuccess(array $overrides = []): void
{
    $payload = array_merge([
        'score' => 8,
        'score_justification' => 'Pertinent pour le test.',
        'category' => 'IA générative',
        'impact' => 'Élevé',
        'tldr' => 'Résumé factuel de test, trente à quarante mots pour respecter le format attendu ici.',
        'hook' => 'Accroche factuelle de test résumant fidèlement le contenu source fourni au modèle.',
        'quote' => null,
        'key_points' => ['Point détaillé un du test.', 'Point détaillé deux du test.'],
        'key_stat' => null,
        'expert_name' => null,
        'expert_role' => null,
        'why_important' => 'Explication de test sur l\'impact concret pour les professionnels visés.',
        'audience' => ['développeurs'],
        'seo_title' => 'Titre SEO de test',
        'meta_description' => 'Description meta de test.',
        'faq_question' => 'Question de test ?',
        'faq_answer' => 'Réponse de test détaillée.',
    ], $overrides);

    Http::fake([
        'openrouter.ai/*' => Http::response([
            'choices' => [['message' => ['content' => json_encode($payload)]]],
        ], 200),
    ]);
}

beforeEach(function () {
    config(['news.dedup_skip_enabled' => true]);
});

// ── Critère 1 : drapeau OFF = zéro diff, zéro appel aux nouveaux services ─────

it('drapeau OFF : aucun article marque fusion, un appel HTTP par article (identique a aujourd hui)', function () {
    config(['news.fusion.enabled' => false]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess();

    $source = nfusSource('SourceOff');
    nfusArticle($source, 'off-1', 'OpenAI lance un nouveau modele IA generative pour les entreprises');
    nfusArticle($source, 'off-2', 'Google devoile son propre modele IA generative concurrent');

    $this->artisan('news:fetch')->assertSuccessful();

    expect(NewsArticle::where('is_comparative_digest', true)->count())->toBe(0)
        ->and(NewsArticle::whereNotNull('is_potential_duplicate_of')->count())->toBe(0);
    Http::assertSentCount(2);
});

// ── Critère 3 : article sans jumeau = chemin singleton inchangé, même drapeau ON ─────

it('drapeau ON : un article sans jumeau suit le chemin singleton habituel', function () {
    // Publication automatique activée explicitement (2026-08-14) : par défaut FALSE depuis la
    // suspension de la publication auto, ce test vérifie le chemin singleton EN PUBLICATION.
    config(['news.fusion.enabled' => true, 'news.autopublish.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess();

    $source = nfusSource('SourceSolo');
    $article = nfusArticle($source, 'solo-1', 'Amazon annonce un nouveau centre de donnees pour le cloud computing');

    $this->artisan('news:fetch')->assertSuccessful();

    $article->refresh();
    expect($article->is_comparative_digest)->toBeFalse()
        ->and($article->is_potential_duplicate_of)->toBeNull()
        ->and($article->is_published)->toBeTrue()
        ->and($article->structured_summary)->not->toBeNull();
    Http::assertSentCount(1);
});

// ── Critères 2, 4, 5 : groupe de 2+ = UNE fiche comparative, UN seul appel IA, sources[] ─────

it('deux articles au sujet partage produisent une fiche comparative avec un membre et sources[]', function () {
    // Publication automatique activée explicitement (2026-08-14) : par défaut FALSE depuis la
    // suspension de la publication auto - ce test vérifie que le membre hérite bien du statut
    // publié de sa fiche comparative, donc la fiche doit réellement être publiée ici.
    config(['news.fusion.enabled' => true, 'news.autopublish.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess(['sources' => [
        ['source_name' => 'SourceA', 'author' => null, 'url' => 'https://exemple.com/grp-a', 'angle' => null],
        ['source_name' => 'SourceB', 'author' => 'Jane Doe', 'url' => 'https://exemple.com/grp-b', 'angle' => null],
    ]]);

    $sourceA = nfusSource('SourceA');
    $sourceB = nfusSource('SourceB');
    nfusArticle($sourceA, 'grp-a', 'Microsoft lance un agent IA generative pour Word et Excel', now()->subHours(2));
    nfusArticle($sourceB, 'grp-b', 'Microsoft devoile un agent IA generative integre a Word', now()->subHour());

    $this->artisan('news:fetch')->assertSuccessful();

    Http::assertSentCount(1);

    expect(NewsArticle::where('is_comparative_digest', true)->count())->toBe(1);
    $digest = NewsArticle::where('is_comparative_digest', true)->first();

    expect(NewsArticle::where('is_potential_duplicate_of', $digest->id)->count())->toBe(1);
    expect($digest->structured_summary['sources'] ?? null)->toHaveCount(2)
        ->and($digest->structured_summary['sources'][0]['source_name'])->toBe('SourceA')
        ->and(array_key_exists('author', $digest->structured_summary['sources'][1]))->toBeTrue();

    $member = NewsArticle::where('is_potential_duplicate_of', $digest->id)->first();
    expect($member->seo_status)->toBe('noindex')
        ->and($member->is_published)->toBeTrue()
        ->and($member->dedup_reason)->not->toBeNull();

    expect(NewsDedupLog::where('new_article_id', $digest->id)->where('action', 'fusion_grouped')->count())->toBe(1);
});

it('un groupe de 3 articles ne declenche exactement qu un seul appel HTTP', function () {
    config(['news.fusion.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess(['sources' => [
        ['source_name' => 'S1', 'author' => null, 'url' => 'https://exemple.com/g1', 'angle' => null],
        ['source_name' => 'S2', 'author' => null, 'url' => 'https://exemple.com/g2', 'angle' => null],
        ['source_name' => 'S3', 'author' => null, 'url' => 'https://exemple.com/g3', 'angle' => null],
    ]]);

    $s1 = nfusSource('S1');
    $s2 = nfusSource('S2');
    $s3 = nfusSource('S3');
    nfusArticle($s1, 'g1', 'OpenAI publie GPT nouvelle version pour les entreprises technologiques', now()->subHours(3));
    nfusArticle($s2, 'g2', 'OpenAI devoile GPT nouvelle version destinee aux entreprises technologiques', now()->subHours(2));
    nfusArticle($s3, 'g3', 'OpenAI lance GPT nouvelle version pour entreprises technologiques mondiales', now()->subHour());

    $this->artisan('news:fetch')->assertSuccessful();

    Http::assertSentCount(1);

    $digest = NewsArticle::where('is_comparative_digest', true)->first();
    expect($digest)->not->toBeNull()
        ->and(NewsArticle::where('is_potential_duplicate_of', $digest->id)->count())->toBe(2);
});

// ── Critère 6 : angle_qc_ca n'apparaît que si fourni, jamais forcé ─────

it('angle_qc_ca reste absent si le champ n est pas retourne par l IA', function () {
    config(['news.fusion.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess([
        'sources' => [
            ['source_name' => 'SourceA', 'author' => null, 'url' => 'https://exemple.com/qc-a', 'angle' => null],
            ['source_name' => 'SourceB', 'author' => null, 'url' => 'https://exemple.com/qc-b', 'angle' => null],
        ],
        'angle_qc_ca' => null,
    ]);

    $sourceA = nfusSource('SourceQcA');
    $sourceB = nfusSource('SourceQcB');
    nfusArticle($sourceA, 'qc-a', 'Nvidia presente une nouvelle puce IA pour les centres de donnees', now()->subHours(2));
    nfusArticle($sourceB, 'qc-b', 'Nvidia devoile une nouvelle puce IA pour les centres de donnees mondiaux', now()->subHour());

    $this->artisan('news:fetch')->assertSuccessful();

    $digest = NewsArticle::where('is_comparative_digest', true)->first();
    expect($digest)->not->toBeNull();
    // array_key_exists (pas ??) : une clé présente avec valeur null reste indétectable via ??
    // (isset() renvoie false sur null) - on vérifie ici le comportement RÉEL consommé par
    // show.blade.php, qui teste !empty($ss['angle_qc_ca']), jamais affiché si vide/absent/null.
    expect(empty($digest->structured_summary['angle_qc_ca']))->toBeTrue();
});

// ── Critère 7 : quota d'indexation fixe, jamais un score IA comme filtre ─────

it('au dela du quota d indexation, la fiche comparative supplementaire est creee noindex des la creation', function () {
    // Publication automatique activée explicitement (2026-08-14) : ce test vérifie que le quota
    // d'INDEXATION (seo_status) reste indépendant du statut publié - il faut donc une fiche
    // réellement publiée pour que la distinction ait un sens.
    config(['news.fusion.enabled' => true, 'news.fusion.max_indexed_digests_per_day' => 1, 'news.autopublish.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess();

    // Quota déjà atteint : un digest indexé existe pour aujourd'hui.
    $preSource = nfusSource('Pre');
    $preDigest = nfusArticle($preSource, 'pre-digest', 'Ancienne fiche comparative deja indexee aujourd hui');
    $preDigest->update(['is_comparative_digest' => true, 'is_published' => true, 'seo_status' => 'index', 'structured_summary' => ['hook' => 'x', 'sources' => []]]);

    $s1 = nfusSource('Q1');
    $s2 = nfusSource('Q2');
    nfusArticle($s1, 'q1', 'Nvidia presente une nouvelle puce IA pour les centres de donnees mondiaux', now()->subHours(2));
    nfusArticle($s2, 'q2', 'Nvidia devoile une nouvelle puce IA destinee aux centres de donnees mondiaux', now()->subHour());

    $this->artisan('news:fetch')->assertSuccessful();

    $newDigest = NewsArticle::where('is_comparative_digest', true)->where('id', '!=', $preDigest->id)->first();

    expect($newDigest)->not->toBeNull()
        ->and($newDigest->is_published)->toBeTrue()
        ->and($newDigest->seo_status)->toBe('noindex');
});

// ── Arbitrage section 14 : absorption (2e fiche même jour / article tardif) ─────

it('un article tardif qui matche une fiche comparative existante est absorbe sans nouvel appel IA', function () {
    config(['news.fusion.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess();

    $existingSource = nfusSource('Existing');
    $digest = nfusArticle($existingSource, 'digest-existant', 'Meta lance un nouveau modele IA generative pour les entreprises', now()->subHours(5));
    $digest->update(['is_comparative_digest' => true, 'is_published' => true, 'seo_status' => 'index', 'structured_summary' => ['hook' => 'x', 'sources' => []]]);

    $lateSource = nfusSource('Late');
    nfusArticle($lateSource, 'tardif', 'Meta devoile un nouveau modele IA generative destine aux entreprises', now());

    $this->artisan('news:fetch')->assertSuccessful();

    Http::assertNothingSent();

    $late = NewsArticle::where('guid', 'guid-tardif')->first();
    expect($late->is_potential_duplicate_of)->toBe($digest->id)
        ->and($late->seo_status)->toBe('noindex')
        ->and($late->is_published)->toBeTrue();

    expect(NewsDedupLog::where('action', 'fusion_grouped')->where('matched_article_id', $late->id)->count())->toBe(1);
});

// ── Correctif 1 (revue adversariale) : republication d'un SINGLETON = toujours sautee ─────

it('une republication d un article singleton deja publie reste sautee, jamais publiee en double, meme drapeau ON', function () {
    config(['news.fusion.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess();

    // Article A : singleton déjà publié lors d'une exécution ANTÉRIEURE (ni digest, ni membre).
    $originalSource = nfusSource('Original');
    $original = nfusArticle($originalSource, 'original-singleton', 'IBM annonce un partenariat IA generative avec des universites canadiennes', now()->subHours(3));
    $original->update([
        'is_published' => true,
        'structured_summary' => ['hook' => 'x'],
        'is_comparative_digest' => false,
        'is_potential_duplicate_of' => null,
    ]);

    // Article B : republication quasi identique, source différente, arrivée à l'exécution suivante.
    $repubSource = nfusSource('Republication');
    nfusArticle($repubSource, 'repub-singleton', 'IBM devoile un partenariat IA generative avec des universites canadiennes', now()->subHour());

    $this->artisan('news:fetch')->assertSuccessful();

    // Aucun appel IA : la republication est interceptée par DEDUP-SKIP avant la déférence fusion.
    Http::assertNothingSent();

    $repub = NewsArticle::where('guid', 'guid-repub-singleton')->first();
    expect($repub->is_published)->toBeFalse()
        ->and($repub->is_potential_duplicate_of)->toBeNull()
        ->and($repub->is_comparative_digest)->toBeFalse()
        ->and($repub->summary)->toBe('[doublon detecte - IA evitee]');

    expect(NewsDedupLog::where('matched_article_id', $repub->id)->count())->toBe(0);
});

// ── Correctif 2 (revue adversariale) : absorption silencieuse, aucun effet de bord observer ─────

it('l absorption d un membre ne cree pas de lien court et ne declenche pas la detection auto d outils', function () {
    config(['news.fusion.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess();

    $existingSource = nfusSource('QuietExisting');
    $digest = nfusArticle($existingSource, 'quiet-digest', 'Salesforce lance un nouvel agent IA generative pour les entreprises', now()->subHours(5));
    // Publication du digest DE FIXTURE (hors périmètre du test) AVANT Bus::fake() : sinon cette
    // publication normale (non-quiet, comportement voulu pour un digest) serait comptée à tort
    // comme un effet de bord de l'ABSORPTION du membre, qui est ce que ce test vérifie.
    $digest->update(['is_comparative_digest' => true, 'is_published' => true, 'seo_status' => 'index', 'structured_summary' => ['hook' => 'x', 'sources' => []]]);

    $lateSource = nfusSource('QuietLate');
    nfusArticle($lateSource, 'quiet-tardif', 'Salesforce devoile un nouvel agent IA generative pour les entreprises', now());

    Bus::fake();

    $this->artisan('news:fetch')->assertSuccessful();

    $late = NewsArticle::where('guid', 'guid-quiet-tardif')->first();
    expect($late->is_potential_duplicate_of)->toBe($digest->id)
        ->and($late->is_published)->toBeTrue()
        ->and($late->short_url_id)->toBeNull();

    Bus::assertNotDispatched(AutoDetectNewsToolsJob::class);
});

// ── Critère 6 (test contrat) : flattenStructuredSummary()/adminShareContents() tolèrent les nouvelles clés ─────

it('flattenStructuredSummary et adminShareContents tolerent les nouvelles cles sans casser', function () {
    $source = nfusSource('Compat');
    $article = nfusArticle($source, 'compat-1', 'Test compatibilite JSON etendu Actus 2.0');
    $article->update([
        'is_published' => true,
        'seo_title' => 'Titre test',
        'structured_summary' => [
            'hook' => 'Accroche.',
            'key_points' => ['Point un.'],
            'why_important' => 'Important.',
            'tldr' => 'Tldr.',
            'quote' => 'Citation.',
            'faq_question' => 'Q ?',
            'faq_answer' => 'R.',
            'sources' => [['source_name' => 'S1', 'author' => null, 'url' => 'https://x', 'angle' => null]],
            'divergences' => ['Divergence test.'],
            'archive_context' => ['summary' => 'Contexte.', 'related' => []],
            'angle_qc_ca' => null,
        ],
    ]);

    expect(fn () => $article->flattenStructuredSummary())->not->toThrow(\Throwable::class);
    expect($article->flattenStructuredSummary())->toContain('Accroche.');

    expect(fn () => $article->adminShareContents())->not->toThrow(\Throwable::class);
    expect($article->adminShareContents())->toBeArray();
});

// ── Critère 8 : le sitemap news exclut les membres ─────

it('le sitemap news exclut les membres avec is_potential_duplicate_of non nul', function () {
    $source = nfusSource('Sitemap');
    $digest = nfusArticle($source, 'sm-digest', 'Fiche comparative sitemap test');
    $digest->update(['is_published' => true, 'seo_status' => 'index', 'is_comparative_digest' => true, 'slug' => 'sm-digest-'.uniqid()]);

    $member = nfusArticle($source, 'sm-member', 'Membre sitemap test');
    $member->update(['is_published' => true, 'seo_status' => 'noindex', 'is_potential_duplicate_of' => $digest->id, 'slug' => 'sm-member-'.uniqid()]);

    $response = $this->get(route('news.sitemap'));
    $response->assertOk();

    expect($response->getContent())->toContain($digest->slug)
        ->and($response->getContent())->not->toContain($member->slug);
});

// ── Critère 9 : auto-guérison de PruneSeoCommand n'affecte jamais un membre (R3) ─────

it('news prune-seo ne reindexe jamais un membre meme si son views_count est eleve', function () {
    Http::fake();
    config(['news.seo_prune' => ['enabled' => true, 'min_age_months' => 12, 'max_views' => 30, 'gone' => ['enabled' => false, 'age_months' => 24, 'max_views' => 5]]]);

    $source = nfusSource('Prune');
    $digest = nfusArticle($source, 'prune-digest', 'Fiche comparative prune test');
    $digest->update(['is_published' => true, 'seo_status' => 'index', 'is_comparative_digest' => true]);

    $member = nfusArticle($source, 'prune-member', 'Membre prune test');
    $member->update([
        'is_published' => true,
        'seo_status' => 'noindex',
        'is_potential_duplicate_of' => $digest->id,
        'views_count' => 500,
        'pub_date' => now()->subMonths(13),
    ]);

    $this->artisan('news:prune-seo')->assertSuccessful();

    expect($member->fresh()->seo_status)->toBe('noindex');
});

// ── Observabilité Actus 2.0 (2026-08-11) : canal 'fusion' dédié + volume borné ─────

/** Chemin du fichier daily du jour pour le canal 'fusion' (voir config/logging.php). */
function nfusFusionLogPath(): string
{
    return storage_path('logs/fusion-'.now()->format('Y-m-d').'.log');
}

/** Repart d'un fichier de log 'fusion' vide pour isoler le contenu produit par CE test. */
function nfusResetFusionLog(): void
{
    @unlink(nfusFusionLogPath());
}

it('le canal fusion recoit la ligne de synthese meme avec un niveau de log global tres restrictif', function () {
    // Simule la config de PRODUCTION diagnostiquée (LOG_LEVEL=error) : les canaux par défaut du
    // projet sont réglés au niveau le plus restrictif possible (emergency), pour prouver que
    // SEUL le hard-code 'level' => 'info' du canal 'fusion' (config/logging.php) rend la ligne
    // de synthèse observable - indépendamment de tout réglage global.
    config([
        'logging.channels.daily.level' => 'emergency',
        'logging.channels.single.level' => 'emergency',
        'news.fusion.enabled' => true,
    ]);
    nfusResetFusionLog();
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess(['sources' => [
        ['source_name' => 'SourceA', 'author' => null, 'url' => 'https://exemple.com/obs-a', 'angle' => null],
        ['source_name' => 'SourceB', 'author' => null, 'url' => 'https://exemple.com/obs-b', 'angle' => null],
    ]]);

    $sourceA = nfusSource('SourceObsA');
    $sourceB = nfusSource('SourceObsB');
    nfusArticle($sourceA, 'obs-a', 'Microsoft lance un agent IA generative pour Word et Excel', now()->subHours(2));
    nfusArticle($sourceB, 'obs-b', 'Microsoft devoile un agent IA generative integre a Word', now()->subHour());

    $this->artisan('news:fetch')->assertSuccessful();

    expect(file_exists(nfusFusionLogPath()))->toBeTrue();
    $content = file_get_contents(nfusFusionLogPath());
    expect($content)->toContain('FUSION-SYNTHESE');
});

it('le volume de lignes fusion reste borne meme avec beaucoup de candidats rejetes dans la fenetre', function () {
    config(['news.fusion.enabled' => true]);
    nfusResetFusionLog();
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess();

    // 6 articles qui ne partagent quasiment rien entre eux sauf, pour certains, une entite
    // isolee ("Google") : peu ou pas de groupe formé, mais de nombreuses paires (C(6,2)=15)
    // sont évaluées et plusieurs sont de vrais quasi-regroupements bruyants.
    $source = nfusSource('SourceVolume');
    nfusArticle($source, 'vol-1', 'Google publie une mise a jour majeure pour ses services cloud', now()->subHours(6));
    nfusArticle($source, 'vol-2', 'Google investit dans un nouveau centre de recherche universitaire', now()->subHours(5));
    nfusArticle($source, 'vol-3', 'Google embauche des experts en robotique pour son laboratoire', now()->subHours(4));
    nfusArticle($source, 'vol-4', 'Google collabore avec des hopitaux pour un projet de sante', now()->subHours(3));
    nfusArticle($source, 'vol-5', 'Tesla devoile une nouvelle usine de batteries au Nevada', now()->subHours(2));
    nfusArticle($source, 'vol-6', 'Amazon annonce un nouveau centre de donnees au Canada', now()->subHour());

    $this->artisan('news:fetch')->assertSuccessful();

    $content = file_exists(nfusFusionLogPath()) ? file_get_contents(nfusFusionLogPath()) : '';
    $lines = array_filter(explode(PHP_EOL, trim($content)));

    $synthesisLines = array_filter($lines, static fn ($l) => str_contains($l, 'FUSION-SYNTHESE'));
    $quasiLines = array_filter($lines, static fn ($l) => str_contains($l, 'FUSION-QUASI'));

    // UNE seule ligne de synthèse par exécution, et AU PLUS 3 lignes de quasi-regroupement,
    // quel que soit le nombre de paires (15 ici) réellement comparées par la boucle quadratique.
    expect($synthesisLines)->toHaveCount(1);
    expect(count($quasiLines))->toBeLessThanOrEqual(3);
});

// ── Critère 10 : désactiver le drapeau après usage ne supprime/modifie rien ─────

it('desactiver le drapeau apres usage ne supprime ni ne modifie une fiche deja publiee', function () {
    // Publication automatique activée explicitement (2026-08-14) pour ce premier passage : le
    // titre du test suppose une fiche RÉELLEMENT publiée avant le rollback (défaut désormais FALSE).
    config(['news.fusion.enabled' => true, 'news.autopublish.enabled' => true]);
    nfusBindFakeRssFetcher();
    nfusFakeOpenRouterSuccess(['sources' => [
        ['source_name' => 'SourceA', 'author' => null, 'url' => 'https://exemple.com/rb-a', 'angle' => null],
        ['source_name' => 'SourceB', 'author' => null, 'url' => 'https://exemple.com/rb-b', 'angle' => null],
    ]]);

    $sourceA = nfusSource('SourceRbA');
    $sourceB = nfusSource('SourceRbB');
    nfusArticle($sourceA, 'rb-a', 'Anthropic lance un nouveau modele Claude pour les entreprises', now()->subHours(2));
    nfusArticle($sourceB, 'rb-b', 'Anthropic devoile un nouveau modele Claude destine aux entreprises', now()->subHour());

    $this->artisan('news:fetch')->assertSuccessful();

    $digestId = NewsArticle::where('is_comparative_digest', true)->value('id');
    expect($digestId)->not->toBeNull();

    // Rollback : drapeau désactivé, deuxième exécution sans nouvel article à traiter.
    config(['news.fusion.enabled' => false]);
    nfusBindFakeRssFetcher();
    $this->artisan('news:fetch')->assertSuccessful();

    expect(NewsArticle::where('is_comparative_digest', true)->count())->toBe(1)
        ->and(NewsArticle::find($digestId)->is_comparative_digest)->toBeTrue();
});
