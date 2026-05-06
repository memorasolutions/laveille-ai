<?php

declare(strict_types=1);

namespace Modules\Sudoku\Console;

use Illuminate\Console\Command;
use Modules\Sudoku\Models\SudokuPuzzle;
use Modules\Sudoku\Services\SudokuGeneratorService;

class GenerateDailyPuzzleCommand extends Command
{
    protected $signature = 'sudoku:generate-daily {--days=1 : Nombre de jours à générer en avance} {--force : Forcer même si puzzle existe}';

    protected $description = 'Génère les 5 puzzles quotidiens (un par difficulté) pour la date du jour';

    public function handle(SudokuGeneratorService $generator): int
    {
        $days = (int) $this->option('days');
        $force = (bool) $this->option('force');
        $difficulties = ['easy', 'medium', 'hard', 'expert', 'diabolical'];

        $totalCreated = 0;
        for ($d = 0; $d < $days; $d++) {
            $date = now('America/Toronto')->addDays($d)->toDateString();

            foreach ($difficulties as $difficulty) {
                $exists = SudokuPuzzle::where('date', $date)
                    ->where('difficulty', $difficulty)
                    ->exists();

                if ($exists && ! $force) {
                    $this->line("Skip {$date} {$difficulty} (existe déjà)");
                    continue;
                }

                if ($exists && $force) {
                    SudokuPuzzle::where('date', $date)->where('difficulty', $difficulty)->delete();
                }

                $data = $generator->generate($difficulty);
                SudokuPuzzle::create([
                    'difficulty' => $difficulty,
                    'date' => $date,
                    'grid_init' => $data['grid_init'],
                    'solution' => $data['solution'],
                    'clues_count' => $data['clues_count'],
                    'generation_time_ms' => $data['time_ms'],
                ]);

                $this->info("✓ {$date} {$difficulty} généré ({$data['clues_count']} indices, {$data['time_ms']} ms)");
                $totalCreated++;
            }
        }

        $this->newLine();
        $this->info("BILAN : {$totalCreated} puzzle(s) créé(s) sur {$days} jour(s).");

        return self::SUCCESS;
    }
}
