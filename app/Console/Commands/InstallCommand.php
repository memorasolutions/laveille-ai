<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InstallCommand extends Command
{
    protected $signature = 'app:install {--force : Skip confirmations}';

    protected $description = 'Install and configure the application';

    public function handle(): int
    {
        $this->showWelcomeBanner();

        if (! $this->checkPhpVersion()) {
            return self::FAILURE;
        }

        if (! $this->confirmInstallation()) {
            $this->components->warn('Installation cancelled.');

            return self::FAILURE;
        }

        $config = $this->gatherConfiguration();

        if (! $this->validateDatabaseConnection($config)) {
            return self::FAILURE;
        }

        $this->updateEnvFile($config);
        $this->runArtisanCommands();
        $this->showSuccessTable($config);

        return self::SUCCESS;
    }

    private function showWelcomeBanner(): void
    {
        $this->components->info('========================================');
        $this->components->info('    Laravel SaaS Boilerplate Installer');
        $this->components->info('========================================');
    }

    private function checkPhpVersion(): bool
    {
        if (version_compare(PHP_VERSION, '8.4.0') < 0) {
            $this->components->error('PHP 8.4 or higher is required. Current version: '.PHP_VERSION);

            return false;
        }

        $this->components->info('PHP version check passed: '.PHP_VERSION);

        return true;
    }

    private function confirmInstallation(): bool
    {
        if ($this->option('force')) {
            return true;
        }

        return $this->confirm('This will configure your application. Continue?', true);
    }

    private function gatherConfiguration(): array
    {
        $config = [];

        $this->components->info('Application Configuration');
        $config['APP_NAME'] = $this->ask('Application name', config('app.name', 'Laravel'));
        $config['APP_URL'] = $this->ask('Application URL', config('app.url', 'http://localhost'));
        $config['APP_ENV'] = $this->choice('Environment', ['local', 'staging', 'production'], 'local');

        $this->components->info('Database Configuration');
        $config['DB_HOST'] = $this->ask('Database host', '127.0.0.1');
        $config['DB_PORT'] = $this->ask('Database port', '3306');
        $config['DB_DATABASE'] = $this->ask('Database name', 'laravel');
        $config['DB_USERNAME'] = $this->ask('Database username', 'root');
        $config['DB_PASSWORD'] = $this->secret('Database password (hidden)') ?? '';

        $this->components->info('Admin User Creation');
        $config['ADMIN_NAME'] = $this->ask('Admin name', 'Administrator');

        do {
            $config['ADMIN_EMAIL'] = $this->ask('Admin email');
            if (! filter_var($config['ADMIN_EMAIL'], FILTER_VALIDATE_EMAIL)) {
                $this->components->error('Invalid email format. Please try again.');
            }
        } while (! filter_var($config['ADMIN_EMAIL'], FILTER_VALIDATE_EMAIL));

        do {
            $config['ADMIN_PASSWORD'] = $this->secret('Admin password (min 8 characters)') ?? '';
            if (strlen($config['ADMIN_PASSWORD']) < 8) {
                $this->components->error('Password must be at least 8 characters.');
            }
        } while (strlen($config['ADMIN_PASSWORD']) < 8);

        if ($this->confirm('Configure Stripe for SaaS features?', false)) {
            $config['STRIPE_KEY'] = $this->ask('Stripe publishable key', '');
            $config['STRIPE_SECRET'] = $this->secret('Stripe secret key') ?? '';
        }

        return $config;
    }

    private function validateDatabaseConnection(array $config): bool
    {
        $this->components->info('Testing database connection...');

        try {
            config([
                'database.connections.install_test' => [
                    'driver' => 'mysql',
                    'host' => $config['DB_HOST'],
                    'port' => $config['DB_PORT'],
                    'database' => $config['DB_DATABASE'],
                    'username' => $config['DB_USERNAME'],
                    'password' => $config['DB_PASSWORD'],
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                ],
            ]);

            DB::connection('install_test')->getPdo();
            $this->components->info('Database connection successful');
            DB::purge('install_test');

            return true;
        } catch (\Exception $e) {
            $this->components->error('Database connection failed: '.$e->getMessage());

            return false;
        }
    }

    private function updateEnvFile(array $config): void
    {
        $this->components->info('Updating .env file...');

        $envPath = base_path('.env');

        if (! file_exists($envPath) && file_exists(base_path('.env.example'))) {
            copy(base_path('.env.example'), $envPath);
        }

        $envContent = file_get_contents($envPath);

        foreach ($config as $key => $value) {
            $escapedValue = str_replace('"', '\"', (string) $value);

            if (Str::contains($envContent, "{$key}=")) {
                $envContent = preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}=\"{$escapedValue}\"",
                    $envContent
                );
            } else {
                $envContent .= "\n{$key}=\"{$escapedValue}\"";
            }
        }

        file_put_contents($envPath, $envContent);
        $this->components->info('.env file updated');
    }

    private function runArtisanCommands(): void
    {
        $this->components->info('Running setup commands...');

        $commands = [
            ['key:generate', [], 'Generating application key'],
            ['migrate', ['--force' => true], 'Running database migrations'],
            ['db:seed', ['--force' => true], 'Seeding database'],
            ['storage:link', [], 'Creating storage link'],
            ['optimize:clear', [], 'Clearing all caches'],
        ];

        foreach ($commands as [$command, $args, $message]) {
            $this->components->task($message, function () use ($command, $args) {
                try {
                    Artisan::call($command, $args);

                    return true;
                } catch (\Exception) {
                    return false;
                }
            });
        }
    }

    private function showSuccessTable(array $config): void
    {
        $this->newLine();
        $this->components->info('Installation Complete!');

        $this->table(
            ['Setting', 'Value'],
            [
                ['Application', $config['APP_NAME']],
                ['URL', $config['APP_URL']],
                ['Environment', $config['APP_ENV']],
                ['Database', $config['DB_DATABASE']],
                ['Admin email', $config['ADMIN_EMAIL']],
                ['Admin panel', $config['APP_URL'].'/'.config('backoffice.admin_path', 'admin')],
                ['Stripe', isset($config['STRIPE_KEY']) && $config['STRIPE_KEY'] !== '' ? 'Configured' : 'Skipped'],
            ]
        );

        $this->components->bulletList([
            'Visit '.$config['APP_URL'].' in your browser',
            'Login with your admin credentials',
            'Configure mail settings in Settings > Mail',
        ]);
    }
}
