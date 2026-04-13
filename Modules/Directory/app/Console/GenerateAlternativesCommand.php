<?php

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;

class GenerateAlternativesCommand extends Command
{
    protected $signature = 'tools:generate-alternatives {--batch=5}';

    protected $description = 'Génère les alternatives croisées pour les outils IA via sonar-pro';

    public function handle(): int
    {
        if (! class_exists(Tool::class)) {
            $this->error('Le module Directory est désactivé ou introuvable.');

            return self::FAILURE;
        }

        $batch = max(1, (int) $this->option('batch'));
        $openRouterService = new OpenRouterService;

        $tools = Tool::where('status', 'published')
            ->whereDoesntHave('alternatives')
            ->limit($batch)
            ->get();

        if ($tools->isEmpty()) {
            $this->info('Aucun outil publié sans alternatives trouvé.');

            return self::SUCCESS;
        }

        $this->info("{$tools->count()} outil(s) à traiter...");
        $this->newLine();

        $totalProcessed = 0;
        $totalLinked = 0;

        foreach ($tools as $tool) {
            $name = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;
            $this->info("--- {$name} (ID:{$tool->id}) ---");

            $query = "Quelles sont les 5 meilleures alternatives à {$name} (outil IA) ? Liste uniquement les noms exacts des outils, séparés par des virgules, sans explication.";

            try {
                $result = $openRouterService->search($query);
            } catch (\Throwable $e) {
                $this->warn("  Erreur API pour {$name} : {$e->getMessage()}");
                $totalProcessed++;
                sleep(3);

                continue;
            }

            $alternativeNames = array_filter(array_map('trim', explode(',', $result)));

            foreach ($alternativeNames as $altName) {
                if (empty($altName)) {
                    continue;
                }

                $altTool = Tool::where('status', 'published')
                    ->whereRaw("JSON_EXTRACT(name, '$.fr_CA') LIKE ?", ["%{$altName}%"])
                    ->first();

                if (! $altTool) {
                    $this->line("  - {$altName} : non trouvé en DB");

                    continue;
                }

                if ($altTool->id === $tool->id) {
                    continue;
                }

                if ($tool->alternatives()->where('alternative_tool_id', $altTool->id)->exists()) {
                    $this->line("  - {$altName} : déjà lié");

                    continue;
                }

                $tool->alternatives()->attach($altTool->id, [
                    'relevance_score' => 70,
                    'source' => 'auto',
                ]);

                $this->info("  + {$altName} : lié");
                $totalLinked++;
            }

            $totalProcessed++;

            if ($tool !== $tools->last()) {
                sleep(3);
            }
        }

        $this->newLine();
        $this->info("=== BILAN : {$totalProcessed} outil(s) traité(s), {$totalLinked} alternative(s) liée(s) ===");

        return self::SUCCESS;
    }
}
