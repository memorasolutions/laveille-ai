<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CorePruneCommand extends Command
{
    protected $signature = 'core:prune {preset?} {--interactive}';

    protected $description = 'Apply a module preset to modules_statuses.json and update environment variables';

    private const array FOUNDATION_MODULES = [
        'Core',
        'Auth',
        'Backoffice',
        'Settings',
        'RolesPermissions',
        'Notifications',
        'Logging',
        'Health',
        'Media',
        'Editor',
        'Privacy',
        'Storage',
        'Backup',
    ];

    public function handle(): int
    {
        $presetName = $this->resolvePresetName();

        if (! $presetName) {
            return Command::FAILURE;
        }

        $presetConfig = config("presets.{$presetName}");

        if (! $presetConfig) {
            $this->error("Preset '{$presetName}' not found in config/presets.php.");

            return Command::FAILURE;
        }

        $this->info("Preset: {$presetName} — {$presetConfig['description']}");
        $this->newLine();

        $currentStatuses = $this->loadStatuses();
        $newStatuses = $this->calculateNewStatuses($currentStatuses, $presetConfig);
        $changes = $this->buildChangesTable($currentStatuses, $newStatuses);

        $this->table(['Module', 'Status', 'Changed'], $changes);

        $disabledCount = count(array_filter($newStatuses, fn ($v) => ! $v));
        $enabledCount = count(array_filter($newStatuses, fn ($v) => $v));
        $this->info("{$enabledCount} enabled, {$disabledCount} disabled.");

        if (! empty($presetConfig['env_overrides'])) {
            $this->newLine();
            $this->info('.env overrides:');
            foreach ($presetConfig['env_overrides'] as $key => $value) {
                $this->line("  {$key}={$value}");
            }
        }

        $this->newLine();

        if (! $this->confirm('Apply these changes?')) {
            $this->info('Cancelled.');

            return Command::SUCCESS;
        }

        $this->writeStatuses($newStatuses);
        $this->applyEnvOverrides($presetConfig['env_overrides'] ?? []);

        $this->call('optimize:clear');

        $this->newLine();
        $this->info("Preset '{$presetName}' applied successfully.");

        return Command::SUCCESS;
    }

    private function resolvePresetName(): ?string
    {
        if ($this->option('interactive')) {
            $presets = array_keys(config('presets', []));
            if (empty($presets)) {
                $this->error('No presets defined in config/presets.php.');

                return null;
            }

            return $this->choice('Select a preset:', $presets);
        }

        $preset = $this->argument('preset');

        if (! $preset) {
            $this->error('Preset name required. Usage: core:prune {saas|blog|minimal} or --interactive');

            return null;
        }

        return $preset;
    }

    private function loadStatuses(): array
    {
        $path = base_path('modules_statuses.json');

        if (! File::exists($path)) {
            return [];
        }

        return json_decode(File::get($path), true) ?? [];
    }

    private function calculateNewStatuses(array $current, array $preset): array
    {
        $disabled = $preset['modules_disabled'] ?? [];
        $new = $current;

        foreach (array_keys($new) as $module) {
            if (in_array($module, self::FOUNDATION_MODULES, true)) {
                $new[$module] = true;
            } else {
                $new[$module] = ! in_array($module, $disabled, true);
            }
        }

        return $new;
    }

    private function buildChangesTable(array $old, array $new): array
    {
        $rows = [];
        $allModules = array_unique(array_merge(array_keys($old), array_keys($new)));
        sort($allModules);

        foreach ($allModules as $module) {
            $oldStatus = $old[$module] ?? false;
            $newStatus = $new[$module] ?? false;
            $changed = $oldStatus !== $newStatus;

            $rows[] = [
                $module,
                $newStatus ? '<fg=green>enabled</>' : '<fg=red>disabled</>',
                $changed ? '<fg=yellow>yes</>' : 'no',
            ];
        }

        return $rows;
    }

    private function writeStatuses(array $statuses): void
    {
        $path = base_path('modules_statuses.json');
        File::put($path, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL);
    }

    private function applyEnvOverrides(array $overrides): void
    {
        if (empty($overrides)) {
            return;
        }

        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            return;
        }

        $content = File::get($envPath);
        $lines = explode("\n", $content);
        $processed = [];

        foreach ($lines as $line) {
            $matched = false;
            foreach ($overrides as $key => $value) {
                if (str_starts_with(trim($line), $key.'=')) {
                    $lines[array_search($line, $lines)] = "{$key}={$value}";
                    $processed[$key] = true;
                    $matched = true;

                    break;
                }
            }
            if (! $matched) {
                continue;
            }
        }

        foreach ($overrides as $key => $value) {
            if (! isset($processed[$key])) {
                $lines[] = "{$key}={$value}";
            }
        }

        File::put($envPath, implode("\n", $lines));
    }
}
