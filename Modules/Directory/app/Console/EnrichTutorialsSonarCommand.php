<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;

class EnrichTutorialsSonarCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:enrich-tutorials-sonar {--batch=5} {--slug=} {--force : Forcer même si kill switch actif}';

    protected $description = 'Enrichit les outils avec tutoriels FR YouTube via OpenRouter Sonar-pro + YouTube oEmbed (remplace besoin clé YouTube Data API v3)';

    public function handle(): int
    {
        if (! $this->option('force') && $this->shouldSkipForKillSwitch('cron.directory-tutorials-sonar')) {
            return Command::SUCCESS;
        }

        if (empty(env('OPENROUTER_API_KEY'))) {
            $this->error('OPENROUTER_API_KEY non configurée. Commande annulée.');

            return Command::FAILURE;
        }

        $batch = (int) $this->option('batch');
        $slug = $this->option('slug');

        if ($slug) {
            $tools = Tool::withCount(['resources' => fn ($q) => $q->where('language', 'fr')->where('type', 'youtube')])
                ->where(fn ($q) => $q->where('slug->fr_CA', $slug)->orWhere('slug->fr', $slug)->orWhere('slug->en', $slug))
                ->get();
        } else {
            $tools = Tool::withCount(['resources' => fn ($q) => $q->where('language', 'fr')->where('type', 'youtube')])
                ->where('status', 'published')
                ->having('resources_count', '<', 5)
                ->where(fn ($q) => $q->whereNull('tutorials_last_scanned_at')->orWhere('tutorials_last_scanned_at', '<', now()->subDays(14)))
                ->orderBy('resources_count', 'asc')
                ->orderByDesc('clicks_count')
                ->limit($batch)
                ->get();
        }

        if ($tools->isEmpty()) {
            $this->info('Aucun outil à enrichir.');

            return Command::SUCCESS;
        }

        $totalProcessed = 0;
        $totalAdded = 0;

        foreach ($tools as $tool) {
            $toolName = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;
            $existing = $tool->resources_count ?? 0;
            $needed = max(1, 5 - $existing);

            $this->info("--- {$toolName} (ID:{$tool->id}) — {$existing}/5 tutos ---");

            try {
                $urls = $this->searchTutorialsViaSonar($toolName, $needed);
                $this->line('  sonar-pro: '.count($urls).' URLs trouvées');

                foreach ($urls as $item) {
                    $videoId = $item['video_id'] ?? null;
                    if (! $videoId) {
                        continue;
                    }

                    try {
                        if (ToolResource::where('video_id', $videoId)->exists()) {
                            $this->warn("  doublon video_id={$videoId}");

                            continue;
                        }

                        $oembed = $this->fetchOEmbed($videoId);
                        if (! $oembed) {
                            $this->warn("  oEmbed échec pour {$videoId}");

                            continue;
                        }

                        $this->insertResource($tool, $videoId, $oembed);
                        $level = ToolResource::detectLevel($oembed['title']);
                        $this->info("  + video_id={$videoId} title='{$oembed['title']}' channel='{$oembed['author_name']}' level={$level}");
                        $totalAdded++;
                    } catch (\Throwable $e) {
                        $this->warn("  oEmbed erreur {$videoId}: {$e->getMessage()}");
                    }
                }
            } catch (\Throwable $e) {
                $this->warn("  Sonar erreur: {$e->getMessage()}");
                Log::warning("[EnrichTutorialsSonar] {$toolName}: {$e->getMessage()}");
            }

            $tool->update(['tutorials_last_scanned_at' => now()]);
            $totalProcessed++;
        }

        $this->newLine();
        $this->info("BILAN : {$totalProcessed} outils, {$totalAdded} tutoriels ajoutés");

        return Command::SUCCESS;
    }

    private function searchTutorialsViaSonar(string $toolName, int $needed): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('OPENROUTER_API_KEY'),
            'Content-Type' => 'application/json',
        ])
            ->timeout(60)
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => 'perplexity/sonar-pro',
                'temperature' => 0.15,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Trouve {$needed} tutoriels YouTube FR 2025-2026 récents pour \"{$toolName}\". Chaînes reconnues (pas spam), durées 5-60min, niveaux variés. IMPORTANT : retourne UNIQUEMENT un array JSON strict (zéro texte autour, zéro markdown fence). Format : [{\"url\": \"https://www.youtube.com/watch?v=XXX\", \"video_id\": \"XXX\", \"title\": \"...\", \"channel_name\": \"...\"}]. URLs doivent être réelles et vérifiées.",
                    ],
                ],
            ]);

        if (! $response->successful()) {
            return [];
        }

        $content = (string) ($response->json('choices.0.message.content') ?? '');
        $content = preg_replace('/```(?:json)?\s*/', '', $content);
        $content = preg_replace('/\s*```/', '', $content);
        $data = json_decode(trim($content), true);

        return is_array($data) ? $data : [];
    }

    private function fetchOEmbed(string $videoId): ?array
    {
        $oembedUrl = 'https://www.youtube.com/oembed?url='.urlencode("https://www.youtube.com/watch?v={$videoId}").'&format=json';
        $response = Http::withoutVerifying()->timeout(15)->get($oembedUrl);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();
        if (! $data || empty($data['title'])) {
            return null;
        }

        return [
            'title' => $data['title'],
            'author_name' => $data['author_name'] ?? '',
            'author_url' => $data['author_url'] ?? '',
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
        ];
    }

    private function insertResource(Tool $tool, string $videoId, array $oembed): void
    {
        ToolResource::create([
            'directory_tool_id' => $tool->id,
            'user_id' => null,
            'url' => "https://www.youtube.com/watch?v={$videoId}",
            'title' => $oembed['title'],
            'type' => 'youtube',
            'language' => 'fr',
            'level' => ToolResource::detectLevel($oembed['title']),
            'thumbnail' => $oembed['thumbnail_url'],
            'video_id' => $videoId,
            'duration_seconds' => null,
            'channel_name' => $oembed['author_name'],
            'channel_url' => $oembed['author_url'],
            'is_approved' => true,
        ]);
    }
}
