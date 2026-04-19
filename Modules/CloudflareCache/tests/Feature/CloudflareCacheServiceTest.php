<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\CloudflareCache\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\CloudflareCache\Services\CloudflareCacheService;
use Tests\TestCase;

class CloudflareCacheServiceTest extends TestCase
{
    public function test_returns_false_when_not_configured(): void
    {
        config(['cloudflarecache.api_token' => null, 'cloudflarecache.zone_id' => null]);
        $service = app(CloudflareCacheService::class);
        $this->assertFalse($service->isConfigured());
        $this->assertFalse($service->purgeByUrls(['https://example.com']));
    }

    public function test_purges_urls_successfully(): void
    {
        config(['cloudflarecache.api_token' => 'fake-token', 'cloudflarecache.zone_id' => 'fake-zone']);
        Http::fake(['api.cloudflare.com/*' => Http::response(['success' => true, 'errors' => [], 'messages' => []], 200)]);

        $service = app(CloudflareCacheService::class);
        $this->assertTrue($service->purgeByUrls(['https://example.com/a', 'https://example.com/b']));

        Http::assertSent(fn ($request) => str_contains($request->url(), 'api.cloudflare.com/client/v4/zones/fake-zone/purge_cache')
            && $request['files'] === ['https://example.com/a', 'https://example.com/b']);
    }

    public function test_empty_urls_returns_true_without_api_call(): void
    {
        config(['cloudflarecache.api_token' => 'x', 'cloudflarecache.zone_id' => 'y']);
        Http::fake();

        $service = app(CloudflareCacheService::class);
        $this->assertTrue($service->purgeByUrls([]));
        Http::assertNothingSent();
    }

    public function test_chunks_urls_over_30(): void
    {
        config(['cloudflarecache.api_token' => 'x', 'cloudflarecache.zone_id' => 'y']);
        Http::fake(['*' => Http::response(['success' => true], 200)]);

        $urls = array_map(fn ($i) => "https://ex.com/{$i}", range(1, 45));
        app(CloudflareCacheService::class)->purgeByUrls($urls);

        Http::assertSentCount(2);
    }
}
