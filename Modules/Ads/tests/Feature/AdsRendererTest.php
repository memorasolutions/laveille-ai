<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Tests Pest - rendu des emplacements publicitaires (AdSense, pub directe, alternance),
 * audience membre / anonyme, validation du controller et purge du cache.
 */

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Modules\Ads\Http\Controllers\AdPlacementController;
use Modules\Ads\Models\AdPlacement;
use Modules\Ads\Services\AdsRenderer;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    if (! \Nwidart\Modules\Facades\Module::find('Ads')?->isEnabled()) {
        test()->markTestSkipped('Module Ads désactivé - tests skipped.');
    }

    config(['services.adsense.client_id' => 'ca-pub-0000000000000000']);
    Cache::flush();
});

function adsFake(array $attributes = []): AdPlacement
{
    return AdPlacement::create(array_merge([
        'key' => 'test-slot',
        'name' => 'Emplacement de test',
        'ad_code' => null,
        'is_active' => true,
        'is_external' => false,
        'sort_order' => 0,
    ], $attributes));
}

it('rend une balise AdSense pour un anonyme', function (): void {
    adsFake(['ad_slot' => '1234567890', 'is_external' => true, 'min_height' => 250, 'ad_format' => 'rectangle']);

    $html = app(AdsRenderer::class)->render('test-slot');

    expect($html)->toContain('<ins class="adsbygoogle lv-adsense"')
        ->toContain('data-ad-slot="1234567890"')
        ->toContain('data-ad-client="ca-pub-0000000000000000"')
        ->toContain('data-ad-format="rectangle"')
        ->toContain('min-height:250px')
        ->toContain('data-lv-lazy="1"')
        ->not->toContain('Publicité')
        ->not->toContain('<script>');
});

it('rend une unité In-Article (fluid) avec data-ad-layout et sans full-width-responsive', function (): void {
    adsFake(['ad_slot' => '6448028197', 'is_external' => false, 'ad_format' => 'fluid']);

    $html = app(AdsRenderer::class)->render('test-slot');

    expect($html)->toContain('data-ad-layout="in-article"')
        ->toContain('data-ad-format="fluid"')
        ->toContain('text-align:center')
        ->not->toContain('data-full-width-responsive')
        // Une unité fluid se dimensionne seule : aucune hauteur imposée, sinon un espace vide
        // reste sous une annonce plus courte que la réserve (défaut « autant d'espace » 2026-10-01).
        ->not->toContain('min-height');
});

it('pousse immédiatement l\'unité AdSense quand le chargement différé est désactivé', function (): void {
    adsFake(['ad_slot' => '1234567890', 'lazy' => false]);

    $html = app(AdsRenderer::class)->render('test-slot');

    expect($html)->toContain('(adsbygoogle=window.adsbygoogle||[]).push({});')
        ->not->toContain('data-lv-lazy')
        ->toContain('min-height:280px');
});

it('rend AdSense à un membre connecté par défaut (members_see_ads vrai) quand l\'emplacement est AdSense seul', function (): void {
    adsFake(['ad_slot' => '1234567890']);

    $this->actingAs(User::factory()->create());

    expect(config('services.adsense.members_see_ads'))->toBeTrue()
        ->and(app(AdsRenderer::class)->render('test-slot'))->toContain('adsbygoogle');
});

it('rend AdSense à un anonyme que members_see_ads soit vrai ou faux', function (): void {
    adsFake(['ad_slot' => '1234567890']);

    foreach ([true, false] as $flag) {
        config(['services.adsense.members_see_ads' => $flag]);
        Cache::flush();

        expect(app(AdsRenderer::class)->render('test-slot'))->toContain('adsbygoogle');
    }
});

it('ne rend rien pour un membre connecté quand members_see_ads est faux et l\'emplacement AdSense seul', function (): void {
    config(['services.adsense.members_see_ads' => false]);
    adsFake(['ad_slot' => '1234567890']);

    $this->actingAs(User::factory()->create());

    expect(app(AdsRenderer::class)->render('test-slot'))->toBeNull();
});

it('rend la pub directe avec le label Publicité pour un anonyme ET pour un membre', function (): void {
    adsFake(['ad_code' => '<p>Promo maison</p>', 'is_external' => false]);

    $anon = app(AdsRenderer::class)->render('test-slot');
    expect($anon)->toContain('Promo maison')->toContain('<span class="ad-label">Publicité</span>');

    $this->actingAs(User::factory()->create());

    $member = app(AdsRenderer::class)->render('test-slot');
    expect($member)->toContain('Promo maison')->toContain('<span class="ad-label">Publicité</span>');
});

it('donne toujours la pub directe à un membre quand les deux sont remplis et members_see_ads est faux', function (): void {
    config(['services.adsense.members_see_ads' => false]);
    adsFake(['ad_code' => '<p>Promo maison</p>', 'ad_slot' => '1234567890']);
    $this->actingAs(User::factory()->create());

    foreach ([2, 3] as $offset) {
        // Deux jours consécutifs : un pair, un impair. Le cache est vidé pour forcer la décision.
        $this->travelTo(now('America/Toronto')->startOfYear()->addDays($offset)->setHour(12));
        Cache::flush();

        $html = app(AdsRenderer::class)->render('test-slot');
        expect($html)->toContain('Promo maison')->not->toContain('adsbygoogle');
    }

    $this->travelBack();
});

