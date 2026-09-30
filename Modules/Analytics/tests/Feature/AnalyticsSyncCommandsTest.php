<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Analytics\Contracts\AnalyticsGateway;
use Modules\Analytics\Models\AnalyticsCollectionRun;
use Modules\Analytics\Models\Ga4Daily;
use Modules\Analytics\Models\GscDaily;
use Modules\News\Models\NewsArticle;
use Modules\News\Models\NewsSource;
use RuntimeException;
use Tests\TestCase;

class AnalyticsSyncCommandsTest extends TestCase
{
    use RefreshDatabase;

    private string $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        // ACTION: fichier temporaire d'identifiants pour les tests (seule son EXISTENCE compte, le contenu est ignore car le gateway est simule)
        // SELF: 2 lignes de test
        // RAISON: tempnam est banni par le prereglage securite d'ArchTest ; on cree le fichier explicitement
        $this->credentials = sys_get_temp_dir().'/analytics-ga-cred-test.json';
        file_put_contents($this->credentials, '{}');
        config([
            'analytics.enabled' => true,
            'analytics.ga4_property_id' => '123456',
            'analytics.gsc_site_url' => 'sc-domain:exemple.ca',
            'analytics.google_credentials' => $this->credentials,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentials);
        parent::tearDown();
    }

    /** @param  array<int, array<string, mixed>>  $ga4 */
    private function fakeGateway(array $ga4 = [], array $gsc = [], ?\Throwable $throw = null): void
    {
        $this->app->instance(AnalyticsGateway::class, new class($ga4, $gsc, $throw) implements AnalyticsGateway
        {
            public function __construct(private array $ga4, private array $gsc, private ?\Throwable $throw) {}

            public function fetchGa4Daily(string $date): array
            {
                return $this->throw ? throw $this->throw : $this->ga4;
            }

            public function fetchGscDaily(string $date): array
            {
                return $this->throw ? throw $this->throw : $this->gsc;
            }
        });
    }

    private function ga4Row(string $url, int $sessions = 10, float $duration = 100.0): array
    {
        return ['url' => $url, 'sessions' => $sessions, 'active_users' => 8, 'screen_page_views' => 20,
            'engaged_sessions' => 6, 'user_engagement_duration' => $duration];
    }

    public function test_commande_ignoree_quand_un_identifiant_manque(): void
    {
        config(['analytics.ga4_property_id' => null, 'analytics.gsc_site_url' => null]);
        $this->fakeGateway([$this->ga4Row('/x')]);

        $this->artisan('analytics:sync-ga4', ['--date' => '2026-09-28'])->assertExitCode(0);
        $this->artisan('analytics:sync-gsc', ['--date' => '2026-09-28'])->assertExitCode(0);

        $this->assertSame(2, AnalyticsCollectionRun::where('status', 'skipped')->count());
        $this->assertStringContainsString('ANALYTICS_GA4_PROPERTY_ID', AnalyticsCollectionRun::where('source', 'ga4')->value('message'));
        $this->assertSame(0, Ga4Daily::count() + GscDaily::count());
    }

    public function test_commande_ignoree_quand_le_module_est_desactive(): void
    {
        config(['analytics.enabled' => false]);
        $this->fakeGateway([$this->ga4Row('/x')]);

        $this->artisan('analytics:sync-ga4', ['--date' => '2026-09-28'])->assertExitCode(0);

        $this->assertSame('skipped', AnalyticsCollectionRun::first()->status);
        $this->assertSame(0, Ga4Daily::count());
    }

    public function test_gateway_fictif_deux_lignes_deux_upserts_et_un_journal_success(): void
    {
        $this->fakeGateway([$this->ga4Row('/a'), $this->ga4Row('https://laveille.ai/b/?utm=1', 4, 90)]);

        $this->artisan('analytics:sync-ga4', ['--date' => '2026-09-28'])->assertExitCode(0);

        $this->assertSame(2, Ga4Daily::count());
        $run = AnalyticsCollectionRun::where('source', 'ga4')->firstOrFail();
        $this->assertSame('success', $run->status);
        $this->assertSame(2, $run->rows_upserted);
        $this->assertSame('2026-09-28', $run->collected_for->toDateString());
        $b = Ga4Daily::where('url', '/b')->firstOrFail();
        $this->assertSame(23, $b->avg_engagement_time_seconds); // 90 / 4 = 22,5 arrondi
        $this->assertSame(0, $b->conversions);
    }

