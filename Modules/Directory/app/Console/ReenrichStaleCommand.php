<?php

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;

class ReenrichStaleCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:reenrich-stale {--batch=3} {--months=3} {--force : Forcer même si kill switch actif}';

    protected $description = 'Re-enrichit les fiches outils dont la dernière mise à jour dépasse X mois';

    public function handle(): int
    {
        if (! class_exists(Tool::class)) {
            $this->error('Module Directory introuvable.');

            return self::FAILURE;
        }

        if ($this->shouldSkipForKillSwitch('cron.ai-enrich')) {
            return self::SUCCESS;
        }

        $apiKey = config('directory.openrouter_api_key') ?: env('OPENROUTER_API_KEY');
        if (empty($apiKey)) {
            $this->error('OPENROUTER_API_KEY non configurée.');

            return self::FAILURE;
        }

        $batch = max(1, (int) $this->option('batch'));
        $months = max(1, (int) $this->option('months'));
        $threshold = Carbon::now()->subMonths($months);

        $tools = Tool::where('status', 'published')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_enriched_at')
                    ->orWhere('last_enriched_at', '<', $threshold);
            })
            ->orderByRaw('last_enriched_at IS NOT NULL, last_enriched_at ASC')
            ->orderByDesc('clicks_count')
            ->limit($batch)
            ->get();

        if ($tools->isEmpty()) {
            $this->info('Aucun outil périmé trouvé.');

            return self::SUCCESS;
        }

        $this->info("{$tools->count()} outil(s) périmé(s) à re-enrichir (seuil : {$months} mois).");

        $openRouter = new OpenRouterService;
        $success = 0;
        $failures = 0;

        foreach ($tools as $tool) {
            $toolName = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;
            $toolUrl = $tool->url ?? '';

            $this->info("--- {$toolName} (ID:{$tool->id}, v{$tool->enrichment_version}) ---");

            try {
                $this->line('  Recherche sonar-pro...');
                $searchResult = $openRouter->search(
                    "Outil IA {$toolName} ({$toolUrl}) : fonctionnalités, pricing détaillé (plans et prix), cas d'utilisation, avantages, inconvénients, public cible, alternatives. ".now()->format('F Y').'.'
                );

                if (empty($searchResult)) {
                    $this->warn('  Recherche vide. Ignoré.');
                    $failures++;

                    continue;
                }

                $this->line('  Rédaction qwen3-max...');
                $description = $openRouter->generate(
                    "Rédige la fiche complète de {$toolName} ({$toolUrl}) avec ces sections H2 : À propos de {$toolName}, Fonctionnalités principales, Tarification, Cas d'utilisation, Notre avis. 800-1200 mots. Voici les informations :\n\n{$searchResult}",
                    "Tu rédiges des fiches d'outils IA en français québécois professionnel pour laveille.ai. Structure Markdown H2. Accents parfaits. Pas de titre H1. Pas d'emoji."
                );

                if (empty($description) || mb_strlen($description) < 200) {
                    $this->warn('  Génération trop courte. Ignoré.');
                    $failures++;

                    continue;
                }

                $shortDesc = $this->extractShortDescription($description, $toolName);

                $tool->setTranslation('description', 'fr_CA', $description);
                $tool->setTranslation('short_description', 'fr_CA', $shortDesc);
                $tool->last_enriched_at = now();
                $tool->enrichment_version = ($tool->enrichment_version ?? 1) + 1;
                $tool->save();

                $this->info('  OK — '.mb_strlen($description).' chars (v'.$tool->enrichment_version.')');
                $success++;
            } catch (\Throwable $e) {
                $this->warn("  Erreur : {$e->getMessage()}");
                Log::warning("[ReenrichStale] Échec {$toolName}", ['error' => $e->getMessage()]);
                $failures++;
            }
        }

        $this->newLine();
        $this->info("=== BILAN : {$success} re-enrichis, {$failures} échoués ===");

        return $failures > 0 && $success === 0 ? self::FAILURE : self::SUCCESS;
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

                return mb_strlen($clean) > 200 ? mb_substr($clean, 0, 197).'...' : $clean;
            }
        }

        return "{$toolName} est un outil d'intelligence artificielle.";
    }
}
