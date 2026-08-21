<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

namespace Modules\AI\Services;

use Illuminate\Support\Facades\Http;
use Modules\Core\Services\OpenRouterPrivacy;

/**
 * ACTION : moteur du banc d'essai IA figé (SPEC-BANC-ESSAI-IA, idée neuve #1 du design doc
 * 2026-08-21 « stratégie de routage/cascade IA »). Rejoue des cas RÉELS gelés par tâche
 * contre plusieurs modèles candidats via OpenRouter et rapporte pass/fail, latence, tokens
 * et coût - pour choisir les modèles par MESURE plutôt que par débat d'architecture.
 * MCP: SELF (assemblage de payload + parsing de réponse, pattern REPRIS TEL QUEL de
 * Modules\News\Services\AiSummaryService::callModelCascade() : Http::post OpenRouter,
 * troncature déjà faite en amont par l'appelant, parsing de usage, gestion d'erreur PAR
 * MODÈLE) et de Modules\Core\Services\OpenRouterPrivacy::applyTo() (deny+zdr) - AUCUN
 * nouveau client LLM écrit ici.
 * RAISON: c'est un OUTIL CLI de MESURE seulement (design doc, critère d'acceptation Ph2) :
 * zéro changement de comportement des features existantes, zéro route, zéro UI. Un échec
 * d'appel (réseau, timeout, réponse invalide) produit une ligne ECHEC isolée, jamais un
 * crash de l'ensemble du banc - chaque appel est protégé par son propre try/catch, exactement
 * comme la cascade News protège chaque modèle de la cascade.
 *
 * Contenu utilisateur jamais journalisé (Loi 25, contraintes-sous-agents.md section 1) : ni
 * le texte des cas, ni la réponse brute du modèle ne passent par Log::* - seuls des
 * identifiants de cas/tâche/modèle et des métriques agrégées circulent.
 */
class AiBenchService
{
    private const OPENROUTER_URL = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * Timeout court par appel (design doc section 1) : le banc doit rester rejouable en
     * quelques dizaines de secondes, jamais bloquer sur un modèle lent.
     */
    private const TIMEOUT_SECONDS = 25;

    /**
     * Table de repli APPROXIMATIVE, utilisée UNIQUEMENT quand OpenRouter ne renvoie pas
     * usage.cost (design doc 2026-08-21 : « préférer le coût RAPPORTÉ, jamais une table de
     * prix maison qui périme » - ceci est le repli explicitement toléré, pas le chemin
     * principal). $/1000 tokens, [entrée, sortie].
     */
    private const APPROX_RATES_PER_1K = [
        'openai/gpt-4o-mini' => [0.00015, 0.0006],
        'deepseek/deepseek-chat' => [0.00027, 0.0011],
    ];

    private const APPROX_RATE_DEFAULT = [0.001, 0.002];

    public function __construct(
        private readonly AiBenchAssertionService $assertions,
    ) {
    }

