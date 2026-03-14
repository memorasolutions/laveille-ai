<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditCommand extends Command
{
    protected $signature = 'app:audit';

    protected $description = 'Audit de santé du projet pour la production';

    public function handle(): int
    {
        $this->info('Audit de santé du projet...');
        $this->line('');

        $passed = 0;
        $total = 0;

        $checks = [
            ['APP_DEBUG est désactivé', ! config('app.debug')],
            ['APP_ENV est production', app()->environment('production')],
            ['Config en cache', file_exists(base_path('bootstrap/cache/config.php'))],
            ['Routes en cache', file_exists(base_path('bootstrap/cache/routes-v7.php'))],
            ['Storage link existe', is_link(public_path('storage'))],
            ['Queue n\'est pas sync', config('queue.default') !== 'sync'],
            ['Mail n\'est pas log', config('mail.default') !== 'log'],
            ['Connexion DB fonctionne', $this->checkDatabase()],
            ['Extensions PHP requises', $this->checkExtensions()],
        ];

        foreach ($checks as [$label, $result]) {
            $total++;
            if ($result) {
                $this->line("  <fg=green>✓</> {$label}");
                $passed++;
            } else {
                $this->line("  <fg=red>✗</> {$label}");
            }
        }

        $this->line('');
        $color = $passed >= 7 ? 'green' : 'red';
        $this->line("<fg={$color}>Score : {$passed}/{$total}</>");

        return $passed >= 7 ? self::SUCCESS : self::FAILURE;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::select('SELECT 1');

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function checkExtensions(): bool
    {
        $required = ['mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'curl'];

        return collect($required)->every(fn (string $ext) => extension_loaded($ext));
    }
}
