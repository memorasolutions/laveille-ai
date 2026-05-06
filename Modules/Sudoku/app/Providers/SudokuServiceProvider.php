<?php

declare(strict_types=1);

namespace Modules\Sudoku\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SudokuServiceProvider extends ServiceProvider
{
    protected string $name = 'Sudoku';

    public function boot(): void
    {
        $this->loadMigrationsFrom(module_path('Sudoku', 'database/migrations'));
        $this->loadViewsFrom(module_path('Sudoku', 'resources/views'), 'sudoku');

        Route::middleware('web')->group(module_path('Sudoku', 'routes/web.php'));
        // #202 : ajout 'web' middleware sur API pour que la session cookie soit
        // disponible (Auth::user() fonctionne dans SudokuConstructionGate).
        // Sans web middleware, Auth::user() = null en API -> 503 pour admin connecte.
        Route::middleware(['web', 'api'])->prefix('api')->group(module_path('Sudoku', 'routes/api.php'));

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\Sudoku\Console\GenerateDailyPuzzleCommand::class,
            ]);

            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('sudoku:generate-daily')
                    ->dailyAt('00:01')
                    ->timezone('America/Toronto');
            });
        }
    }

    public function register(): void
    {
        // Aucun config a merger pour le moment.
    }
}