    /**
     * Tâches disponibles = un fichier {tache}.json par tâche sous database/bench/. Liste
     * ouverte et extensible (spec section 2) - jamais une liste figée en dur dans le code.
     *
     * @return array<int, string>
     */
    public function availableTasks(): array
    {
        $files = glob($this->benchPath('*.json')) ?: [];
        $tasks = array_map(static fn (string $file): string => pathinfo($file, PATHINFO_FILENAME), $files);
        sort($tasks);

        return $tasks;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function loadCases(string $task): array
    {
        $path = $this->benchPath("{$task}.json");
        if (! is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Exécute UN cas contre UN modèle. Jamais de throw vers l'appelant : toute exception ou
     * réponse invalide devient une ligne 'ECHEC' avec sa raison, la commande continue.
     *
     * @param  array<string, mixed>  $case  {id, systemPrompt?, input, assertions[]}
     * @return array<string, mixed>
     */
    public function runCase(string $task, array $case, string $model): array
    {
        $row = [
            'task' => $task,
            'model' => $model,
            'case_id' => (string) ($case['id'] ?? 'inconnu'),
            'status' => 'ECHEC',
            'latency_ms' => null,
            'tokens' => null,
            'cost' => null,
            'cost_source' => null,
            'reason' => null,
        ];

        $apiKey = config('services.openrouter.api_key');
        if (! $apiKey) {
            $row['reason'] = 'OPENROUTER_API_KEY non configurée';

            return $row;
        }

        $messages = [];
        if (! empty($case['systemPrompt'])) {
            $messages[] = ['role' => 'system', 'content' => (string) $case['systemPrompt']];
        }
        $messages[] = ['role' => 'user', 'content' => (string) ($case['input'] ?? '')];

        // ACTION : bloc partagé OpenRouterPrivacy (deny+zdr), jamais recopié - même garde que
        // AiSummaryService::callModelCascade(). 'usage.include' demande à OpenRouter de
        // rapporter le coût réel de l'appel (usage.cost) plutôt qu'une estimation locale.
        $payload = OpenRouterPrivacy::applyTo([
            'model' => $model,
            'messages' => $messages,
            'temperature' => 0.3,
            'usage' => ['include' => true],
        ]);

        $start = microtime(true);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
            ])->timeout(self::TIMEOUT_SECONDS)->post(self::OPENROUTER_URL, $payload);
        } catch (\Throwable $e) {
            $row['latency_ms'] = $this->elapsedMs($start);
            $row['reason'] = 'exception réseau : '.$e->getMessage();

            return $row;
        }

        $row['latency_ms'] = $this->elapsedMs($start);

        $data = $response->json();
        if (! $response->successful() || ! isset($data['choices'][0]['message']['content'])) {
            $errorMessage = $data['error']['message'] ?? ('HTTP '.$response->status());
            $row['reason'] = "réponse API invalide : {$errorMessage}";

            return $row;
        }

        [$row['tokens'], $row['cost'], $row['cost_source']] = $this->extractUsage($data, $model);

        $content = trim((string) $data['choices'][0]['message']['content']);
        $content = (string) preg_replace('/^```json?\s*/i', '', $content);
        $content = (string) preg_replace('/\s*```$/', '', $content);

        foreach ((array) ($case['assertions'] ?? []) as $assertion) {
            $result = $this->assertions->evaluate((array) $assertion, $content);
            if (! $result['ok']) {
                $row['status'] = 'FAIL';
                $row['reason'] = $result['reason'];

                return $row;
            }
        }

        $row['status'] = 'PASS';

        return $row;
    }

    /**
     * @param  array<int, string>  $tasks
     * @param  array<int, string>  $models
     * @return array<int, array<string, mixed>>
     */
    public function run(array $tasks, array $models): array
    {
        $rows = [];
        foreach ($tasks as $task) {
            foreach ($this->loadCases($task) as $case) {
                foreach ($models as $model) {
                    $rows[] = $this->runCase($task, $case, $model);
                }
            }
        }

        return $rows;
    }

