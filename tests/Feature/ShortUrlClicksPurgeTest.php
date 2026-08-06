<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createShortUrlForClicksPurgeTest(string $slug): int
{
    return (int) DB::table('short_urls')->insertGetId([
        'slug' => $slug,
        'original_url' => 'https://laveille.ai/'.$slug,
        'is_active' => true,
        'redirect_type' => 302,
        'clicks_count' => 0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

test('app:cleanup dry-run counts short url clicks older than 12 months without deleting', function () {
    $shortUrlId = createShortUrlForClicksPurgeTest('purge-dry-run');

    DB::table('short_url_clicks')->insert([
        ['short_url_id' => $shortUrlId, 'clicked_at' => now()->subDays(400)],
        ['short_url_id' => $shortUrlId, 'clicked_at' => now()->subDays(10)],
    ]);

    Artisan::call('app:cleanup', ['--dry-run' => true]);

    // Dry-run : aucune suppression, les deux clics restent en base.
    expect(DB::table('short_url_clicks')->count())->toBe(2);
});

test('app:cleanup purges short url clicks older than 12 months (365 days)', function () {
    $shortUrlId = createShortUrlForClicksPurgeTest('purge-real');

    DB::table('short_url_clicks')->insert([
        ['short_url_id' => $shortUrlId, 'clicked_at' => now()->subDays(400)],
        ['short_url_id' => $shortUrlId, 'clicked_at' => now()->subDays(10)],
    ]);

    Artisan::call('app:cleanup');

    expect(DB::table('short_url_clicks')->count())->toBe(1);
    expect(DB::table('short_url_clicks')->first()->short_url_id)->toBe($shortUrlId);

    // Le lien lui-meme n'est JAMAIS supprime par cette purge (seules les stats de clics le sont).
    expect(DB::table('short_urls')->where('id', $shortUrlId)->exists())->toBeTrue();
});
