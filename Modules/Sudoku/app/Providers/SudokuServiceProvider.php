<?php

declare(strict_types=1);

namespace Modules\Sudoku\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SudokuServiceProvider extends ServiceProvider
{
    protected string $name = 'Sudoku';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Sudoku', 'database/migrations'));
        $this->loadViewsFrom(module_path('Sudoku', 'resources/views'), 'sudoku');

        // Routes web (auth optionnelle, public par défaut)
        Route::middleware('web')->group(module_path('Sudoku', 'routes/web.php'));

        // API REST sans préfixe global (déjà dans routes/api.php du module)
        Route::middleware('api')->prefix('api')->group(module_path('Sudoku', 'routes/api.php'));

        // Schedule daily puzzle generation 00:01 America/Toronto
        $this->app->booted(function () {
            $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
            if (class_exists(\Modules\Sudoku\Console\GenerateDailyPuzzleCommand::class)) {
                $schedule->command('sudoku:generate-daily')
                    ->dailyAt('00:01')
                    ->timezone('America/Toronto')
                    ->withoutOverlapping();
            }
        });

        // Register console command
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Sudoku\Console\GenerateDailyPuzzleCommand::class,
            ]);
        }
    }

    public function register(): void
    {
        if (file_exists($configPath = module_path('Sudoku', 'config/config.php'))) {
            $this->mergeConfigFrom($configPath, 'sudoku');
        }
    }
}