it('alterne AdSense et pub directe d\'un jour à l\'autre pour un anonyme', function (): void {
    adsFake(['ad_code' => '<p>Promo maison</p>', 'ad_slot' => '1234567890']);

    $seen = [];
    foreach ([1, 2] as $offset) {
        $this->travelTo(now('America/Toronto')->startOfYear()->addDays($offset)->setHour(12));
        Cache::flush();

        $html = app(AdsRenderer::class)->render('test-slot');
        $seen[] = str_contains((string) $html, 'adsbygoogle') ? 'adsense' : 'direct';
    }

    $this->travelBack();

    expect($seen)->toContain('adsense')->toContain('direct');
});

it('alterne AdSense et pub directe pour un membre quand members_see_ads est vrai', function (): void {
    adsFake(['ad_code' => '<p>Promo maison</p>', 'ad_slot' => '1234567890']);
    $this->actingAs(User::factory()->create());

    $seen = [];
    foreach ([1, 2] as $offset) {
        $this->travelTo(now('America/Toronto')->startOfYear()->addDays($offset)->setHour(12));
        Cache::flush();

        $html = app(AdsRenderer::class)->render('test-slot');
        $seen[] = str_contains((string) $html, 'adsbygoogle') ? 'adsense' : 'direct';
    }

    $this->travelBack();

    expect($seen)->toContain('adsense')->toContain('direct');
});

it('ne sert pas à un membre une version AdSense mise en cache pour un anonyme (members_see_ads faux)', function (): void {
    config(['services.adsense.members_see_ads' => false]);
    adsFake(['ad_slot' => '1234567890']);

    expect(app(AdsRenderer::class)->render('test-slot'))->toContain('adsbygoogle');

    $this->actingAs(User::factory()->create());

    expect(app(AdsRenderer::class)->render('test-slot'))->toBeNull();
});

it('crée un emplacement externe via le controller (régression is_external)', function (): void {
    $request = Request::create('/admin/ads', 'POST', [
        'key' => 'external-one',
        'name' => 'AdSense externe',
        'ad_code' => '<script>x</script>',
        'is_external' => '1',
        'is_active' => '1',
        'sort_order' => 0,
    ]);

    app(AdPlacementController::class)->store($request);

    $ad = AdPlacement::where('key', 'external-one')->firstOrFail();
    expect($ad->is_external)->toBeTrue()->and($ad->is_active)->toBeTrue()->and($ad->lazy)->toBeFalse();
});

it('accepte un emplacement AdSense sans ad_code et exige l\'un des deux', function (): void {
    $store = fn (array $data) => app(AdPlacementController::class)->store(
        Request::create('/admin/ads', 'POST', array_merge(['key' => 'k1', 'name' => 'N', 'sort_order' => 0], $data))
    );

    $store(['ad_slot' => '555', 'ad_format' => 'fluid', 'min_height' => 300, 'lazy' => '1']);
    $ad = AdPlacement::where('key', 'k1')->firstOrFail();
    expect($ad->isAdsense())->toBeTrue()->and($ad->hasDirect())->toBeFalse()
        ->and($ad->min_height)->toBe(300)->and($ad->lazy)->toBeTrue();

    expect(fn () => app(AdPlacementController::class)->store(
        Request::create('/admin/ads', 'POST', ['key' => 'k2', 'name' => 'N', 'sort_order' => 0])
    ))->toThrow(ValidationException::class);

    expect(fn () => app(AdPlacementController::class)->store(
        Request::create('/admin/ads', 'POST', ['key' => 'k3', 'name' => 'N', 'ad_slot' => '1', 'ad_format' => 'bogus', 'sort_order' => 0])
    ))->toThrow(ValidationException::class);
});

it('clearCache vide les deux clés du jour (rendu direct et ligne résolue)', function (): void {
    adsFake(['ad_code' => '<p>Promo maison</p>']);
    $renderer = app(AdsRenderer::class);
    $renderer->render('test-slot');

    $day = now()->timezone('America/Toronto')->format('Y-z');
    expect(Cache::has("ad_placement:test-slot:{$day}"))->toBeTrue()
        ->and(Cache::has("ad_placement_row:test-slot:{$day}"))->toBeTrue();

    $renderer->clearCache('test-slot');

    expect(Cache::has("ad_placement:test-slot:{$day}"))->toBeFalse()
        ->and(Cache::has("ad_placement_row:test-slot:{$day}"))->toBeFalse();
});

it('la migration news-infeed crée un emplacement AdSense actif et se réverse', function (): void {
    $migration = require base_path('Modules/Ads/database/migrations/2026_10_10_000001_seed_news_infeed_placement.php');

    $migration->up();
    $ad = AdPlacement::where('key', 'news-infeed')->first();
    expect($ad)->not->toBeNull()
        ->and($ad->is_active)->toBeTrue()
        ->and($ad->ad_slot)->not->toBeEmpty();

    $migration->down();
    expect(AdPlacement::where('key', 'news-infeed')->exists())->toBeFalse();
});

it('rend une unité manuelle (data-ad-slot) pour news-infeed, rien si inactive', function (): void {
    // La ligne est semée par la migration.
    expect(app(AdsRenderer::class)->render('news-infeed'))->toContain('data-ad-slot="4523648998"');

    AdPlacement::where('key', 'news-infeed')->update(['is_active' => false]);
    Cache::flush();
    expect(app(AdsRenderer::class)->render('news-infeed'))->toBeNull();
});

it('la config in-feed expose une première position 3 et un intervalle 6 par défaut', function (): void {
    expect(config('ads.infeed.first_position'))->toBe(3)
        ->and(config('ads.infeed.interval'))->toBe(6);
});

it('le chargeur effondre les cellules d\'annonce non servies', function (): void {
    $html = view('ads::partials.lazy-loader')->render();

    expect($html)->toContain('data-lv-ad-cell')
        ->toContain('lv-ad-off')
        ->toContain('data-ad-status');
});