    public function test_idempotence_et_mise_a_jour(): void
    {
        $this->fakeGateway([$this->ga4Row('/a', 10)]);
        $this->artisan('analytics:sync-ga4', ['--date' => '2026-09-28'])->assertExitCode(0);
        $this->artisan('analytics:sync-ga4', ['--date' => '2026-09-28'])->assertExitCode(0);
        $this->assertSame(1, Ga4Daily::count());
        $this->assertSame(1, AnalyticsCollectionRun::count());

        $this->fakeGateway([$this->ga4Row('/a', 25)]);
        $this->artisan('analytics:sync-ga4', ['--date' => '2026-09-28'])->assertExitCode(0);
        $this->assertSame(1, Ga4Daily::count());
        $this->assertSame(25, Ga4Daily::first()->sessions);
    }

    public function test_gsc_agrege_les_urls_equivalentes_et_recalcule_ctr_et_position(): void
    {
        $this->fakeGateway([], [
            ['url' => 'https://laveille.ai/glossaire/rag', 'clicks' => 2, 'impressions' => 100, 'ctr' => 0.02, 'position' => 4.0],
            ['url' => 'https://laveille.ai/glossaire/rag/?x=1', 'clicks' => 3, 'impressions' => 100, 'ctr' => 0.03, 'position' => 6.0],
        ]);

        $this->artisan('analytics:sync-gsc', ['--date' => '2026-09-28'])->assertExitCode(0);

        $this->assertSame(1, GscDaily::count());
        $ligne = GscDaily::first();
        $this->assertSame(5, $ligne->clicks);
        $this->assertSame(200, $ligne->impressions);
        $this->assertEquals(0.025, $ligne->ctr);
        $this->assertEquals(5.0, $ligne->position);
    }

    public function test_resolution_content_id_cablee_a_l_ingestion(): void
    {
        $source = NewsSource::firstOrCreate(
            ['url' => 'https://analytics-sync.exemple.com/rss'],
            ['name' => 'Source sync test', 'language' => 'fr', 'active' => true]
        );
        $article = NewsArticle::create([
            'news_source_id' => $source->id, 'title' => 'Titre', 'guid' => 'guid-sync', 'url' => 'https://exemple.com/sync',
            'description' => '', 'summary' => 'Résumé', 'slug' => 'article-sync', 'pub_date' => now(),
            'is_published' => true, 'seo_status' => 'index',
        ]);
        $this->fakeGateway([$this->ga4Row('/actualites/article-sync'), $this->ga4Row('/page-libre')]);

        $this->artisan('analytics:sync-ga4', ['--date' => '2026-09-28'])->assertExitCode(0);

        $ligne = Ga4Daily::where('url', '/actualites/article-sync')->firstOrFail();
        $this->assertSame($article->id, $ligne->content_id);
        $this->assertSame('news_article', $ligne->content_type);
        $libre = Ga4Daily::where('url', '/page-libre')->firstOrFail();
        $this->assertNull($libre->content_id);
    }

    public function test_exception_du_gateway_donne_un_journal_error_sans_donnees_et_code_zero(): void
    {
        $this->fakeGateway([], [], new RuntimeException('quota dépassé'));

        $this->artisan('analytics:sync-ga4', ['--date' => '2026-09-28'])->assertExitCode(0);
        $this->artisan('analytics:sync-gsc', ['--date' => '2026-09-28'])->assertExitCode(0);

        $this->assertSame(2, AnalyticsCollectionRun::where('status', 'error')->count());
        $this->assertStringContainsString('quota dépassé', AnalyticsCollectionRun::where('source', 'gsc')->value('message'));
        $this->assertSame(0, Ga4Daily::count() + GscDaily::count());
    }
}
