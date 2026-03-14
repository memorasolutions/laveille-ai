<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupHooksCommand extends Command
{
    protected $signature = 'app:setup-hooks {--force : Overwrite existing hook without confirmation}';

    protected $description = 'Install git pre-commit hook for code quality checks';

    public function handle(): int
    {
        $source = base_path('scripts/pre-commit');
        $destination = base_path('.git/hooks/pre-commit');

        if (! is_dir(dirname($destination))) {
            $this->components->error('.git/hooks directory does not exist. Is this a git repository?');

            return self::FAILURE;
        }

        if (! file_exists($source)) {
            $this->components->error('scripts/pre-commit not found.');

            return self::FAILURE;
        }

        if (file_exists($destination) && ! $this->option('force')) {
            if (! $this->confirm('Pre-commit hook already exists. Overwrite?', false)) {
                $this->components->info('Operation cancelled.');

                return self::SUCCESS;
            }
        }

        if (! copy($source, $destination)) {
            $this->components->error('Failed to install hook.');

            return self::FAILURE;
        }

        chmod($destination, 0755);

        $this->components->info('Pre-commit hook installed successfully!');
        $this->components->bulletList([
            'Runs Laravel Pint on staged PHP files',
            'Runs PHPStan on staged PHP files',
            'Skips if no PHP files are staged',
            'Use git commit --no-verify to bypass',
        ]);

        return self::SUCCESS;
    }
}
