<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;
use Spatie\Health\ResultStores\ResultStore;

class BackofficeHealthController extends Controller
{
    public function index(): View
    {
        $results = app(ResultStore::class)->latestResults();

        return view('backoffice::health.index', compact('results'));
    }

    public function refresh(): RedirectResponse
    {
        Artisan::call('health:check');

        return redirect()->route('admin.health')->with('success', 'Vérifications effectuées.');
    }
}
