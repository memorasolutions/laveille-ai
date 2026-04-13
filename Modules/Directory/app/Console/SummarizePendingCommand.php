<?php

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Services\OpenRouterService;
use Modules\Directory\Services\YouTubeCaptionsService;

class SummarizePendingCommand extends Command
{
    protected $signature = 'resources:summarize-pending {--batch=10}';

    protected $description = 'Résume les tutoriels YouTube en attente via IA';

    public function handle(): int
    {
        if (! class_exists(ToolResource::class)) {
            $this->error('Le module Directory est désactivé ou introuvable.');

            return self::FAILURE;
        }

        $batch = max(1, (int) $this->option('batch'));

        $resources = ToolResource::where('type', 'youtube')
            ->whereNull('video_summary')
            ->whereNotNull('video_id')
            ->limit($batch)
            ->get();

        $total = $resources->count();

        if ($total === 0) {
            $this->info('Aucun tutoriel YouTube en attente de résumé.');

            return self::SUCCESS;
        }

        $this->info("Traitement de {$total} tutoriel(s) YouTube en attente...");

        $captionsService = new YouTubeCaptionsService;
        $openRouterService = new OpenRouterService;

        $summarized = 0;

        foreach ($resources as $index => $resource) {
            $transcript = $captionsService->getTranscript($resource->video_id);

            if ($transcript === null) {
                $this->warn("Pas de sous-titres : {$resource->video_id}");

                continue;
            }

            if (strlen($transcript) < 100) {
                $this->warn("Transcript trop court : {$resource->video_id}");

                continue;
            }

            $summary = $openRouterService->summarize($transcript, 200);

            if (empty($summary)) {
                $this->warn("Résumé vide : {$resource->video_id}");

                continue;
            }

            $resource->update(['video_summary' => $summary]);

            $this->info("Résumé : {$resource->title}");
            $summarized++;

            if ($index < $total - 1) {
                sleep(2);
            }
        }

        $this->newLine();
        $this->info("=== BILAN : {$summarized} résumé(s) ajouté(s) sur {$total} traité(s) ===");

        return self::SUCCESS;
    }
}
