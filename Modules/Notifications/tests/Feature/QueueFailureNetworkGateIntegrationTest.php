<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project laveille.ai
 *
 * Ticket #2246 (2026-09-10) - preuve d'INTÉGRATION : le vrai listener
 * Queue::failing() branché dans AppServiceProvider::configureQueueFailureHandling() consulte
 * bien NetworkJobFailureWindow AVANT d'appeler AutomationAlertService::fire(), pour un job
 * réseau réel (CaptureScreenshotJob). Les tests unitaires de NetworkJobFailureWindowTest.php
 * prouvent déjà la logique de fenêtre elle-même ; celui-ci prouve le BRANCHEMENT, jamais
 * reconstruit à côté du mécanisme existant.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Modules\Directory\Jobs\CaptureScreenshotJob;

uses(Tests\TestCase::class, RefreshDatabase::class);

/**
 * Fausse implémentation minimale du contrat Illuminate\Contracts\Queue\Job : seule
 * resolveName() est réellement exercée par le listener testé, le reste n'a besoin que
 * d'exister pour satisfaire l'interface.
 */
function nfwFakeQueueJob(string $resolvedName): \Illuminate\Contracts\Queue\Job
{
    return new class($resolvedName) implements \Illuminate\Contracts\Queue\Job
    {
        public function __construct(private string $resolvedName) {}

        public function uuid() { return 'test-uuid'; }

        public function getJobId() { return 'test-id'; }

        public function payload() { return []; }

        public function fire() {}

        public function release($delay = 0) {}

        public function isReleased() { return false; }

        public function delete() {}

        public function isDeleted() { return false; }

        public function isDeletedOrReleased() { return false; }

        public function attempts() { return 1; }

        public function hasFailed() { return true; }

        public function markAsFailed() {}

        public function fail($e = null) {}

        public function maxTries() { return 1; }

        public function maxExceptions() { return null; }

        public function timeout() { return null; }

        public function retryUntil() { return null; }

        public function getName() { return $this->resolvedName; }

        public function resolveName() { return $this->resolvedName; }

        public function resolveQueuedJobClass() { return $this->resolvedName; }

        public function getConnectionName() { return 'database'; }

        public function getQueue() { return 'default'; }

        public function getRawBody() { return '{}'; }
    };
}

function nfwFireJobFailed(string $jobClass): void
{
    event(new JobFailed('database', nfwFakeQueueJob($jobClass), new \RuntimeException('Site distant indisponible')));
}

beforeEach(function () {
    Cache::forget('network_job_failure_window:'.hash('sha256', CaptureScreenshotJob::class));
    Cache::forget('automation_alert:'.md5('queue:'.CaptureScreenshotJob::class));
});

it('n\'envoie AUCUN courriel pour les deux premiers échecs réels du job réseau CaptureScreenshotJob', function () {
    Mail::shouldReceive('raw')->never();

    nfwFireJobFailed(CaptureScreenshotJob::class);
    nfwFireJobFailed(CaptureScreenshotJob::class);
});

it('envoie le courriel d\'alerte au TROISIÈME échec réel, dans la fenêtre, du même job réseau', function () {
    Mail::shouldReceive('raw')->once();

    nfwFireJobFailed(CaptureScreenshotJob::class);
    nfwFireJobFailed(CaptureScreenshotJob::class);
    nfwFireJobFailed(CaptureScreenshotJob::class);
});
