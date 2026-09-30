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
use Modules\Analytics\Models\GscDaily;
use Tests\TestCase;

class GscDailyTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_migration_cree_la_table_analytics_gsc_daily(): void
    {
        $this->assertTrue(Schema::hasTable('analytics_gsc_daily'));
    }

    public function test_une_ligne_peut_etre_inseree_et_relue_avec_le_bon_cast_de_date(): void
    {
        $ligne = GscDaily::create([
            'date' => '2026-09-29',
            'url' => '/glossaire/rag',
            'clicks' => 12,
            'impressions' => 340,
            'ctr' => 3.5294,
            'position' => 8.75,
            'content_id' => 100,
            'content_type' => 'dictionary_term',
        ]);

        $relue = GscDaily::find($ligne->id);

        $this->assertInstanceOf(Carbon::class, $relue->date);
        $this->assertSame('2026-09-29', $relue->date->format('Y-m-d'));
        $this->assertSame('/glossaire/rag', $relue->url);
        $this->assertSame(12, $relue->clicks);
        $this->assertSame('3.5294', (string) $relue->ctr);
        $this->assertSame('8.75', (string) $relue->position);
    }

    public function test_la_contrainte_unique_date_url_empeche_un_doublon(): void
    {
        GscDaily::create(['date' => '2026-09-29', 'url' => '/glossaire/rag']);

        $this->expectException(QueryException::class);

        GscDaily::create(['date' => '2026-09-29', 'url' => '/glossaire/rag']);
    }

    public function test_updateorcreate_met_a_jour_au_lieu_de_dupliquer(): void
    {
        GscDaily::updateOrCreate(
            ['date' => '2026-09-29', 'url' => '/glossaire/rag'],
            ['clicks' => 5]
        );

        GscDaily::updateOrCreate(
            ['date' => '2026-09-29', 'url' => '/glossaire/rag'],
            ['clicks' => 17]
        );

        $this->assertSame(
            1,
            GscDaily::where('date', '2026-09-29')->where('url', '/glossaire/rag')->count()
        );
        $this->assertSame(
            17,
            GscDaily::where('date', '2026-09-29')->where('url', '/glossaire/rag')->first()->clicks
        );
    }
}
