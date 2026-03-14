<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use DateTime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogsCommand extends Command
{
    protected $signature = 'app:logs
                            {--lines=50 : Number of lines to show initially}
                            {--level= : Filter by log level (emergency,alert,critical,error,warning,notice,info,debug)}
                            {--clear : Clear the log file before tailing}';

    protected $description = 'Tail the Laravel log file with color-coded output and filtering';

    private string $logFile;

    private bool $shouldStop = false;

    /** @var array<string, string> */
    private array $colors = [
        'emergency' => 'red',
        'alert' => 'red',
        'critical' => 'red',
        'error' => 'red',
        'warning' => 'yellow',
        'notice' => 'green',
        'info' => 'green',
        'debug' => 'gray',
    ];

    public function handle(): int
    {
        $this->logFile = storage_path('logs/laravel.log');

        if (! file_exists($this->logFile)) {
            $this->components->error("Log file not found: {$this->logFile}");

            return self::FAILURE;
        }

        if ($this->option('clear')) {
            $this->clearLogFile();
        }

        $this->registerSignalHandler();

        $initialLines = $this->getInitialLines();
        foreach ($initialLines as $line) {
            $this->outputLine($line);
        }

        $this->components->info('Tailing logs... (Ctrl+C to stop)');
        $this->tailLogFile();

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function getInitialLines(): array
    {
        $count = (int) $this->option('lines');
        if ($count <= 0) {
            return [];
        }

        $content = File::get($this->logFile);
        $allLines = array_filter(explode("\n", $content), fn (string $l) => trim($l) !== '');

        $lastLines = array_slice($allLines, -$count);

        return $this->filterByLevel($lastLines);
    }

    /**
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function filterByLevel(array $lines): array
    {
        $level = $this->option('level');
        if (! $level) {
            return $lines;
        }

        $level = strtolower((string) $level);

        return array_values(array_filter(
            $lines,
            fn (string $line) => stripos($line, ".{$level}:") !== false
        ));
    }

    private function matchesLevel(string $line): bool
    {
        $level = $this->option('level');
        if (! $level) {
            return true;
        }

        return stripos($line, '.'.strtolower((string) $level).':') !== false;
    }

    private function outputLine(string $line): void
    {
        if (trim($line) === '') {
            return;
        }

        $formatted = $this->formatTimestamp($line);
        $color = $this->getLineColor($line);

        if ($color) {
            $this->line("<fg={$color}>{$formatted}</>");
        } else {
            $this->line($formatted);
        }
    }

    private function getLineColor(string $line): ?string
    {
        foreach ($this->colors as $level => $color) {
            if (stripos($line, ".{$level}:") !== false) {
                return $color;
            }
        }

        return null;
    }

    private function formatTimestamp(string $line): string
    {
        $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/';

        if (preg_match($pattern, $line, $matches)) {
            $logTime = DateTime::createFromFormat('Y-m-d H:i:s', $matches[1]);
            if (! $logTime) {
                return $line;
            }

            $interval = (new DateTime)->diff($logTime);

            $relative = match (true) {
                $interval->days > 0 => $interval->days.'d ago',
                $interval->h > 0 => $interval->h.'h ago',
                $interval->i > 0 => $interval->i.'m ago',
                default => $interval->s.'s ago',
            };

            return (string) preg_replace($pattern, "[{$relative}]", $line);
        }

        return $line;
    }

    private function tailLogFile(): void
    {
        $handle = fopen($this->logFile, 'r');
        if (! $handle) {
            return;
        }

        fseek($handle, 0, SEEK_END);

        while (! $this->shouldStop) {
            $content = fread($handle, 8192);

            if ($content !== false && $content !== '') {
                $lines = explode("\n", $content);

                foreach ($lines as $line) {
                    if (trim($line) === '' || ! $this->matchesLevel($line)) {
                        continue;
                    }
                    $this->outputLine($line);
                }
            }

            usleep(200000);

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }
        }

        fclose($handle);
    }

    private function registerSignalHandler(): void
    {
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGINT, function () {
                $this->shouldStop = true;
                $this->newLine();
                $this->components->info('Stopped.');
            });

            pcntl_signal(SIGTERM, function () {
                $this->shouldStop = true;
            });
        }
    }

    private function clearLogFile(): void
    {
        File::put($this->logFile, '');
        $this->components->info('Log file cleared.');
    }
}
