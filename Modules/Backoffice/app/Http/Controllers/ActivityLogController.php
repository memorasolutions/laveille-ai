<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\Settings\Models\Setting;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController
{
    public function index(): View
    {
        return view('backoffice::activity-logs.index');
    }

    public function export(): StreamedResponse
    {
        $fileName = 'activity_logs_'.now()->format('Y-m-d_H-i-s').'.csv';

        return new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['ID', 'Date', 'Description', 'Utilisateur', 'Sujet', 'Journal', 'Propriétés']);

            Activity::with('causer')->orderBy('created_at', 'desc')->chunk(1000, function ($activities) use ($handle) {
                foreach ($activities as $activity) {
                    fputcsv($handle, [
                        $activity->id,
                        $activity->created_at->format('Y-m-d H:i:s'),
                        $activity->description,
                        $activity->causer->name ?? 'Système',
                        $activity->subject_type ? class_basename($activity->subject_type).'#'.$activity->subject_id : '-',
                        $activity->log_name,
                        json_encode($activity->properties),
                    ]);
                }
            });

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function purge(): RedirectResponse
    {
        $days = (int) Setting::get('retention.activity_log_days', 180);
        $deletedCount = Activity::where('created_at', '<', now()->subDays($days))->delete();

        return back()->with('success', "{$deletedCount} entrées de plus de {$days} jours supprimées.");
    }
}
