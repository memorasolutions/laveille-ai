<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Modules\Directory\Models\Tool;
use Modules\Directory\Services\OpenRouterService;

class ConvertPricesCadCommand extends Command
{
    protected $signature = 'directory:convert-prices-cad {--dry-run : afficher sans sauvegarder} {--limit=20 : nombre max d outils} {--rate=1.38 : taux USD vers CAD}';

    protected $description = 'Convertit les montants USD présents dans les fiches outils en dollars canadiens approximatifs (≈ X $ CA, facturé ~Y $ US).';

    public function handle(OpenRouterService $openRouter): int
    {
        $rate = (float) $this->option('rate');
        $limit = (int) $this->option('limit');
        $dry = (bool) $this->option('dry-run');

        $candidates = Tool::whereNull('prices_converted_cad_at')
            ->where(function ($q) {
                $q->where('description->fr_CA', 'like', '%$%')
                    ->orWhere('description->fr_CA', 'like', '%USD%');
            })
            ->limit($limit * 3)
            ->get();

        $tools = [];
        foreach ($candidates as $tool) {
            $desc = $tool->getTranslation('description', 'fr_CA', false);
            if ($desc === null) {
                continue;
            }
            if (preg_match('/\$|\bUSD\b/i', $desc)) {
                $tools[] = $tool;
                if (count($tools) >= $limit) {
                    break;
                }
            }
        }

        if (empty($tools)) {
            $this->info('Aucune fiche à convertir.');

            return self::SUCCESS;
        }

        if (! $dry) {
            $backupDir = storage_path('app/price-backups');
            File::ensureDirectoryExists($backupDir);
            $backupData = [];
            foreach ($tools as $tool) {
                $backupData[$tool->id] = $tool->getTranslation('description', 'fr_CA', false);
            }
            $backupPath = $backupDir . '/backup-' . now()->format('Ymd-His') . '.json';
            File::put($backupPath, json_encode($backupData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line("Backup: {$backupPath}");
        }

        $converted = 0;
        $skipped = 0;

        foreach ($tools as $tool) {
            $orig = $tool->getTranslation('description', 'fr_CA', false);
            if ($orig === null) {
                $skipped++;
                continue;
            }

            $name = $tool->getTranslation('name', 'fr_CA', false) ?: ('#' . $tool->id);

            $prompt = "Voici une fiche d'outil en Markdown. Convertis UNIQUEMENT les montants d'argent exprimés en dollars américains (ou en \$ ambigu) vers des dollars canadiens approximatifs au taux 1 USD = {$rate} CAD. Format exact : « ≈ X \$ CA » et, entre parenthèses, la devise d'origine « (facturé ~Y \$ US) ». N'ajoute, ne retire et ne reformule AUCUN autre mot : garde la structure Markdown, les titres, l'ordre et le reste du texte STRICTEMENT identiques. Si aucun montant n'est présent, renvoie le texte inchangé. Renvoie UNIQUEMENT le texte Markdown final, sans commentaire.\n\n---\n" . $orig;

            try {
                $new = trim($openRouter->generate($prompt, "Tu es un convertisseur de devises chirurgical. Tu ne modifies que les montants. Tu préserves tout le reste."));
                // Retire un éventuel séparateur « --- » que le LLM aurait recopié en tête.
                $new = preg_replace('/^\s*-{3,}\s*\n+/', '', $new);
                $new = trim((string) $new);
            } catch (\Exception $e) {
                $this->warn("SKIP #{$tool->id} {$name} (erreur LLM: {$e->getMessage()})");
                $skipped++;
                continue;
            }

            $origLen = mb_strlen($orig);
            $newLen = mb_strlen($new);

            if ($new === '' || $newLen < $origLen * 0.85 || $newLen > $origLen * 1.4) {
                $this->warn("SKIP #{$tool->id} {$name} (variation de longueur suspecte)");
                $skipped++;
                continue;
            }

            if ($dry) {
                $preview = mb_substr($new, 0, 200);
                $this->line("DRY-RUN #{$tool->id} {$name}: {$preview}");
            } else {
                $tool->setTranslation('description', 'fr_CA', $new);
                $tool->prices_converted_cad_at = now();
                $tool->saveQuietly();
                $this->info("OK #{$tool->id} {$name}");
                $converted++;
            }
        }

        $this->info("Total convertis: {$converted}, sautés: {$skipped}");

        return self::SUCCESS;
    }
}
