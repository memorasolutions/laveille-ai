<?php

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;

class RefreshPricingCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:refresh-pricing {--batch=5} {--force : Forcer même si kill switch actif}';

    protected $description = 'Vérifie et met à jour le pricing des outils via sonar-pro';

    public function handle(): int
    {
        if ($this->shouldSkipForKillSwitch('cron.directory-pricing')) {
            return self::SUCCESS;
        }

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
            ->orderBy('updated_at', 'asc')
            ->limit($batch)
            ->get();

        if ($tools->isEmpty()) {
            $this->info('Aucun outil à vérifier.');

            return self::SUCCESS;
        }

        $this->info("{$tools->count()} outil(s) à vérifier.");

        $verified = 0;
        $modified = 0;
        $errors = 0;

        foreach ($tools as $tool) {
            $toolName = $tool->getTranslation('name', 'fr_CA', false) ?: $tool->name;
            $toolUrl = $tool->url ?? '';

            $this->info("--- {$toolName} (ID:{$tool->id}) ---");

            try {
                $response = $openRouter->search(
                    "Quel est le pricing actuel de {$toolName} ({$toolUrl}) en ".now()->format('F Y').' ? Répondre UNIQUEMENT par un seul mot parmi : free, freemium, paid, open_source. Puis le détail des plans et prix.'
                );

                if (empty($response)) {
                    $this->warn('  Recherche vide.');
                    $errors++;

                    continue;
                }

                $firstLine = mb_strtolower(trim(strtok($response, "\n")));
                $firstLine = str_replace('open-source', 'open_source', $firstLine);

                $validTypes = ['open_source', 'freemium', 'paid', 'free'];

                $newPricing = null;
                foreach ($validTypes as $type) {
                    if (str_contains($firstLine, $type)) {
                        $newPricing = $type;
                        break;
                    }
                }

                if (! $newPricing) {
                    $this->warn("  Pricing non parsé : \"{$firstLine}\"");
                    $errors++;

                    continue;
                }

                $allowedPricings = ['free', 'freemium', 'paid', 'open_source', 'enterprise'];
                if (! in_array($newPricing, $allowedPricings, true)) {
                    $this->warn("  Pricing invalide rejeté : \"{$newPricing}\"");
                    $errors++;

                    continue;
                }

                $verified++;

                if ($tool->pricing !== $newPricing) {
                    $oldPricing = $tool->pricing;
                    $tool->update(['pricing' => $newPricing]);
                    $modified++;

                    Log::info('[RefreshPricing] Pricing mis à jour', [
                        'tool' => $toolName,
                        'old' => $oldPricing,
                        'new' => $newPricing,
                    ]);

                    $this->line("  {$oldPricing} → {$newPricing}");
                } else {
                    $this->line("  Inchangé ({$newPricing})");
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::warning("[RefreshPricing] Échec {$toolName}", ['error' => $e->getMessage()]);
                $this->warn("  Erreur : {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("=== BILAN : {$verified} vérifiés, {$modified} modifiés, {$errors} erreurs ===");

        return self::SUCCESS;
    }
}
