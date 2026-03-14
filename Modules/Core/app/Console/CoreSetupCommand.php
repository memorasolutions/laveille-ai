<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Core\Console;

use Illuminate\Console\Command;

class CoreSetupCommand extends Command
{
    protected $signature = 'core:setup {--fresh : Run fresh migrations (WARNING: drops all tables)}';

    protected $description = 'Set up the Laravel Core application (migrations, seeders, caches)';

    public function handle(): int
    {
        $this->info('Laravel Core - Setup');
        $this->newLine();

        // 1. Migrations
        if ($this->option('fresh')) {
            $this->warn('Running fresh migrations (all tables will be dropped)...');
            if (! $this->confirm('Are you sure?')) {
                $this->info('Aborted.');

                return self::FAILURE;
            }
            $this->call('migrate:fresh');
        } else {
            $this->info('Running migrations...');
            $this->call('migrate');
        }
        $this->newLine();

        // 2. Seed
        $this->info('Seeding database...');
        $this->call('db:seed');
        $this->newLine();

        // 3. Clear caches
        $this->info('Clearing caches...');
        $this->call('config:clear');
        $this->call('cache:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        $this->newLine();

        // 4. Storage link
        if (! file_exists(public_path('storage'))) {
            $this->info('Creating storage link...');
            $this->call('storage:link');
            $this->newLine();
        }

        $this->info('Setup complete!');
        $this->newLine();
        $this->table(
            ['Item', 'Value'],
            [
                ['Admin URL', url('/admin')],
                ['Admin email', config('app.admin_email')],
                ['Admin password', '(défini dans .env → ADMIN_PASSWORD)'],
            ]
        );

        return self::SUCCESS;
    }
}
