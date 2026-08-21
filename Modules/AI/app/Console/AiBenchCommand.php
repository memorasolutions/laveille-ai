<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 */

namespace Modules\AI\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Modules\AI\Services\AiBenchService;

/**
 * ACTION : commande CLI du banc d'essai IA figé - rejoue des cas RÉELS gelés par tâche
 * contre plusieurs modèles candidats et sort un tableau qualité/coût/latence (design doc
 * 2026-08-21, idée neuve #1 « banc d'essai figé » de la stratégie de routage/cascade IA).
 * MCP: SELF (orchestration < 60 lignes utiles, toute la logique de mesure vit dans
 * AiBenchService/AiBenchAssertionService - jamais recopiée ici)
 * RAISON: OUTIL CLI de MESURE seulement (contraintes-sous-agents.md + spec) : aucune route,
 * aucune UI, aucun changement de comportement des features existantes, aucun bump de
 * version. Choisir les modèles par MESURE plutôt que par débat d'architecture.
 */
class AiBenchCommand extends Command
{
    protected $signature = 'ai:bench {--task=all} {--models=} {--out=}';

    protected $description = "Rejoue le banc d'essai IA figé (cas réels gelés) contre plusieurs modèles candidats et rapporte qualité, latence et coût";

    public function handle(AiBenchService $bench): int
    {
        $taskOption = (string) $this->option('task');
        $tasks = ($taskOption === '' || $taskOption === 'all')
            ? $bench->availableTasks()
            : [$taskOption];

        // Ne garder que les tâches qui ont réellement des cas gelés - une tâche demandée
        // sans fixture ne doit jamais faire échouer silencieusement toute la commande.
        $tasks = array_values(array_filter($tasks, fn (string $task): bool => $bench->loadCases($task) !== []));

        if ($tasks === []) {
            $this->error("Aucun cas gelé trouvé pour la tâche demandée ({$taskOption}).");

            return self::FAILURE;
        }

        $modelsOption = (string) ($this->option('models') ?: 'openai/gpt-4o-mini,deepseek/deepseek-chat');
        $models = array_values(array_filter(array_map('trim', explode(',', $modelsOption))));

        if ($models === []) {
            $this->error('Aucun modèle candidat fourni.');

            return self::FAILURE;
        }

        $this->info('Banc d\'essai IA - tâches : '.implode(', ', $tasks).' - modèles : '.implode(', ', $models));

        $rows = $bench->run($tasks, $models);
        $aggregates = $bench->aggregate($rows);

        $this->renderConsoleSummary($aggregates);

        $generatedAt = Carbon::now('America/Toronto');
        $outPath = (string) ($this->option('out') ?: storage_path('app/ai-bench-'.$generatedAt->format('Ymd-His').'.md'));

        $directory = dirname($outPath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($outPath, $bench->renderMarkdown($rows, $aggregates, $generatedAt));

        $this->info("Rapport écrit : {$outPath}");

        $echecs = count(array_filter($rows, static fn (array $r): bool => $r['status'] === 'ECHEC'));
        if ($echecs > 0) {
            $this->warn("{$echecs} appel(s) en échec technique (réseau/API) - voir le détail dans le rapport.");
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, array<string, mixed>>  $aggregates
     */
    private function renderConsoleSummary(array $aggregates): void
    {
        $this->table(
            ['Tâche', 'Modèle', 'Réussite', 'Latence moy.', 'Tokens moy.', 'Coût moy.', 'Source coût'],
            array_map(static fn (array $agg): array => [
                $agg['task'],
                $agg['model'],
                "{$agg['passed']}/{$agg['total']} ({$agg['rate']} %)",
                $agg['avg_latency_ms'] !== null ? $agg['avg_latency_ms'].' ms' : 'n/d',
                $agg['avg_tokens'] ?? 'n/d',
                $agg['avg_cost'] !== null ? number_format((float) $agg['avg_cost'], 6).' $' : 'n/d',
                $agg['cost_source_label'],
            ], $aggregates)
        );
    }
}
