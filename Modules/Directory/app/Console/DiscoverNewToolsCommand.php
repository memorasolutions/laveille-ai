<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use App\Console\Concerns\HasKillSwitch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Directory\Services\ToolDiscoveryService;

class DiscoverNewToolsCommand extends Command
{
    use HasKillSwitch;

    protected $signature = 'tools:discover-new
        {--dry-run : Simuler sans insérer}
        {--source= : Filtrer une source (producthunt|rss)}
        {--force : Forcer même si kill switch actif}';

    protected $description = 'Découvre de nouveaux outils IA depuis Product Hunt et flux RSS';

    public function handle(): int
    {
        if ($this->shouldSkipForKillSwitch('cron.directory-discovery')) {
            return self::SUCCESS;
        }

        $dryRun = $this->option('dry-run');
        $source = $this->option('source');

        if ($source !== null && ! in_array($source, ['producthunt', 'rss'], true)) {
            $this->error("Source invalide : {$source}. Valeurs acceptées : producthunt, rss.");

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Mode simulation — aucune insertion.');
        }

        $this->info('Découverte de nouveaux outils IA...');

        try {
            $service = new ToolDiscoveryService;

            $discovered = match ($source) {
                'producthunt' => $service->fetchProductHunt(),
                'rss' => $service->fetchRssFeeds(),
                default => $service->discoverAll(),
            };

            $sourceLabel = $source ?? 'toutes les sources';
            $this->info("Source : {$sourceLabel}");

            $countDiscovered = count($discovered);

            if ($countDiscovered === 0) {
                $this->warn('Aucun nouvel outil découvert.');
            } else {
                $this->info("{$countDiscovered} outil(s) découvert(s).");
                $this->newLine();

                foreach ($discovered as $toolData) {
                    $name = $toolData['name'] ?? 'Sans nom';
                    $url = $toolData['url'] ?? 'N/A';

                    $this->line("  {$name} — {$url}");

                    if ($dryRun) {
                        $this->line("    [DRY] Serait ingéré : {$name}");

                        continue;
                    }

                    $result = $service->ingest($toolData);

                    if ($result) {
                        $this->info("    Créé (ID:{$result->id})");
                    } else {
                        $this->line('    '.$this->refusalLabel($service->getLastRefusalReason()));
                    }
                }
            }

            // Bilan chiffré de fin d'exécution (correctif 2026-08-22) : 'examined' inclut aussi
            // les candidats jamais parvenus jusqu'à $discovered (adresse ProductHunt non résolue,
            // rejetée dans fetchRssFeeds() avant que le candidat existe) - c'est pourquoi ce bilan
            // se fonde sur $service->getDiscoveryStats() et non sur $countDiscovered seul. Toujours
            // journalisé, même quand $countDiscovered === 0 : un pipeline qui examine puis refuse
            // tout ne doit jamais avoir l'air identique à un pipeline qui ne trouve rien.
            $stats = $service->getDiscoveryStats();

            $this->newLine();
            $this->info("=== BILAN : {$stats['examined']} candidat(s) examiné(s), {$stats['accepted']} accepté(s), {$stats['refused_total']} refusé(s) ===");
            $this->line("    Refusés - adresse ProductHunt non résolue : {$stats['refused']['adresse_non_resolue']}");
            $this->line("    Refusés - hôte d'agrégateur : {$stats['refused']['agregateur']}");
            $this->line("    Refusés - titre ressemblant à une commande : {$stats['refused']['titre_commande']}");
            $this->line("    Refusés - doublon : {$stats['refused']['doublon']}");

            Log::channel('directory_discovery')->info('[DiscoverNewTools] Bilan de fin d\'exécution', [
                'source' => $sourceLabel,
                'dry_run' => $dryRun,
                'discovered' => $countDiscovered,
                'examined' => $stats['examined'],
                'accepted' => $stats['accepted'],
                'refused_total' => $stats['refused_total'],
                'refused_adresse_non_resolue' => $stats['refused']['adresse_non_resolue'],
                'refused_agregateur' => $stats['refused']['agregateur'],
                'refused_titre_commande' => $stats['refused']['titre_commande'],
                'refused_doublon' => $stats['refused']['doublon'],
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Erreur : {$e->getMessage()}");
            Log::error('[DiscoverNewTools] Échec', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    /**
     * Message affiché par fiche refusée, aligné sur le VRAI motif (correctif 2026-08-22,
     * finition 2) : avant ce correctif, la ligne affichait « Doublon, ignoré. » pour les trois
     * motifs de refus d'ingest() confondus - y compris agrégateur et titre-commande - trompeur
     * pour quiconque lance la commande à la main. Vocabulaire repris tel quel des libellés du
     * bilan chiffré ci-dessus (« hôte d'agrégateur », « titre ressemblant à une commande »),
     * pour ne jamais nommer la même raison de deux façons différentes.
     */
    private function refusalLabel(?string $reason): string
    {
        return match ($reason) {
            'agregateur' => 'Hôte agrégateur, ignoré.',
            'titre_commande' => 'Titre ressemblant à une commande, ignoré.',
            'doublon' => 'Doublon, ignoré.',
            default => 'Refusé (raison indéterminée), ignoré.',
        };
    }
}
