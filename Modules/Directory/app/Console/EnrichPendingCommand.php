<?php

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;

class EnrichPendingCommand extends Command
{
    protected $signature = 'tools:enrich-pending {--batch=3}';

    protected $description = 'Enrichit les fiches outils IA via OpenRouter (sonar-pro recherche + qwen3-max rédaction)';

    public function handle(): int
    {
        $apiKey = config('directory.openrouter_api_key') ?: env('OPENROUTER_API_KEY');
        if (empty($apiKey)) {
            $this->error('OPENROUTER_API_KEY non configurée.');

            return self::FAILURE;
        }

        $openRouter = new OpenRouterService;
        $batch = max(1, (int) $this->option('batch'));

        $tools = Tool::query()
            ->where('status', 'published')
            ->whereRaw("CHAR_LENGTH(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(description, '$.\"fr_CA\"')), '')) < 500")
            ->orderByDesc('clicks_count')
            ->orderByDesc('is_featured')
            ->limit($batch)
            ->get();

        if ($tools->isEmpty()) {
            $this->info('Tous les outils sont enrichis. Rien à faire.');

            return self::SUCCESS;
        }

        $this->info("{$tools->count()} outil(s) à enrichir.");

        $enriched = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tools as $tool) {
            $toolName = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;
            $toolUrl = $tool->url ?? '';
            $currentDesc = $tool->getTranslation('description', 'fr_CA', false) ?? '';

            $this->info("--- {$toolName} (ID:{$tool->id}) ---");

            if (mb_strlen($currentDesc) > 1000) {
                $this->line("  Déjà enrichi (> 1000 chars). Ignoré.");
                $skipped++;

                continue;
            }

            try {
                // Étape 1 : recherche via sonar-pro
                $this->line("  Recherche sonar-pro...");
                $searchResult = $openRouter->search(
                    "Outil IA {$toolName} ({$toolUrl}) : fonctionnalités, pricing détaillé (plans et prix), cas d'utilisation, avantages, inconvénients, public cible, alternatives. Avril 2026."
                );

                if (empty($searchResult)) {
                    $this->warn("  Recherche vide. Ignoré.");
                    $failed++;

                    continue;
                }

                // Étape 2 : rédaction via qwen3-max
                $this->line("  Rédaction qwen3-max...");
                $description = $openRouter->generate(
                    "Rédige la fiche complète de {$toolName} ({$toolUrl}) avec ces sections H2 : À propos de {$toolName}, Fonctionnalités principales, Tarification, Cas d'utilisation, Notre avis. 800-1200 mots. Voici les informations :\n\n{$searchResult}",
                    "Tu rédiges des fiches d'outils IA en français québécois professionnel pour laveille.ai. Structure Markdown H2. Accents parfaits. Pas de titre H1. Pas d'emoji."
                );

                if (empty($description) || mb_strlen($description) < 200) {
                    $this->warn("  Génération trop courte. Ignoré.");
                    $failed++;

                    continue;
                }

                // Étape 3 : extraire short_description (première phrase après "À propos")
                $shortDesc = $this->extractShortDescription($description, $toolName);

                // Étape 4 : sauvegarder
                $tool->setTranslation('description', 'fr_CA', $description);
                $tool->setTranslation('short_description', 'fr_CA', $shortDesc);
                $tool->save();

                $this->info("  OK — " . mb_strlen($description) . " chars");
                $enriched++;
            } catch (\Throwable $e) {
                $this->warn("  Erreur : {$e->getMessage()}");
                Log::warning("[EnrichPending] Échec {$toolName}", ['error' => $e->getMessage()]);
                $failed++;
            }
        }

        $this->newLine();
        $this->info("=== BILAN : {$enriched} enrichis, {$skipped} ignorés, {$failed} échoués ===");

        return self::SUCCESS;
    }

    private function extractShortDescription(string $content, string $toolName): string
    {
        $lines = explode("\n", $content);
        $inAbout = false;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^##\s*À propos/iu', $trimmed)) {
                $inAbout = true;

                continue;
            }
            if ($inAbout && str_starts_with($trimmed, '##')) {
                break;
            }
            if ($inAbout && $trimmed !== '' && ! str_starts_with($trimmed, '#')) {
                $clean = strip_tags(preg_replace('/\*{1,2}([^*]+)\*{1,2}/', '$1', $trimmed));

                return mb_strlen($clean) > 200 ? mb_substr($clean, 0, 197) . '...' : $clean;
            }
        }

        return "{$toolName} est un outil d'intelligence artificielle.";
    }
}
