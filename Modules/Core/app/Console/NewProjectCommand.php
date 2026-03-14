<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature;
use Nwidart\Modules\Facades\Module;

class NewProjectCommand extends Command
{
    protected $signature = 'core:new-project';

    protected $description = 'Configure this CORE template for a new project';

    /** @var list<string> */
    private const CORE_MODULES = [
        'Auth', 'Core', 'Settings', 'RolesPermissions', 'Backoffice',
        'Notifications', 'Pages', 'Menu', 'Editor',
        'Health', 'Logging', 'SEO', 'Storage', 'Media',
        'Search', 'Translation', 'Export', 'Webhooks', 'Backup',
    ];

    /** @var array<string, bool> */
    private const BUSINESS_MODULES = [
        'blog' => true,
        'newsletter' => true,
        'faq' => true,
        'testimonials' => true,
        'widget' => true,
        'formbuilder' => true,
        'customfields' => true,
        'shorturl' => true,
    ];

    /** @var array<string, string> */
    private const ALIAS_TO_MODULE = [
        'blog' => 'Blog',
        'newsletter' => 'Newsletter',
        'faq' => 'Faq',
        'testimonials' => 'Testimonials',
        'widget' => 'Widget',
        'formbuilder' => 'FormBuilder',
        'customfields' => 'CustomFields',
        'shorturl' => 'ShortUrl',
        'ai' => 'AI',
        'team' => 'Team',
        'saas' => 'SaaS',
        'tenancy' => 'Tenancy',
        'abtest' => 'ABTest',
        'import' => 'Import',
        'api' => 'Api',
        'booking' => 'Booking',
        'roadmap' => 'Roadmap',
    ];

    /** @var array<string, bool> */
    private const ADVANCED_MODULES = [
        'ai' => false,
        'team' => false,
        'saas' => false,
        'tenancy' => false,
        'abtest' => false,
        'import' => false,
        'api' => false,
        'booking' => false,
        'roadmap' => false,
    ];

    public function handle(): int
    {
        $this->info('Laravel CORE - Configuration nouveau projet');
        $this->newLine();

        // 1. Configuration de base
        $appName = $this->ask('Nom de l\'application', 'My App');
        $appUrl = $this->ask('URL de l\'application', 'http://localhost');
        $dbName = $this->ask('Nom de la base de données', Str::snake(Str::lower($appName)));

        $this->newLine();
        $this->info('Configuration du .env...');
        $this->updateEnv('APP_NAME', $appName);
        $this->updateEnv('APP_URL', $appUrl);
        $this->updateEnv('DB_DATABASE', $dbName);

        $this->info('Generation de la cle...');
        $this->call('key:generate');

        // 2. Modules core (toujours actifs)
        $this->newLine();
        $this->info('Modules CORE (toujours actifs) :');
        $this->table(
            ['Module', 'Statut'],
            array_map(fn (string $m) => [$m, 'Actif'], self::CORE_MODULES),
        );

        // 3. Modules business (choix individuel, defaut = actif)
        $this->newLine();
        $this->info('Modules BUSINESS (desactiver individuellement) :');
        $selectedBusiness = $this->selectModules(self::BUSINESS_MODULES);

        // 4. Modules avances (choix individuel, defaut = inactif)
        $this->newLine();
        $this->info('Modules AVANCES (activer individuellement) :');
        $selectedAdvanced = $this->selectModules(self::ADVANCED_MODULES);

        // 5. Appliquer les feature flags
        $this->newLine();
        $this->info('Application des feature flags...');
        $allModules = array_merge(self::BUSINESS_MODULES, self::ADVANCED_MODULES);
        $allSelected = array_merge($selectedBusiness, $selectedAdvanced);

        foreach (array_keys($allModules) as $module) {
            $flag = 'module-'.$module;
            if (in_array($module, $allSelected, true)) {
                Feature::activateForEveryone($flag);
                $this->line("  + {$flag} active");
            } else {
                Feature::deactivateForEveryone($flag);
                $this->line("  - {$flag} desactive");
            }
        }

        // 6. Activer/desactiver les modules nwidart
        $this->newLine();
        $this->info('Activation/desactivation des modules nwidart...');
        $this->toggleNwidartModules($allSelected);

        // 7. Recapitulatif final
        $this->newLine();
        $this->info('Recapitulatif :');

        $rows = [];
        foreach (self::CORE_MODULES as $m) {
            $rows[] = ['Core', $m, 'Actif'];
        }
        foreach (array_keys(self::BUSINESS_MODULES) as $m) {
            $rows[] = ['Business', $m, in_array($m, $selectedBusiness, true) ? 'Actif' : 'Inactif'];
        }
        foreach (array_keys(self::ADVANCED_MODULES) as $m) {
            $rows[] = ['Avance', $m, in_array($m, $selectedAdvanced, true) ? 'Actif' : 'Inactif'];
        }

        $this->table(['Categorie', 'Module', 'Statut'], $rows);

        $this->table(['Configuration', 'Valeur'], [
            ['Nom', $appName],
            ['URL', $appUrl],
            ['Base de donnees', $dbName],
            ['Modules business', implode(', ', $selectedBusiness) ?: 'aucun'],
            ['Modules avances', implode(', ', $selectedAdvanced) ?: 'aucun'],
        ]);

        $this->newLine();
        $this->info('Prochaine etape : php artisan core:setup');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, bool>  $modules
     * @return list<string>
     */
    private function selectModules(array $modules): array
    {
        $selected = [];

        foreach ($modules as $name => $default) {
            if ($this->confirm("  Activer le module {$name} ?", $default)) {
                $selected[] = $name;
            }
        }

        return $selected;
    }

    /**
     * @param  list<string>  $allSelected
     */
    private function toggleNwidartModules(array $allSelected): void
    {
        foreach (self::ALIAS_TO_MODULE as $alias => $moduleName) {
            if (in_array($alias, $allSelected, true)) {
                Module::enable($moduleName);
                $this->line("  + {$moduleName} active");
            } else {
                Module::disable($moduleName);
                $this->line("  - {$moduleName} desactive");
            }
        }
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
