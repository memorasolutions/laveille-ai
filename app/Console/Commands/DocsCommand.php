<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DocsCommand extends Command
{
    protected $signature = 'app:docs
                            {--format=console : Output format (console or markdown)}
                            {--output= : File path for markdown output}';

    protected $description = 'Generate technical documentation for the project';

    public function handle(): int
    {
        $format = $this->option('format');
        $outputPath = $this->option('output');

        if ($format === 'markdown') {
            $markdown = $this->generateMarkdown();

            if ($outputPath) {
                File::put($outputPath, $markdown);
                $this->components->info("Documentation written to: {$outputPath}");
            } else {
                $this->output->write($markdown);
            }

            return self::SUCCESS;
        }

        return $this->generateConsoleOutput();
    }

    private function generateConsoleOutput(): int
    {
        $this->components->info('Project Documentation');
        $this->newLine();

        $this->sectionModules();
        $this->sectionRoutes();
        $this->sectionPermissions();
        $this->sectionCommands();
        $this->sectionConfig();

        $this->components->info('Documentation generated successfully.');

        return self::SUCCESS;
    }

    private function sectionModules(): void
    {
        $this->components->info('1. Modules');
        $modules = $this->getModules();
        $enabled = count(array_filter($modules, fn ($m) => $m['enabled']));

        $this->components->twoColumnDetail('Total', (string) count($modules));
        $this->components->twoColumnDetail('Enabled', "<fg=green>{$enabled}</>");
        $this->components->twoColumnDetail('Disabled', '<fg=yellow>'.(count($modules) - $enabled).'</>');
        $this->newLine();

        $rows = array_map(fn ($m) => [
            $m['name'],
            $m['enabled'] ? '<fg=green>Enabled</>' : '<fg=red>Disabled</>',
        ], $modules);

        $this->table(['Module', 'Status'], $rows);
        $this->newLine();
    }

    private function sectionRoutes(): void
    {
        $this->components->info('2. Routes');
        $groups = $this->getRouteGroups();
        $total = array_sum($groups);

        $this->components->twoColumnDetail('Total', "<fg=white>{$total}</>");
        $this->newLine();

        $rows = [];
        foreach ($groups as $prefix => $count) {
            $rows[] = [$prefix ?: '/', (string) $count];
        }

        $this->table(['Prefix', 'Count'], $rows);
        $this->newLine();
    }

    private function sectionPermissions(): void
    {
        $this->components->info('3. Permissions & Roles');

        try {
            $permissions = DB::table('permissions')->pluck('name');
            $roles = DB::table('roles')->pluck('name');

            $this->components->twoColumnDetail('Permissions', "<fg=white>{$permissions->count()}</>");
            $this->components->twoColumnDetail('Roles', "<fg=white>{$roles->count()}</>");
            $this->newLine();

            if ($roles->isNotEmpty()) {
                $this->table(['Roles'], $roles->map(fn ($r) => [$r])->toArray());
            }

            if ($permissions->isNotEmpty()) {
                $this->table(['Permissions'], $permissions->map(fn ($p) => [$p])->toArray());
            }
        } catch (\Exception) {
            $this->components->twoColumnDetail('Status', '<fg=red>Tables not found</>');
        }

        $this->newLine();
    }

    private function sectionCommands(): void
    {
        $this->components->info('4. Artisan Commands (app:*)');
        $commands = $this->getAppCommands();

        $this->components->twoColumnDetail('Total', '<fg=white>'.count($commands).'</>');
        $this->newLine();

        $rows = [];
        foreach ($commands as $name => $desc) {
            $rows[] = [$name, $desc];
        }

        $this->table(['Command', 'Description'], $rows);
        $this->newLine();
    }

    private function sectionConfig(): void
    {
        $this->components->info('5. Configuration');

        $configs = $this->getConfigSummary();

        foreach ($configs as $key => $value) {
            $this->components->twoColumnDetail($key, (string) $value);
        }

        $this->newLine();
    }

    private function generateMarkdown(): string
    {
        $md = "# Project Documentation\n\n";
        $md .= '*Generated on: '.now()->format('Y-m-d H:i:s')."*\n\n---\n\n";

        $modules = $this->getModules();
        $enabled = count(array_filter($modules, fn ($m) => $m['enabled']));
        $md .= "## 1. Modules\n\n";
        $md .= '- **Total:** '.count($modules)."\n";
        $md .= "- **Enabled:** {$enabled}\n";
        $md .= '- **Disabled:** '.(count($modules) - $enabled)."\n\n";
        $md .= "| Module | Status |\n|--------|--------|\n";
        foreach ($modules as $m) {
            $status = $m['enabled'] ? 'Enabled' : 'Disabled';
            $md .= "| {$m['name']} | {$status} |\n";
        }
        $md .= "\n";

        $groups = $this->getRouteGroups();
        $total = array_sum($groups);
        $md .= "## 2. Routes\n\n";
        $md .= "- **Total:** {$total}\n\n";
        $md .= "| Prefix | Count |\n|--------|-------|\n";
        foreach ($groups as $prefix => $count) {
            $label = $prefix ?: '/';
            $md .= "| {$label} | {$count} |\n";
        }
        $md .= "\n";

        $md .= "## 3. Permissions & Roles\n\n";
        try {
            $permissions = DB::table('permissions')->pluck('name');
            $roles = DB::table('roles')->pluck('name');
            $md .= "- **Permissions:** {$permissions->count()}\n";
            $md .= "- **Roles:** {$roles->count()}\n\n";

            if ($roles->isNotEmpty()) {
                $md .= "### Roles\n\n";
                foreach ($roles as $role) {
                    $md .= "- {$role}\n";
                }
                $md .= "\n";
            }

            if ($permissions->isNotEmpty()) {
                $md .= "### Permissions\n\n";
                $md .= "| Permission |\n|------------|\n";
                foreach ($permissions as $perm) {
                    $md .= "| {$perm} |\n";
                }
                $md .= "\n";
            }
        } catch (\Exception) {
            $md .= "*Database tables not available*\n\n";
        }

        $commands = $this->getAppCommands();
        $md .= "## 4. Artisan Commands (app:*)\n\n";
        $md .= "| Command | Description |\n|---------|-------------|\n";
        foreach ($commands as $name => $desc) {
            $md .= "| `{$name}` | {$desc} |\n";
        }
        $md .= "\n";

        $configs = $this->getConfigSummary();
        $md .= "## 5. Configuration\n\n";
        foreach ($configs as $key => $value) {
            $md .= "- **{$key}:** {$value}\n";
        }

        return $md;
    }

    /** @return array<int, array{name: string, enabled: bool}> */
    private function getModules(): array
    {
        $path = base_path('modules_statuses.json');

        if (! File::exists($path)) {
            return [];
        }

        $data = json_decode(File::get($path), true);

        if (! is_array($data)) {
            return [];
        }

        $modules = [];
        foreach ($data as $name => $enabled) {
            $modules[] = ['name' => $name, 'enabled' => (bool) $enabled];
        }

        return $modules;
    }

    /** @return array<string, int> */
    private function getRouteGroups(): array
    {
        try {
            Artisan::call('route:list', ['--json' => true]);
            $routes = json_decode(Artisan::output(), true);
        } catch (\Exception) {
            return [];
        }

        if (! is_array($routes)) {
            return [];
        }

        $groups = [];
        foreach ($routes as $route) {
            $uri = $route['uri'] ?? '';
            $parts = explode('/', ltrim($uri, '/'));
            $prefix = $parts[0] ?? '';

            if (! isset($groups[$prefix])) {
                $groups[$prefix] = 0;
            }
            $groups[$prefix]++;
        }

        arsort($groups);

        return $groups;
    }

    /** @return array<string, string> */
    private function getAppCommands(): array
    {
        $commands = [];

        foreach (Artisan::all() as $command) {
            $name = $command->getName();
            if (str_starts_with($name, 'app:')) {
                $commands[$name] = $command->getDescription();
            }
        }

        ksort($commands);

        return $commands;
    }

    /** @return array<string, string|int> */
    private function getConfigSummary(): array
    {
        return [
            'App Name' => Config::get('app.name', 'Not set'),
            'Environment' => Config::get('app.env', 'Not set'),
            'URL' => Config::get('app.url', 'Not set'),
            'Database' => Config::get('database.default', 'Not set'),
            'Cache' => Config::get('cache.default', 'Not set'),
            'Queue' => Config::get('queue.default', 'Not set'),
            'Mail' => Config::get('mail.default', 'Not set'),
            'Modules' => count($this->getModules()),
        ];
    }
}
