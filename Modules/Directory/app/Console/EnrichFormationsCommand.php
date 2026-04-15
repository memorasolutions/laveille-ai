<?php

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Models\ToolResource;
use Modules\Directory\Services\OpenRouterService;

class EnrichFormationsCommand extends Command
{
    protected $signature = 'tools:enrich-formations {--batch=5}';

    protected $description = 'Enrichit les outils avec des formations gratuites via sonar-pro';

    public function handle(): int
    {
        if (! class_exists(Tool::class)) {
            $this->error('Module Directory introuvable.');

            return self::FAILURE;
        }

        $apiKey = config('directory.openrouter_api_key') ?: env('OPENROUTER_API_KEY');
        if (empty($apiKey)) {
            $this->error('OPENROUTER_API_KEY non configurée.');

            return self::FAILURE;
        }

        $openRouter = new OpenRouterService;
        $batch = max(1, (int) $this->option('batch'));

        $tools = Tool::where('status', 'published')
            ->withCount(['resources as formations_count' => fn ($q) => $q->where('type', 'formation')])
            ->having('formations_count', '<', 3)
            ->orderByDesc('clicks_count')
            ->limit($batch)
            ->get();

        if ($tools->isEmpty()) {
            $this->info('Tous les outils ont 3+ formations. Rien à faire.');

            return self::SUCCESS;
        }

        $this->info("{$tools->count()} outil(s) à enrichir.");

        $totalFormations = 0;
        $totalErrors = 0;

        foreach ($tools as $tool) {
            $toolName = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;
            $toolUrl = $tool->url ?? '';

            $this->info("--- {$toolName} (ID:{$tool->id}) ---");

            try {
                $response = $openRouter->search(
                    "Trouve 3 formations ou cours en ligne 100% GRATUITS pour apprendre {$toolName} ({$toolUrl}). "
                    ."Sources prioritaires : site officiel de l'outil, Google Skillshop, Microsoft Learn, Canva Design School, "
                    .'Notion Academy, OpenClassrooms, HubSpot Academy, freeCodeCamp, DeepLearning.AI, openformation.fr. '
                    ."Pour chaque formation retourne exactement : TITLE | URL | LANGUAGE (fr ou en). "
                    .'Une formation par ligne. Pas de formations payantes, pas de Udemy payant, pas de Coursera payant.'
                );
            } catch (\Throwable $e) {
                $this->warn("  Erreur recherche : {$e->getMessage()}");
                $totalErrors++;

                continue;
            }

            if (empty($response)) {
                $this->warn('  Recherche vide.');
                $totalErrors++;

                continue;
            }

            $lines = array_filter(array_map('trim', explode("\n", $response)));
            $added = 0;

            foreach ($lines as $line) {
                $parts = array_map('trim', explode('|', $line));

                if (count($parts) < 2) {
                    continue;
                }

                $title = $parts[0];
                $url = $parts[1];
                $language = isset($parts[2]) ? strtolower(trim($parts[2])) : 'en';

                if (! filter_var($url, FILTER_VALIDATE_URL)) {
                    continue;
                }

                if (! in_array($language, ['fr', 'en'])) {
                    $language = 'en';
                }

                if (ToolResource::where('url', $url)->exists()) {
                    $this->line("  Déjà existant : {$url}");

                    continue;
                }

                ToolResource::create([
                    'directory_tool_id' => $tool->id,
                    'url' => $url,
                    'title' => $title,
                    'type' => 'formation',
                    'language' => $language,
                    'is_approved' => true,
                ]);

                $this->info("  + [{$language}] {$title}");
                $added++;
                $totalFormations++;
            }

            if ($added === 0) {
                $this->line('  Aucune formation trouvée.');
            }
        }

        $this->newLine();
        $this->info("=== BILAN : {$tools->count()} outils, {$totalFormations} formations ajoutées, {$totalErrors} erreurs ===");

        return self::SUCCESS;
    }
}
