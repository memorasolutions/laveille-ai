<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StatusCommand extends Command
{
    protected $signature = 'app:status';

    protected $description = 'Display system status dashboard';

    public function handle(): int
    {
        $this->components->info('System Status Dashboard');
        $this->newLine();

        $this->components->twoColumnDetail('Application', config('app.name').' ['.config('app.env').']');
        $this->components->twoColumnDetail('Debug Mode', config('app.debug') ? '<fg=yellow>Enabled</>' : '<fg=green>Disabled</>');
        $this->components->twoColumnDetail('URL', config('app.url'));

        $this->newLine();

        $this->components->twoColumnDetail('PHP Version', '<fg=green>✓</> '.PHP_VERSION);
        $this->components->twoColumnDetail('Laravel Version', '<fg=green>✓</> '.app()->version());

        $this->newLine();

        $this->components->twoColumnDetail('Database', $this->checkDatabase());
        $this->components->twoColumnDetail('Cache', $this->checkCache());
        $this->components->twoColumnDetail('Queue', $this->checkQueue());
        $this->components->twoColumnDetail('Storage Symlink', $this->checkStorage());
        $this->components->twoColumnDetail('Modules', $this->checkModules());

        $this->newLine();

        $this->components->twoColumnDetail('Users', $this->safeCount('users'));
        $this->components->twoColumnDetail('Articles', $this->safeCount('articles'));

        $this->newLine();

        return self::SUCCESS;
    }

    private function checkDatabase(): string
    {
        try {
            DB::select('SELECT 1');

            return '<fg=green>✓ Connected</>';
        } catch (\Exception) {
            return '<fg=red>✗ Not connected</>';
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::put('_status_check', true, 10);

            return Cache::get('_status_check') === true
                ? '<fg=green>✓ Working</>'
                : '<fg=red>✗ Not working</>';
        } catch (\Exception) {
            return '<fg=red>✗ Not working</>';
        }
    }

    private function checkQueue(): string
    {
        $driver = config('queue.default');

        try {
            $count = $driver === 'database' ? DB::table('jobs')->count() : 0;

            return "<fg=green>✓</> {$driver}".($count > 0 ? " ({$count} pending)" : '');
        } catch (\Exception) {
            return "<fg=red>✗</> {$driver}";
        }
    }

    private function checkStorage(): string
    {
        return is_link(public_path('storage'))
            ? '<fg=green>✓ Created</>'
            : '<fg=red>✗ Not created</>';
    }

    private function checkModules(): string
    {
        $path = base_path('modules_statuses.json');

        if (! File::exists($path)) {
            return '<fg=yellow>No modules file</>';
        }

        $modules = json_decode(File::get($path), true);
        $enabled = is_array($modules) ? count(array_filter($modules)) : 0;

        return "<fg=green>✓</> {$enabled} enabled";
    }

    private function safeCount(string $table): string
    {
        try {
            return (string) DB::table($table)->count();
        } catch (\Exception) {
            return '<fg=red>N/A</>';
        }
    }
}
