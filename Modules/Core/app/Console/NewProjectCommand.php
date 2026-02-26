<?php

declare(strict_types=1);

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;

class NewProjectCommand extends Command
{
    protected $signature = 'core:new-project';

    protected $description = 'Configure this CORE template for a new project';

    public function handle(): int
    {
        $this->info('Laravel CORE - Configuration nouveau projet');
        $this->newLine();

        $appName = $this->ask('Nom de l\'application', 'My App');
        $appUrl = $this->ask('URL de l\'application', 'http://localhost');
        $dbName = $this->ask('Nom de la base de données', Str::snake(Str::lower($appName)));

        $optionalModules = ['saas', 'tenancy', 'sms'];
        $selectedModules = [];

        foreach ($optionalModules as $module) {
            if ($this->confirm("Activer le module {$module} ?", false)) {
                $selectedModules[] = $module;
            }
        }

        $this->newLine();
        $this->info('Configuration du .env...');
        $this->updateEnv('APP_NAME', $appName);
        $this->updateEnv('APP_URL', $appUrl);
        $this->updateEnv('DB_DATABASE', $dbName);

        $this->info('Génération de la clé...');
        $this->call('key:generate');

        if (! empty($selectedModules)) {
            $this->info('Activation des feature flags...');
            foreach ($selectedModules as $module) {
                Feature::activateForEveryone('module-'.$module);
                $this->line("  - module-{$module} activé");
            }
        }

        $this->newLine();
        $this->table(['Configuration', 'Valeur'], [
            ['Nom', $appName],
            ['URL', $appUrl],
            ['Base de données', $dbName],
            ['Modules activés', ! empty($selectedModules) ? implode(', ', $selectedModules) : 'aucun'],
        ]);

        $this->newLine();
        $this->info('Prochaine étape : php artisan core:setup');

        return self::SUCCESS;
    }

    private function updateEnv(string $key, string $value): void
    {
        $path = base_path('.env');

        if (! file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);
        $escaped = preg_quote($key, '/');
        $pattern = "/^{$escaped}=.*$/m";

        $envValue = Str::contains($value, ' ') ? "\"{$value}\"" : $value;
        $replacement = "{$key}={$envValue}";

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            $content .= "\n{$replacement}";
        }

        file_put_contents($path, $content);
    }
}
