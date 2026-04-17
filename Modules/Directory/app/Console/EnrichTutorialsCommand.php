<?php

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Services\YouTubeService;

class EnrichTutorialsCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:enrich-tutorials {--batch=5} {--force : Forcer même si kill switch actif}';

    protected $description = 'Enrichit les outils publiés avec des tutoriels YouTube FR manquants';

    public function handle(): int
    {
        if ($this->shouldSkipForKillSwitch('cron.directory-tutorials')) {
            return self::SUCCESS;
        }

        if (! class_exists(Tool::class) || ! class_exists(ToolResource::class)) {
            $this->error('Le module Directory est désactivé ou introuvable.');

            return self::FAILURE;
        }

        $apiKey = config('directory.youtube_api_key');
        if (empty($apiKey)) {
            $this->error('YOUTUBE_API_KEY non configurée. Commande annulée.');

            return self::FAILURE;
        }

        $youtubeService = new YouTubeService;
        $batch = max(1, (int) $this->option('batch'));

        $this->info("Recherche des outils avec < 5 tutoriels FR (batch : {$batch})...");

        $tools = Tool::withCount([
            'resources' => fn ($q) => $q->where('language', 'fr')->where('type', 'youtube'),
        ])
            ->where('status', 'published')
            ->having('resources_count', '<', 5)
            ->where(fn ($q) => $q->whereNull('tutorials_last_scanned_at')
                ->orWhere('tutorials_last_scanned_at', '<', now()->subDays(14)))
            ->orderBy('resources_count', 'asc')
            ->orderByDesc('clicks_count')
            ->orderByDesc('is_featured')
            ->limit($batch)
            ->get();

        if ($tools->isEmpty()) {
            $this->info('Tous les outils ont 5+ tutoriels FR. Rien à faire.');

            return self::SUCCESS;
        }

        $this->info("{$tools->count()} outil(s) à enrichir.");

        $totalAdded = 0;
        $totalSkipped = 0;
        $totalProcessed = 0;

        foreach ($tools as $tool) {
            $existing = (int) $tool->resources_count;
            $needed = 5 - $existing;
            $toolName = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;

            $this->info("--- {$toolName} (ID:{$tool->id}) — {$existing}/5 tutoriels ---");

            try {
                $tutorials = $youtubeService->findTutorials($toolName, $needed, $tool->url);
            } catch (\Throwable $e) {
                $this->warn("  Échec YouTube : {$e->getMessage()}");
                Log::warning("[EnrichTutorials] Échec pour {$toolName}", ['error' => $e->getMessage()]);
                $totalProcessed++;

                continue;
            }

            if (empty($tutorials)) {
                $this->line("  Aucun tutoriel trouvé.");
                $totalProcessed++;

                continue;
            }

            foreach ($tutorials as $tuto) {
                $videoId = $tuto['video_id'] ?? null;
                if (! $videoId) {
                    continue;
                }

                if (ToolResource::where('video_id', $videoId)->exists()) {
                    $this->line("  Déjà existant : {$videoId}");
                    $totalSkipped++;

                    continue;
                }

                try {
                    ToolResource::create([
                        'directory_tool_id' => $tool->id,
                        'user_id' => null,
                        'url' => $tuto['url'] ?? "https://youtube.com/watch?v={$videoId}",
                        'title' => $tuto['title'] ?? '',
                        'type' => 'youtube',
                        'language' => $tuto['language'] ?? 'fr',
                        'level' => ToolResource::detectLevel($tuto['title'] ?? ''),
                        'thumbnail' => $tuto['thumbnail'] ?? null,
                        'video_id' => $videoId,
                        'duration_seconds' => $tuto['duration_seconds'] ?? null,
                        'channel_name' => $tuto['channel_name'] ?? null,
                        'channel_url' => $tuto['channel_url'] ?? null,
                        'is_approved' => true,
                    ]);

                    $this->info("  + {$tuto['title']} ({$videoId})");
                    $totalAdded++;
                } catch (\Throwable $e) {
                    $this->warn("  Erreur insertion {$videoId} : {$e->getMessage()}");
                }
            }

            $totalProcessed++;
            $tool->update(['tutorials_last_scanned_at' => now()]);
        }

        $this->newLine();
        $this->info("=== BILAN : {$totalProcessed} outils, {$totalAdded} tutoriels ajoutés, {$totalSkipped} doublons ===");

        return self::SUCCESS;
    }
}
