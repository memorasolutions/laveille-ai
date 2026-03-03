<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Modules\Backoffice\Models\ScheduledTask;

class SchedulerController extends Controller
{
    /** @var array<string, string> */
    public const CRON_PRESETS = [
        '* * * * *' => 'Chaque minute',
        '*/5 * * * *' => 'Toutes les 5 minutes',
        '0 * * * *' => 'Chaque heure',
        '0 */2 * * *' => 'Toutes les 2 heures',
        '0 0 * * *' => 'Chaque jour à minuit',
        '0 3 * * *' => 'Chaque jour à 3h',
        '0 0 * * 1' => 'Chaque lundi à minuit',
        '0 0 1 * *' => 'Le 1er de chaque mois',
    ];

    public function index(): View
    {
        $systemTasks = $this->getSystemTasks();
        $customTasks = ScheduledTask::orderBy('created_at', 'desc')->get();

        return view('backoffice::scheduler.index', [
            'title' => 'Tâches planifiées',
            'subtitle' => 'Scheduler',
            'systemTasks' => $systemTasks,
            'customTasks' => $customTasks,
        ]);
    }

    public function create(): View
    {
        return view('backoffice::scheduler.create', [
            'cronPresets' => self::CRON_PRESETS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'command' => 'required|string|max:255',
            'cron_expression' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        ScheduledTask::create($data);

        return redirect()->route('admin.scheduler')->with('success', 'Tâche planifiée créée.');
    }

    public function edit(ScheduledTask $scheduledTask): View
    {
        abort_if($scheduledTask->is_system, 403, 'Les tâches système ne peuvent pas être modifiées.');

        return view('backoffice::scheduler.edit', [
            'task' => $scheduledTask,
            'cronPresets' => self::CRON_PRESETS,
        ]);
    }

    public function update(Request $request, ScheduledTask $scheduledTask): RedirectResponse
    {
        abort_if($scheduledTask->is_system, 403, 'Les tâches système ne peuvent pas être modifiées.');

        $data = $request->validate([
            'command' => 'required|string|max:255',
            'cron_expression' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $scheduledTask->update($data);

        return redirect()->route('admin.scheduler')->with('success', 'Tâche planifiée mise à jour.');
    }

    public function destroy(ScheduledTask $scheduledTask): RedirectResponse
    {
        abort_if($scheduledTask->is_system, 403, 'Les tâches système ne peuvent pas être supprimées.');

        $scheduledTask->delete();

        return redirect()->route('admin.scheduler')->with('success', 'Tâche planifiée supprimée.');
    }

    public function toggle(ScheduledTask $scheduledTask): RedirectResponse
    {
        abort_if($scheduledTask->is_system, 403, 'Les tâches système ne peuvent pas être modifiées.');

        $scheduledTask->update(['is_active' => ! $scheduledTask->is_active]);

        $status = $scheduledTask->is_active ? 'activée' : 'désactivée';

        return redirect()->route('admin.scheduler')->with('success', "Tâche {$status}.");
    }

    /** @return list<array{expression: string, command: string, next_due: string}> */
    private function getSystemTasks(): array
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

        return $tasks;
    }
}
