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
use Modules\Analytics\Models\AnalyticsCollectionRun;
use Tests\TestCase;

class AnalyticsCollectionRunTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_migration_cree_la_table_analytics_collection_runs(): void
    {
        $this->assertTrue(Schema::hasTable('analytics_collection_runs'));
    }

    public function test_une_ligne_peut_etre_inseree_et_relue_avec_le_bon_cast_de_date(): void
    {
        $ligne = AnalyticsCollectionRun::create([
            'source' => 'ga4',
            'collected_for' => '2026-09-29',
            'ran_at' => '2026-09-29 10:00:00',
            'status' => 'success',
            'rows_upserted' => 87,
            'message' => null,
        ]);

        $relue = AnalyticsCollectionRun::find($ligne->id);

        $this->assertInstanceOf(Carbon::class, $relue->collected_for);
        $this->assertSame('2026-09-29', $relue->collected_for->format('Y-m-d'));
        $this->assertInstanceOf(Carbon::class, $relue->ran_at);
        $this->assertSame('ga4', $relue->source);
        $this->assertSame('success', $relue->status);
        $this->assertSame(87, $relue->rows_upserted);
    }

    public function test_la_contrainte_unique_source_collected_for_empeche_un_doublon(): void
    {
        AnalyticsCollectionRun::create([
            'source' => 'ga4',
            'collected_for' => '2026-09-29',
            'status' => 'success',
        ]);

        $this->expectException(QueryException::class);

        AnalyticsCollectionRun::create([
            'source' => 'ga4',
            'collected_for' => '2026-09-29',
            'status' => 'success',
        ]);
    }

    public function test_updateorcreate_est_idempotent_sur_source_et_collected_for(): void
    {
        AnalyticsCollectionRun::updateOrCreate(
            ['source' => 'gsc', 'collected_for' => '2026-09-29'],
            ['status' => 'partial', 'rows_upserted' => 3]
        );

        AnalyticsCollectionRun::updateOrCreate(
            ['source' => 'gsc', 'collected_for' => '2026-09-29'],
            ['status' => 'success', 'rows_upserted' => 12]
        );

        $this->assertSame(
            1,
            AnalyticsCollectionRun::where('source', 'gsc')->where('collected_for', '2026-09-29')->count()
        );

        $run = AnalyticsCollectionRun::where('source', 'gsc')->where('collected_for', '2026-09-29')->first();
        $this->assertSame('success', $run->status);
        $this->assertSame(12, $run->rows_upserted);
    }
}
