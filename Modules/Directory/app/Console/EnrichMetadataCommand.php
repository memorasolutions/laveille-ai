<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;

final class EnrichMetadataCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:enrich-metadata {--batch=5} {--slug=} {--id=} {--force}';

    protected $description = 'Enrichit launch_year + target_audience via sonar-pro (JSON structuré)';

    public function handle(OpenRouterService $openRouter): int
    {
        if (! $this->option('force') && $this->shouldSkipForKillSwitch('cron.ai-enrich-metadata')) {
            return self::SUCCESS;
        }

        $tools = $this->resolveTools();

        if ($tools->isEmpty()) {
            $this->info('Aucun outil à enrichir.');

            return self::SUCCESS;
        }

        $enriched = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tools as $tool) {
            $name = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;

            try {
                $needsYear = $tool->launch_year === null;
                $needsAudience = empty($tool->target_audience);

                if (! $needsYear && ! $needsAudience) {
                    $skipped++;
                    $this->line("Skip : {$name} (déjà complet)");
                    continue;
                }

                $response = $openRouter->search($this->buildPrompt($name, (string) $tool->url));
                $data = $this->parseJson($response);

                if ($data === null || ! $this->validateData($data)) {
                    $failed++;
                    $this->warn("Données invalides pour : {$name}");
                    continue;
                }

                $updated = false;

                if ($needsYear && isset($data['launch_year']) && is_int($data['launch_year'])) {
                    $tool->launch_year = $data['launch_year'];
                    $updated = true;
                }

                if ($needsAudience && isset($data['target_audience']) && is_array($data['target_audience'])) {
                    $tool->target_audience = array_values(array_slice($data['target_audience'], 0, 4));
                    $updated = true;
                }

                if ($updated) {
                    $tool->save();
                    $enriched++;
                    $this->info("OK : {$name}");
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->warn("Erreur {$name} : {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("=== BILAN : {$enriched} enrichis / {$skipped} ignorés / {$failed} échoués ===");

        return self::SUCCESS;
    }

    private function resolveTools(): Collection
    {
        if ($id = $this->option('id')) {
            return Tool::where('id', (int) $id)->get();
        }

        if ($slug = $this->option('slug')) {
            return Tool::where('slug->fr_CA', $slug)
                ->orWhere('slug->fr', $slug)
                ->orWhere('slug->en', $slug)
                ->get();
        }

        return Tool::query()
            ->where(function ($q) {
                $q->whereNull('launch_year')->orWhereNull('target_audience');
            })
            ->whereIn('status', ['published', 'pending'])
            ->orderByDesc('clicks_count')
            ->orderByDesc('is_featured')
            ->limit((int) $this->option('batch'))
            ->get();
    }

    private function buildPrompt(string $name, string $url): string
    {
        return "Pour l'outil IA \"{$name}\" ({$url}), fournis ces informations en JSON STRICT uniquement, sans texte autour :\n\n"
            ."- \"launch_year\" : année de lancement (entier 2000-2026). Si inconnue, null.\n"
            ."- \"target_audience\" : tableau de 2 à 4 audiences cibles en français parmi : "
            ."\"Développeurs\", \"Entreprises\", \"Éducation\", \"Marketeurs\", \"Créateurs de contenu\", "
            ."\"Data scientists\", \"Designers\", \"Étudiants\", \"Chercheurs\", \"Freelances\".\n\n"
            .'Exemple strict : {"launch_year": 2023, "target_audience": ["Développeurs", "Data scientists"]}'."\n"
            .'Réponds UNIQUEMENT avec le JSON, rien d\'autre.';
    }

    private function parseJson(string $response): ?array
    {
        if (! preg_match('/\{[^{}]*\}/s', $response, $m)) {
            return null;
        }
        $decoded = json_decode($m[0], true);

        return is_array($decoded) ? $decoded : null;
    }

    private function validateData(array $data): bool
    {
        if (array_key_exists('launch_year', $data)) {
            $y = $data['launch_year'];
            if ($y !== null && (! is_int($y) || $y < 2000 || $y > 2026)) {
                return false;
            }
        }

        if (isset($data['target_audience'])) {
            if (! is_array($data['target_audience']) || count($data['target_audience']) > 6) {
                return false;
            }
            foreach ($data['target_audience'] as $a) {
                if (! is_string($a) || trim($a) === '') {
                    return false;
                }
            }
        }

        return true;
    }
}
