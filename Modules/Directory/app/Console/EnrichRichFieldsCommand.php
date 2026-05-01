<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;
use Throwable;

final class EnrichRichFieldsCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:enrich-rich-fields {--batch=3} {--slug=} {--id=} {--force}';

    protected $description = 'Enrichit core_features + use_cases + pros + cons + faq + how_to_use via sonar-pro (JSON FR-CA)';

    public function handle(OpenRouterService $openRouter): int
    {
        if (! $this->option('force') && $this->shouldSkipForKillSwitch('cron.ai-enrich-rich-fields')) {
            return self::SUCCESS;
        }

        $tools = $this->resolveTools();

        if ($tools->isEmpty()) {
            $this->info('Aucun outil à enrichir');
            return self::SUCCESS;
        }

        $enriched = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($tools as $tool) {
            $name = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;

            try {
                $allFilled = true;
                foreach (['core_features', 'use_cases', 'pros', 'cons', 'how_to_use'] as $field) {
                    if (empty($tool->getTranslation($field, 'fr_CA', false))) {
                        $allFilled = false;
                        break;
                    }
                }
                if ($allFilled && ! empty($tool->faq)) {
                    $skipped++;
                    $this->line("Skip : {$name} (déjà complet)");
                    continue;
                }

                $response = $openRouter->search($this->buildPrompt($name, (string) $tool->url));
                $data = $this->parseJson($response);

                if ($data === null || ! $this->validateData($data)) {
                    $this->warn("Données invalides : {$name}");
                    $failed++;
                    continue;
                }

                $tool->setTranslation('core_features', 'fr_CA', $data['core_features']);
                $tool->setTranslation('use_cases', 'fr_CA', $data['use_cases']);
                $tool->setTranslation('pros', 'fr_CA', $data['pros']);
                $tool->setTranslation('cons', 'fr_CA', $data['cons']);
                $tool->setTranslation('how_to_use', 'fr_CA', $data['how_to_use']);
                $tool->faq = $data['faq'];
                $tool->last_enriched_at = now();
                $tool->save();

                $this->info("OK : {$name}");
                $enriched++;
            } catch (Throwable $e) {
                $failed++;
                Log::warning("[EnrichRichFields] Échec {$name}", ['error' => $e->getMessage()]);
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
                $q->whereNull('core_features')
                    ->orWhereNull('use_cases')
                    ->orWhereNull('pros')
                    ->orWhereNull('cons')
                    ->orWhereNull('faq')
                    ->orWhereNull('how_to_use');
            })
            ->whereIn('status', ['published', 'pending'])
            ->orderByDesc('clicks_count')
            ->orderByDesc('is_featured')
            ->limit((int) $this->option('batch'))
            ->get();
    }

    private function buildPrompt(string $name, string $url): string
    {
        return "Pour l'outil IA \"{$name}\" ({$url}), fournis ces informations en français québécois professionnel, en JSON STRICT uniquement, sans texte autour :\n\n"
            . "- \"core_features\" : 4 à 6 fonctionnalités principales séparées par virgule (string max 250 chars).\n"
            . "- \"use_cases\" : 3 à 5 cas d'usage concrets séparés par virgule (string max 250 chars).\n"
            . "- \"pros\" : 3 à 5 avantages clairs séparés par virgule (string max 250 chars).\n"
            . "- \"cons\" : 2 à 4 inconvénients honnêtes séparés par virgule (string max 200 chars).\n"
            . "- \"faq\" : tableau de 3 à 5 objets {\"question\":\"...\",\"answer\":\"...\"} (questions courtes, réponses ~150 chars).\n"
            . "- \"how_to_use\" : string max 200 chars expliquant les premiers pas.\n\n"
            . "Exemple : {\"core_features\":\"GPT-4o, Voix temps réel, Mémoire, Recherche web, Plugins\",\"use_cases\":\"Rédaction, Code, Analyse\",\"pros\":\"Polyvalent, Rapide, Écosystème riche\",\"cons\":\"Limites gratuit, Confidentialité\",\"faq\":[{\"question\":\"Est-ce gratuit ?\",\"answer\":\"Oui en plan de base.\"}],\"how_to_use\":\"Aller sur chat.openai.com et taper une question.\"}\n\n"
            . "Réponds UNIQUEMENT avec le JSON, rien d'autre.";
    }

    private function parseJson(string $response): ?array
    {
        if (! preg_match('/\{.*\}/s', $response, $matches)) {
            return null;
        }

        $decoded = json_decode($matches[0], true);

        return is_array($decoded) ? $decoded : null;
    }

    private function validateData(array $data): bool
    {
        $requiredKeys = ['core_features', 'use_cases', 'pros', 'cons', 'faq', 'how_to_use'];
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
        }

        foreach (['core_features', 'use_cases', 'pros', 'cons', 'how_to_use'] as $field) {
            if (! is_string($data[$field]) || trim($data[$field]) === '' || strlen($data[$field]) > 300) {
                return false;
            }
        }

        if (! is_array($data['faq']) || count($data['faq']) < 1 || count($data['faq']) > 6) {
            return false;
        }

        foreach ($data['faq'] as $item) {
            if (! is_array($item) || ! isset($item['question'], $item['answer'])) {
                return false;
            }
            if (! is_string($item['question']) || trim($item['question']) === '') {
                return false;
            }
            if (! is_string($item['answer']) || trim($item['answer']) === '') {
                return false;
            }
        }

        return true;
    }
}
