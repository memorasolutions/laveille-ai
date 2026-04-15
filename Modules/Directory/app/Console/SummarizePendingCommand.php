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
            $summary = null;

            // Stratégie 1 : sous-titres YouTube (meilleure qualité)
            $transcript = $captionsService->getTranscript($resource->video_id);

            if ($transcript && strlen($transcript) >= 100) {
                $summary = $openRouterService->summarize($transcript, 200);
                if ($summary) {
                    $this->info("  Résumé (transcript) : {$resource->title}");
                }
            }

            // Stratégie 2 : métadonnées (fallback 100% fiable)
            if (empty($summary)) {
                $toolName = $resource->tool?->getTranslation('name', 'fr_CA', false) ?? '';
                $duration = $resource->duration_seconds ? floor($resource->duration_seconds / 60) . ' minutes' : '';
                $context = "Outil : {$toolName}\nTitre : {$resource->title}\nChaîne : {$resource->channel_name}\nDurée : {$duration}\nLangue : {$resource->language}";

                $summary = $openRouterService->generate(
                    "Résume cette vidéo YouTube en 2-3 phrases en français, basé sur ses métadonnées :\n{$context}",
                    'Tu résumes des tutoriels vidéo pour laveille.ai. Style concis, informatif, en français québécois professionnel.'
                );

                if ($summary) {
                    $this->info("  Résumé (métadonnées) : {$resource->title}");
                }
            }

            if (empty($summary)) {
                $this->warn("  Échec résumé : {$resource->video_id}");

                continue;
            }

            $resource->update(['video_summary' => $summary]);
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
