<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\CloudflareCache\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\CloudflareCache\Services\CloudflareCacheService;

class PurgeCloudflareCacheJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 2;

    /** @var array<int,int> */
    public array $backoff = [30, 120];

    public string $queue = 'cloudflare';

    public function __construct(public array $urls) {}

    public function handle(): void
    {
        Log::info('PurgeCloudflareCacheJob: starting', ['urls_count' => count($this->urls)]);

        $service = app(CloudflareCacheService::class);
        $service->purgeByUrls($this->urls);

        Log::info('PurgeCloudflareCacheJob: completed', ['urls_count' => count($this->urls)]);
    }

    public function failed(?\Throwable $exception = null): void
    {
        Log::error('PurgeCloudflareCacheJob: failed', [
            'urls_count' => count($this->urls),
            'exception' => $exception?->getMessage(),
        ]);
    }
}
