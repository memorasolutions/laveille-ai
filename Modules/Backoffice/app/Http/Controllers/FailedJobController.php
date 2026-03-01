<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FailedJobController
{
    public function index(): View
    {
        $failedJobs = DB::table('failed_jobs')->latest('failed_at')->get();

        return view('backoffice::failed-jobs.index', [
            'title' => 'Jobs échoués',
            'subtitle' => 'File d\'attente',
            'failedJobs' => $failedJobs,
        ]);
    }

    public function retry(string $id): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$id]]);

        return back()->with('success', 'Job relancé avec succès.');
    }

    public function destroy(string $id): RedirectResponse
    {
        DB::table('failed_jobs')->where('id', $id)->delete();

        return back()->with('success', 'Job supprimé.');
    }

    public function destroyAll(): RedirectResponse
    {
        Artisan::call('queue:flush');

        return back()->with('success', 'Tous les jobs échoués ont été supprimés.');
    }
}
