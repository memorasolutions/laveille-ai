<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Analytics\Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Modules\Analytics\Models\Ga4Daily;
use Tests\TestCase;

class Ga4DailyTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_migration_cree_la_table_analytics_ga4_daily(): void
    {
        $this->assertTrue(Schema::hasTable('analytics_ga4_daily'));
    }

    public function test_une_ligne_peut_etre_inseree_et_relue_avec_le_bon_cast_de_date(): void
    {
        $ligne = Ga4Daily::create([
            'date' => '2026-09-29',
            'url' => '/glossaire/rag',
            'sessions' => 42,
            'active_users' => 30,
            'screen_page_views' => 55,
            'engaged_sessions' => 20,
            'avg_engagement_time_seconds' => 65,
            'conversions' => 3,
            'content_id' => 100,
            'content_type' => 'dictionary_term',
        ]);

        $relue = Ga4Daily::find($ligne->id);

        $this->assertInstanceOf(Carbon::class, $relue->date);
        $this->assertSame('2026-09-29', $relue->date->format('Y-m-d'));
        $this->assertSame('/glossaire/rag', $relue->url);
        $this->assertSame(42, $relue->sessions);
        $this->assertSame(100, $relue->content_id);
        $this->assertSame('dictionary_term', $relue->content_type);
    }

    public function test_la_contrainte_unique_date_url_empeche_un_doublon(): void
    {
        Ga4Daily::create(['date' => '2026-09-29', 'url' => '/glossaire/rag']);

        $this->expectException(QueryException::class);

        Ga4Daily::create(['date' => '2026-09-29', 'url' => '/glossaire/rag']);
    }

    public function test_updateorcreate_met_a_jour_au_lieu_de_dupliquer(): void
    {
        Ga4Daily::updateOrCreate(
            ['date' => '2026-09-29', 'url' => '/glossaire/rag'],
            ['sessions' => 10]
        );

        Ga4Daily::updateOrCreate(
            ['date' => '2026-09-29', 'url' => '/glossaire/rag'],
            ['sessions' => 25]
        );

        $this->assertSame(
            1,
            Ga4Daily::where('date', '2026-09-29')->where('url', '/glossaire/rag')->count()
        );
        $this->assertSame(
            25,
            Ga4Daily::where('date', '2026-09-29')->where('url', '/glossaire/rag')->first()->sessions
        );
    }
}
