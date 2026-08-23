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
use Modules\Directory\Services\OpenRouterService;

class EnrichToolJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Nombre de cascades OpenRouter qu'un enrichissement peut enchaîner pour UN outil.
     * `tools:enrich-pending` en fait deux : une recherche, puis une rédaction.
     */
    public const CASCADES_PAR_OUTIL = 2;

    /** Marge pour tout ce qui n'est pas un appel réseau : requêtes en base, écritures, journal. */
    public const MARGE_SECONDES = 30;

    public int $timeout;

    public int $tries = 2;

    public int $backoff = 60;

    private const ALLOWED_COMMANDS = [
        'tools:enrich-pending',
        'tools:enrich-metadata',
    ];

    public function __construct(
        public int $toolId,
        public string $artisanCommand = 'tools:enrich-pending',
    ) {
        $this->timeout = self::timeoutFromBudget();
    }

    /**
     * Délai du job DÉRIVÉ du budget de la cascade, jamais écrit en dur.
     *
     * 2026-08-23 : `$timeout` valait 180 s alors que le pire cas réel de l'enrichissement était
     * d'environ 1 080 s (3 modèles × 3 tentatives × 60 s, deux fois par outil). Le job se faisait
     * tuer par son propre délai, deux fois, puis marquer « attempted too many times » - une alerte
     * dont la trace ne montrait que la mécanique de la file, jamais la cause. Deux nombres logés
     * dans deux fichiers différents finissent toujours par diverger : celui-ci se CALCULE, et
     * EnrichToolJobTimeoutTest échoue si la relation se brise.
     */
    public static function timeoutFromBudget(): int
    {
        return OpenRouterService::budgetSecondes() * self::CASCADES_PAR_OUTIL + self::MARGE_SECONDES;
    }

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
