<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\Directory\Jobs\EnrichToolJob;
use Modules\Directory\Models\Tool;

class DispatchEnrichmentCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:dispatch-enrichment {--limit=5} {--type=pending : pending|metadata}';

    protected $description = 'Dispatch N EnrichToolJob dans la queue default (type=pending ou metadata)';

    public function handle(): int
    {
        if ($this->shouldSkipForKillSwitch('cron.ai-enrich-dispatch')) {
            return self::SUCCESS;
        }

        $type = $this->option('type');
        $limit = max(1, (int) $this->option('limit'));

        $tools = match ($type) {
            'metadata' => $this->metadataQuery($limit)->get(),
            default => $this->pendingQuery($limit)->get(),
        };

        $command = match ($type) {
            'metadata' => 'tools:enrich-metadata',
            default => 'tools:enrich-pending',
        };

        $tools->each(fn (Tool $tool) => EnrichToolJob::dispatch($tool->id, $command));

        $count = $tools->count();
        $this->info("Dispatched {$count} job(s) (type: {$type}).");

        return self::SUCCESS;
    }

    private function pendingQuery(int $limit): Builder
    {
        return Tool::query()
            ->whereIn('status', ['published', 'pending'])
            ->whereRaw("CHAR_LENGTH(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(description, '$.\"fr_CA\"')), '')) < 500")
            ->orderByDesc('clicks_count')
            ->orderByDesc('is_featured')
            ->limit($limit);
    }

    private function metadataQuery(int $limit): Builder
    {
        return Tool::query()
            ->whereIn('status', ['published', 'pending'])
            ->where(fn (Builder $q) => $q->whereNull('launch_year')->orWhereNull('target_audience'))
            ->orderByDesc('clicks_count')
            ->orderByDesc('is_featured')
            ->limit($limit);
    }
}
