<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SchedulerController
{
    public function index(): View
    {
        Artisan::call('schedule:list');

        $output = Artisan::output();
        $tasks = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '-')) {
                continue;
            }

            if (preg_match('/^([\*\d\/\-\,\s]{9,20})\s+(.+?)\s+(?:\.+\s+)?Next Due:\s*(.+)$/i', $line, $matches)) {
                $tasks[] = [
                    'expression' => trim($matches[1]),
                    'command' => trim($matches[2]),
                    'next_due' => trim($matches[3]),
                ];
            }
        }

        return view('backoffice::scheduler.index', [
            'title' => 'Tâches planifiées',
            'subtitle' => 'Scheduler',
            'tasks' => $tasks,
            'rawOutput' => $output,
        ]);
    }
}
