<?php

declare(strict_types=1);

namespace Modules\Authors\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Authors\Services\ModerationPipelineService;
use Modules\Blog\Models\Article;
use Throwable;

class ScanArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public Article $article) {}

    public function handle(ModerationPipelineService $service): void
    {
        $service->scan($this->article);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('ScanArticleJob failed for article ID '.$this->article->id.': '.$exception->getMessage());
    }
}
