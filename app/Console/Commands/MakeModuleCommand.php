<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'app:make-module {name : The module name in PascalCase}';

    protected $description = 'Create a new module scaffold with all required files';

    private int $fileCount = 0;

    public function handle(): int
    {
        $name = Str::studly(trim($this->argument('name')));
        $lower = Str::lower($name);
        $modulesPath = config('modules.paths.modules', base_path('Modules'));
        $path = "{$modulesPath}/{$name}";

        if (File::exists($path)) {
            $this->components->error("Module {$name} already exists!");

            return self::FAILURE;
        }

        $this->components->info("Creating module {$name}...");

        $dirs = [
            'app/Http/Controllers', 'app/Models', 'app/Providers',
            'config', 'database/migrations', 'database/seeders',
            'database/factories', 'resources/views', 'routes',
            'tests/Feature', 'tests/Unit',
        ];

        foreach ($dirs as $dir) {
            File::makeDirectory("{$path}/{$dir}", 0755, true, true);
        }

        $this->createServiceProvider($path, $name, $lower);
        $this->createRouteServiceProvider($path, $name);
        $this->createEventServiceProvider($path, $name);
        $this->createConfigFile($path, $name);
        $this->createDatabaseSeeder($path, $name);
        $this->createWebRoutes($path);
        $this->createApiRoutes($path);
        $this->createPestTest($path, $name);
        $this->createModuleJson($path, $name, $lower);
        $this->createPluginJson($path, $name);
        $this->createComposerJson($path, $name);
        $this->createGitkeepFiles($path);
        $this->updateModulesStatuses($name);

        $this->newLine();
        $this->components->info("Module {$name} created successfully! ({$this->fileCount} files)");
        $this->components->bulletList([
            "Location: Modules/{$name}/",
            "Namespace: Modules\\{$name}\\",
            'Run: composer dump-autoload',
        ]);

        return self::SUCCESS;
    }

    private function writeFile(string $path, string $content): void
    {
        File::put($path, $content);
        $this->fileCount++;
    }

    private function createServiceProvider(string $modulePath, string $name, string $lower): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Modules\\{$name}\\Providers;

            use Illuminate\Support\ServiceProvider;
            use Nwidart\Modules\Traits\PathNamespace;

            class {$name}ServiceProvider extends ServiceProvider
            {
                use PathNamespace;

                protected string \$name = '{$name}';

                protected string \$nameLower = '{$lower}';

                public function boot(): void
                {
                    \$this->registerConfig();
                    \$this->registerViews();
                    \$this->loadMigrationsFrom(module_path(\$this->name, 'database/migrations'));
                }

                public function register(): void
                {
                    \$this->app->register(RouteServiceProvider::class);
                    \$this->app->register(EventServiceProvider::class);
                }

                private function registerConfig(): void
                {
                    \$path = module_path(\$this->name, 'config/config.php');
                    \$this->mergeConfigFrom(\$path, "modules.{\$this->nameLower}");
                }

                private function registerViews(): void
                {
                    \$sourcePath = module_path(\$this->name, 'resources/views');
                    \$this->loadViewsFrom(\$sourcePath, \$this->nameLower);
                }
            }

            PHP;

        $this->writeFile("{$modulePath}/app/Providers/{$name}ServiceProvider.php", $this->dedentHeredoc($content));
    }

    private function createRouteServiceProvider(string $modulePath, string $name): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Modules\\{$name}\\Providers;

            use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
            use Illuminate\Support\Facades\Route;

            class RouteServiceProvider extends ServiceProvider
            {
                protected string \$name = '{$name}';

                public function map(): void
                {
                    \$this->mapWebRoutes();
                    \$this->mapApiRoutes();
                }

                protected function mapWebRoutes(): void
                {
                    Route::middleware('web')
                        ->group(module_path(\$this->name, 'routes/web.php'));
                }

                protected function mapApiRoutes(): void
                {
                    Route::middleware('api')
                        ->prefix('api')
                        ->group(module_path(\$this->name, 'routes/api.php'));
                }
            }

            PHP;

        $this->writeFile("{$modulePath}/app/Providers/RouteServiceProvider.php", $this->dedentHeredoc($content));
    }

    private function createEventServiceProvider(string $modulePath, string $name): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Modules\\{$name}\\Providers;

            use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

            class EventServiceProvider extends ServiceProvider
            {
                /** @var array<class-string, list<class-string>> */
                protected \$listen = [];
            }

            PHP;

        $this->writeFile("{$modulePath}/app/Providers/EventServiceProvider.php", $this->dedentHeredoc($content));
    }

    private function createConfigFile(string $modulePath, string $name): void
    {
        $content = <<<'PHP'
            <?php

            return [
                'name' => '%%%NAME%%%',
            ];

            PHP;

        $this->writeFile("{$modulePath}/config/config.php", str_replace('%%%NAME%%%', $name, $this->dedentHeredoc($content)));
    }

    private function createDatabaseSeeder(string $modulePath, string $name): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            namespace Modules\\{$name}\\Database\\Seeders;

            use Illuminate\Database\Seeder;

            class {$name}DatabaseSeeder extends Seeder
            {
                public function run(): void
                {
                    //
                }
            }

            PHP;

        $this->writeFile("{$modulePath}/database/seeders/{$name}DatabaseSeeder.php", $this->dedentHeredoc($content));
    }

    private function createWebRoutes(string $modulePath): void
    {
        $content = <<<'PHP'
            <?php

            use Illuminate\Support\Facades\Route;

            // Define your web routes here

            PHP;

        $this->writeFile("{$modulePath}/routes/web.php", $this->dedentHeredoc($content));
    }

    private function createApiRoutes(string $modulePath): void
    {
        $content = <<<'PHP'
            <?php

            use Illuminate\Support\Facades\Route;

            // Define your API routes here

            PHP;

        $this->writeFile("{$modulePath}/routes/api.php", $this->dedentHeredoc($content));
    }

    private function createPestTest(string $modulePath, string $name): void
    {
        $content = <<<PHP
            <?php

            declare(strict_types=1);

            test('{$name} module is loaded', function () {
                expect(array_key_exists('{$name}', app('modules')->allEnabled()))
                    ->toBeTrue();
            });

            PHP;

        $this->writeFile("{$modulePath}/tests/Feature/{$name}Test.php", $this->dedentHeredoc($content));
    }

    private function createModuleJson(string $modulePath, string $name, string $lower): void
    {
        $data = [
            'name' => $name,
            'alias' => $lower,
            'description' => '',
            'keywords' => [],
            'priority' => 0,
            'providers' => ["Modules\\{$name}\\Providers\\{$name}ServiceProvider"],
            'files' => [],
        ];

        $this->writeFile("{$modulePath}/module.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    private function createPluginJson(string $modulePath, string $name): void
    {
        $data = [
            'name' => $name,
            'description' => "Module {$name}",
            'version' => '1.0.0',
            'type' => 'plugin',
            'dependencies' => ['Core'],
            'priority' => 10,
        ];

        $this->writeFile("{$modulePath}/plugin.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    private function createComposerJson(string $modulePath, string $name): void
    {
        $data = [
            'name' => 'modules/' . Str::lower($name),
            'autoload' => [
                'psr-4' => [
                    "Modules\\{$name}\\" => 'app/',
                    "Modules\\{$name}\\Database\\" => 'database/',
                    "Modules\\{$name}\\Tests\\" => 'tests/',
                ],
            ],
        ];

        $this->writeFile("{$modulePath}/composer.json", json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    private function createGitkeepFiles(string $modulePath): void
    {
        $dirs = [
            'app/Http/Controllers', 'app/Models',
            'database/migrations', 'database/factories',
            'resources/views',
        ];

        foreach ($dirs as $dir) {
            $this->writeFile("{$modulePath}/{$dir}/.gitkeep", '');
        }
    }

    private function updateModulesStatuses(string $name): void
    {
        $statusesPath = config('modules.activators.file.statuses-file', base_path('modules_statuses.json'));
        $statuses = [];

        if (File::exists($statusesPath)) {
            $statuses = json_decode(File::get($statusesPath), true) ?? [];
        }

        $statuses[$name] = true;
        ksort($statuses);

        File::put($statusesPath, json_encode($statuses, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    private function dedentHeredoc(string $content): string
    {
        $lines = explode("\n", $content);
        $minIndent = PHP_INT_MAX;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $indent = strlen($line) - strlen(ltrim($line));
            $minIndent = min($minIndent, $indent);
        }

        if ($minIndent === PHP_INT_MAX || $minIndent === 0) {
            return $content;
        }

        return implode("\n", array_map(
            fn (string $line) => trim($line) === '' ? '' : substr($line, $minIndent),
            $lines
        ));
    }
}
