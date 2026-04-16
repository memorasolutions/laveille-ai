<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
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
            'killSwitches' => $this->killSwitches(),
            'failedJobs' => $this->failedJobsStats(),
        ]);
    }

    /**
     * Stats des jobs échoués depuis la table failed_jobs.
     *
     * @return array{total:int, last_7_days:int, last_24h:int, by_class:mixed, recent:mixed}
     */
    private function failedJobsStats(): array
    {
        try {
            $total = \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
            $last7Days = \Illuminate\Support\Facades\DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDays(7))
                ->count();
            $last24h = \Illuminate\Support\Facades\DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDay())
                ->count();

            $byClass = \Illuminate\Support\Facades\DB::table('failed_jobs')
                ->select('payload', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), \Illuminate\Support\Facades\DB::raw('MAX(failed_at) as last_failed'))
                ->groupBy('payload')
                ->orderByDesc('count')
                ->limit(50)
                ->get()
                ->map(function ($row) {
                    $decoded = json_decode((string) $row->payload, false);
                    return (object) [
                        'class' => $decoded->displayName ?? 'Unknown',
                        'count' => (int) $row->count,
                        'last_failed' => $row->last_failed,
                    ];
                })
                ->groupBy('class')
                ->map(function ($group) {
                    return (object) [
                        'class' => $group->first()->class,
                        'count' => $group->sum('count'),
                        'last_failed' => \Illuminate\Support\Carbon::parse($group->max('last_failed')),
                    ];
                })
                ->sortByDesc('count')
                ->take(5)
                ->values();

            $recent = \Illuminate\Support\Facades\DB::table('failed_jobs')
                ->select('id', 'payload', 'exception', 'failed_at')
                ->orderByDesc('failed_at')
                ->limit(5)
                ->get()
                ->map(function ($row) {
                    $decoded = json_decode((string) $row->payload, false);
                    return (object) [
                        'id' => $row->id,
                        'displayName' => $decoded->displayName ?? 'Unknown',
                        'exception' => \Illuminate\Support\Str::limit((string) $row->exception, 200),
                        'failed_at' => \Illuminate\Support\Carbon::parse($row->failed_at),
                    ];
                });

            return [
                'total' => $total,
                'last_7_days' => $last7Days,
                'last_24h' => $last24h,
                'by_class' => $byClass,
                'recent' => $recent,
            ];
        } catch (\Throwable $e) {
            return [
                'total' => 0,
                'last_7_days' => 0,
                'last_24h' => 0,
                'by_class' => collect(),
                'recent' => collect(),
            ];
        }
    }

    /**
     * Retourne la liste des 9 kill switches Pennant avec leur état actuel.
     *
     * @return list<array{flag: string, label: string, description: string, active: bool}>
     */
    private function killSwitches(): array
    {
        $flags = [
            ['flag' => 'cron.newsletter-send', 'label' => 'Envoi newsletter hebdomadaire', 'description' => 'Mercredi 9h, digest aux 48 abonnés'],
            ['flag' => 'cron.newsletter-preview', 'label' => 'Preview newsletter', 'description' => 'Mardi 9h, envoi aperçu à l\'admin'],
            ['flag' => 'cron.ai-enrich', 'label' => 'Enrichissement IA outils', 'description' => '6 commandes Directory (enrich, summarize, alternatives, reenrich-stale, screenshots, FR trad)'],
            ['flag' => 'cron.gelato-sync', 'label' => 'Sync prix Gelato', 'description' => 'Dimanche 3h, sync coûts POD'],
            ['flag' => 'cron.news-fetch', 'label' => 'Fetch news RSS', 'description' => ':15 chaque heure, 23 sources'],
            ['flag' => 'cron.directory-discovery', 'label' => 'Découverte outils', 'description' => '04h00 Product Hunt + RSS'],
            ['flag' => 'cron.directory-tutorials', 'label' => 'Tutoriels YouTube', 'description' => '05h00 batch 5, enrich FR'],
            ['flag' => 'cron.directory-pricing', 'label' => 'Refresh pricing', 'description' => 'Re-vérification pricing outils'],
            ['flag' => 'cron.directory-formations', 'label' => 'Formations gratuites', 'description' => 'Dimanche 07h00 batch 5'],
        ];

        if (! class_exists(\Laravel\Pennant\Feature::class)) {
            foreach ($flags as $i => $item) {
                $flags[$i]['active'] = true;
            }
            return $flags;
        }

        foreach ($flags as $i => $item) {
            $flags[$i]['active'] = \Laravel\Pennant\Feature::active($item['flag']);
        }
        return $flags;
    }

    /**
     * Bascule un kill switch Pennant (whitelist stricte).
     */
    public function toggleKillSwitch(string $flag): RedirectResponse
    {
        $allowed = [
            'cron.newsletter-send',
            'cron.newsletter-preview',
            'cron.ai-enrich',
            'cron.gelato-sync',
            'cron.news-fetch',
            'cron.directory-discovery',
            'cron.directory-tutorials',
            'cron.directory-pricing',
            'cron.directory-formations',
        ];

        if (! in_array($flag, $allowed, true)) {
            abort(404, 'Kill switch inconnu.');
        }

        if (! class_exists(\Laravel\Pennant\Feature::class)) {
            return redirect()->route('admin.scheduler')
                ->with('error', 'Laravel Pennant n\'est pas installé.');
        }

        if (\Laravel\Pennant\Feature::active($flag)) {
            \Laravel\Pennant\Feature::deactivate($flag);
            $state = 'désactivé';
        } else {
            \Laravel\Pennant\Feature::activate($flag);
            $state = 'activé';
        }

        return redirect()->route('admin.scheduler')
            ->with('success', "Le kill switch « {$flag} » a été {$state}.");
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
