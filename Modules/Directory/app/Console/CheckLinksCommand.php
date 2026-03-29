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

    public function handle(): int
    {
        $tools = Tool::published()->get();
        $ok = 0;
        $errors = 0;
        $redirects = 0;
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

                if ($status >= 400) {
                    $errors++;
                    $this->error("{$tool->name} → {$status}");

                    if ($this->option('fix')) {
                        $tool->update(['status' => 'draft']);
                    }
                } elseif ($status >= 300 && $status < 400) {
                    $redirect = $response->header('Location') ?? '';
                    $redirects++;
                    $this->warn("{$tool->name} → {$status} → {$redirect}");
                } else {
                    $ok++;
                }

                $rows[] = [$tool->name, $url, $status, $redirect];
            } catch (\Throwable $e) {
                $errors++;
                $rows[] = [$tool->name, $url, 'TIMEOUT', $e->getMessage()];
                $this->error("{$tool->name} → TIMEOUT");
            }
        }

        $this->table(['Nom', 'URL', 'Status', 'Redirect'], $rows);
        $this->info("OK: {$ok} | Erreurs: {$errors} | Redirects: {$redirects}");

        return self::SUCCESS;
    }
}
