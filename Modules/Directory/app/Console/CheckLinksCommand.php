<?php

declare(strict_types=1);

namespace Modules\Directory\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Modules\Directory\Models\Tool;

class CheckLinksCommand extends Command
{
    protected $signature = 'directory:check-links {--fix}';

    protected $description = 'Vérifie les liens externes de tous les outils publiés.';

    /**
     * Codes qui signifient vraiment « la ressource n'existe plus ». C'est la SEULE famille
     * qui justifie une mise en quarantaine.
     */
    private const DISPARU = [404, 410];

    public function handle(): int
    {
        $tools = Tool::published()->get();
        $ok = 0;
        $redirects = 0;
        $disparus = 0;
        $refus = 0;
        $ennuis = 0;
        $quarantined = [];
        $rows = [];

        foreach ($tools as $tool) {
            $url = $tool->url;

            if (! $url) {
                continue;
            }

            try {
                $response = Http::timeout(10)->withoutVerifying()->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaVeilleBot/1.0)',
                ])->get($url);

                $status = $response->status();
                $redirect = '';
                $statusDisplay = $status;

                if ($status >= 400) {
                    $family = $this->classify($status);
                    $statusDisplay = $this->statusLabel($status, $family);

                    if ($family === 'disparu') {
                        $disparus++;

                        if ($this->option('fix')) {
                            $tool->update(['status' => 'draft']);
                            $quarantined[] = $tool->name;
                            $this->error("{$tool->name} → {$status} (disparu, mis en quarantaine)");
                        } else {
                            $this->error("{$tool->name} → {$status} (disparu, --fix absent : aucune action)");
                        }
                    } elseif ($family === 'refus') {
                        $refus++;
                        $this->warn("{$tool->name} → {$status} (le site refuse le robot, probablement vivant : aucune action)");
                    } else {
                        $ennuis++;
                        $this->warn("{$tool->name} → {$status} (ennui serveur probablement transitoire : aucune action)");
                    }
                } elseif ($status >= 300 && $status < 400) {
                    $redirect = $response->header('Location') ?? '';
                    $redirects++;
                    $this->warn("{$tool->name} → {$status} → {$redirect}");
                } else {
                    $ok++;
                }

                $rows[] = [$tool->name, $url, $statusDisplay, $redirect];
            } catch (\Throwable $e) {
                $ennuis++;
                $statusDisplay = $this->statusLabel('TIMEOUT', 'ennui');
                $rows[] = [$tool->name, $url, $statusDisplay, $e->getMessage()];
                $this->warn("{$tool->name} → TIMEOUT (ennui serveur probablement transitoire : aucune action)");
            }
        }

        $this->table(['Nom', 'URL', 'Status', 'Redirect'], $rows);
        $this->info("OK: {$ok} | Redirects: {$redirects} | Disparus (404/410): {$disparus} | Refus du robot mais vivants: {$refus} | Ennuis serveur transitoires: {$ennuis}");

        if ($disparus === 0) {
            $this->info('Aucune fiche disparue : aucune quarantaine à appliquer.');
        } elseif ($this->option('fix')) {
            $noms = implode(', ', $quarantined);
            $this->info("Mises en quarantaine (statut=draft) : {$noms}");
        } else {
            $this->info("{$disparus} fiche(s) disparue(s) détectée(s), aucune quarantaine appliquée (relancer avec --fix pour agir).");
        }

        return self::SUCCESS;
    }

    /**
     * Classe un code HTTP d'échec (>= 400) dans l'une des trois familles. Seul « disparu »
     * justifie une action ; « refus » et « ennui » ne sont que des signalements.
     *
     * Tout code 4xx qui n'est pas dans DISPARU tombe dans la famille « refus » (le site est
     * vivant mais refuse notre robot, User-Agent LaVeilleBot/1.0 étant une signature évidente).
     * Les codes 401, 403, 405, 429 en sont les cas les plus fréquents, mais un autre code 4xx
     * non listé ici reste un refus, jamais une disparition : on n'agit QUE sur DISPARU.
     */
    private function classify(int $status): string
    {
        if (in_array($status, self::DISPARU, true)) {
            return 'disparu';
        }

        if ($status >= 400 && $status < 500) {
            return 'refus';
        }

        return 'ennui';
    }

    private function statusLabel(int|string $status, string $family): string
    {
        $label = match ($family) {
            'disparu' => 'disparu',
            'refus' => 'refus du robot, vivant',
            default => 'ennui serveur',
        };

        return "{$status} ({$label})";
    }
}