    /**
     * Regroupe les lignes par (tâche, modèle) et calcule les statistiques agrégées.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    public function aggregate(array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            $key = $row['task'].'|'.$row['model'];
            $groups[$key]['task'] ??= $row['task'];
            $groups[$key]['model'] ??= $row['model'];
            $groups[$key]['rows'][] = $row;
        }

        $aggregates = [];
        foreach ($groups as $group) {
            $groupRows = $group['rows'];
            $total = count($groupRows);
            $passed = count(array_filter($groupRows, static fn (array $r): bool => $r['status'] === 'PASS'));

            $latencies = array_values(array_filter(array_column($groupRows, 'latency_ms'), static fn ($v) => $v !== null));
            $tokens = array_values(array_filter(array_column($groupRows, 'tokens'), static fn ($v) => $v !== null));
            $costs = array_values(array_filter(array_column($groupRows, 'cost'), static fn ($v) => $v !== null));
            $sources = array_values(array_unique(array_filter(array_column($groupRows, 'cost_source'))));

            $aggregates[] = [
                'task' => $group['task'],
                'model' => $group['model'],
                'total' => $total,
                'passed' => $passed,
                'rate' => $total > 0 ? round($passed / $total * 100, 1) : 0.0,
                'avg_latency_ms' => $latencies !== [] ? round(array_sum($latencies) / count($latencies), 1) : null,
                'avg_tokens' => $tokens !== [] ? round(array_sum($tokens) / count($tokens), 1) : null,
                'avg_cost' => $costs !== [] ? round(array_sum($costs) / count($costs), 6) : null,
                'cost_source_label' => $this->costSourceLabel($sources),
            ];
        }

        return $aggregates;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>>  $aggregates
     */
    public function renderMarkdown(array $rows, array $aggregates, \DateTimeInterface $generatedAt): string
    {
        $md = "# Banc d'essai IA - laveille.ai\n\n";
        $md .= 'Généré le '.$generatedAt->format('Y-m-d H:i:s')." (America/Toronto)\n\n";

        $tasks = array_values(array_unique(array_column($aggregates, 'task')));
        sort($tasks);

        foreach ($tasks as $task) {
            $md .= "## Tâche : {$task}\n\n";
            $md .= "| Modèle | Réussite | Latence moy. | Tokens moy. | Coût moy. | Source du coût |\n";
            $md .= "|---|---|---|---|---|---|\n";

            foreach ($aggregates as $agg) {
                if ($agg['task'] !== $task) {
                    continue;
                }

                $md .= sprintf(
                    "| %s | %d/%d (%s %%) | %s | %s | %s | %s |\n",
                    $agg['model'],
                    $agg['passed'],
                    $agg['total'],
                    $agg['rate'],
                    $agg['avg_latency_ms'] !== null ? $agg['avg_latency_ms'].' ms' : 'n/d',
                    $agg['avg_tokens'] ?? 'n/d',
                    $agg['avg_cost'] !== null ? number_format((float) $agg['avg_cost'], 6).' $' : 'n/d',
                    $agg['cost_source_label']
                );
            }

            $failures = array_filter($rows, static fn (array $r): bool => $r['task'] === $task && $r['status'] !== 'PASS');
            if ($failures !== []) {
                $md .= "\n### Échecs\n\n";
                foreach ($failures as $f) {
                    $md .= sprintf(
                        "- cas=%s modèle=%s statut=%s raison=%s\n",
                        $f['case_id'],
                        $f['model'],
                        $f['status'],
                        $f['reason'] ?? 'n/d'
                    );
                }
            }

            $md .= "\n";
        }

        return $md;
    }

    /**
     * @return array{0: ?int, 1: ?float, 2: ?string}
     */
    private function extractUsage(array $data, string $model): array
    {
        $usage = (array) ($data['usage'] ?? []);
        $tokens = isset($usage['total_tokens']) ? (int) $usage['total_tokens'] : null;

        // Coût RAPPORTÉ par OpenRouter, toujours préféré (design doc section (c)) - jamais
        // une estimation quand la valeur réelle est disponible.
        if (isset($usage['cost'])) {
            return [$tokens, (float) $usage['cost'], 'rapporte'];
        }

        if ($tokens === null) {
            return [null, null, null];
        }

        // Repli d'estimation, marqué explicitement comme tel dans la sortie (jamais confondu
        // avec un coût réel).
        $promptTokens = isset($usage['prompt_tokens']) ? (int) $usage['prompt_tokens'] : intdiv($tokens, 2);
        $completionTokens = isset($usage['completion_tokens']) ? (int) $usage['completion_tokens'] : max(0, $tokens - $promptTokens);
        [$inRate, $outRate] = self::APPROX_RATES_PER_1K[$model] ?? self::APPROX_RATE_DEFAULT;
        $estimated = ($promptTokens * $inRate / 1000) + ($completionTokens * $outRate / 1000);

        return [$tokens, round($estimated, 6), 'estime'];
    }

    /**
     * @param  array<int, string>  $sources
     */
    private function costSourceLabel(array $sources): string
    {
        if ($sources === []) {
            return 'n/d';
        }

        if (count($sources) === 1) {
            return $sources[0] === 'rapporte' ? 'rapporté' : 'estimé (approx.)';
        }

        return 'mixte (rapporté + estimé)';
    }

    private function elapsedMs(float $start): float
    {
        return round((microtime(true) - $start) * 1000, 1);
    }

    private function benchPath(string $file): string
    {
        return module_path('AI', 'database/bench/'.$file);
    }
}
