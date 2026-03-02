<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Console;

use Illuminate\Console\Command;
use Modules\Newsletter\Services\WorkflowEngine;

class ProcessWorkflowsCommand extends Command
{
    protected $signature = 'newsletter:process-workflows';

    protected $description = 'Process due workflow enrollments (run every 5 minutes)';

    public function handle(WorkflowEngine $engine): int
    {
        $processed = $engine->processDueEnrollments();

        $this->components->info("Processed {$processed} enrollment(s).");

        return self::SUCCESS;
    }
}
