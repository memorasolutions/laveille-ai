<?php

declare(strict_types=1);

namespace Modules\Directory\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EnrichToolJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 180;

    public int $tries = 2;

    public int $backoff = 60;

    private const ALLOWED_COMMANDS = [
        'tools:enrich-pending',
        'tools:enrich-metadata',
    ];

    public function __construct(
        public int $toolId,
        public string $artisanCommand = 'tools:enrich-pending',
    ) {}

    public function handle(): void
    {
        if (! in_array($this->artisanCommand, self::ALLOWED_COMMANDS, true)) {
            Log::error('[EnrichToolJob] Command not in allowlist.', [
                'command' => $this->artisanCommand,
                'tool_id' => $this->toolId,
            ]);

            return;
        }

        $exitCode = Artisan::call($this->artisanCommand, [
            '--id' => $this->toolId,
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            Log::warning('[EnrichToolJob] Non-zero exit code.', [
                'command' => $this->artisanCommand,
                'tool_id' => $this->toolId,
                'exit_code' => $exitCode,
                'output' => Artisan::output(),
            ]);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[EnrichToolJob] Job failed.', [
            'command' => $this->artisanCommand,
            'tool_id' => $this->toolId,
            'exception' => $e->getMessage(),
        ]);
    }
}
