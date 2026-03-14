<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Newsletter\Models\WorkflowEnrollment;
use Modules\Newsletter\Services\WorkflowEngine;

class ProcessWorkflowStep implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public WorkflowEnrollment $enrollment
    ) {
        $this->onQueue('workflows');
    }

    public function handle(WorkflowEngine $engine): void
    {
        $engine->processStep($this->enrollment);
    }
}
