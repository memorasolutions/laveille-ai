<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CheckCommand extends Command
{
    protected $signature = 'app:check {--quick : Skip PHPStan and tests}';

    protected $description = 'Run pre-deployment checks to validate project health';

    /** @var list<string> */
    private array $failedChecks = [];

    public function handle(): int
    {
        $this->components->info('Running pre-deployment checks...');
        $this->newLine();

        $checks = [
            'Environment' => fn () => $this->checkEnvironment(),
            'Database' => fn () => $this->checkDatabase(),
            'PHPStan' => fn () => $this->checkPhpStan(),
            'Tests' => fn () => $this->checkTests(),
            'Security' => fn () => $this->checkSecurity(),
            'Config' => fn () => $this->checkConfig(),
            'Storage symlink' => fn () => $this->checkStorageSymlink(),
        ];

        if ($this->option('quick')) {
            unset($checks['PHPStan'], $checks['Tests']);
        }

        foreach ($checks as $name => $check) {
            $this->components->task($name, $check);
        }

        $this->newLine();

        if (empty($this->failedChecks)) {
            $this->components->info('All checks passed successfully.');

            return self::SUCCESS;
        }

        $this->components->error('Some checks failed:');
        foreach ($this->failedChecks as $reason) {
            $this->components->twoColumnDetail("  {$reason}", '<fg=red>FAIL</>');
        }

        return self::FAILURE;
    }

    private function checkEnvironment(): bool
    {
        if (! file_exists(base_path('.env'))) {
            $this->failedChecks[] = '.env file missing';

            return false;
        }

        if (empty(config('app.key'))) {
            $this->failedChecks[] = 'APP_KEY not set';

            return false;
        }

        if (config('app.env') === 'production' && config('app.debug') !== false) {
            $this->failedChecks[] = 'APP_DEBUG should be false in production';

            return false;
        }

        return true;
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
        } catch (\Exception) {
            $this->failedChecks[] = 'Database connection failed';

            return false;
        }

        try {
            $applied = DB::table('migrations')->count();
            $files = count(glob(database_path('migrations/*.php')) ?: []);

            if ($files > $applied) {
                $this->failedChecks[] = 'Pending migrations ('.($files - $applied).' unapplied)';

                return false;
            }
        } catch (\Exception) {
            $this->failedChecks[] = 'Migration check failed';

            return false;
        }

        return true;
    }

    private function checkPhpStan(): bool
    {
        $process = new Process(['vendor/bin/phpstan', 'analyse', '--no-progress', '--error-format=raw']);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300);

        try {
            $process->mustRun();

            return true;
        } catch (ProcessFailedException) {
            $this->failedChecks[] = 'PHPStan analysis errors';

            return false;
        }
    }

    private function checkTests(): bool
    {
        $process = new Process(['vendor/bin/pest', '--parallel', '--no-coverage']);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(300);

        try {
            $process->mustRun();

            return true;
        } catch (ProcessFailedException) {
            $this->failedChecks[] = 'Test suite failed';

            return false;
        }
    }

    private function checkSecurity(): bool
    {
        $process = new Process(['composer', 'audit']);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(60);

        try {
            $process->mustRun();

            return true;
        } catch (ProcessFailedException) {
            $this->failedChecks[] = 'Composer security audit found vulnerabilities';

            return false;
        }
    }

    private function checkConfig(): bool
    {
        try {
            $name = config('app.name');

            if (empty($name)) {
                $this->failedChecks[] = 'app.name is empty';

                return false;
            }

            return true;
        } catch (\Exception) {
            $this->failedChecks[] = 'Config check failed';

            return false;
        }
    }

    private function checkStorageSymlink(): bool
    {
        if (! is_link(public_path('storage'))) {
            $this->failedChecks[] = 'Storage symlink not created (run php artisan storage:link)';

            return false;
        }

        return true;
    }
}
