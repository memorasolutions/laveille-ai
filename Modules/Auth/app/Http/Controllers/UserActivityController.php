<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class UserActivityController extends Controller
{
    public function index(Request $request): View
    {
        $activities = Activity::causedBy(auth()->user())
            ->latest()
            ->paginate(20);

        return view('auth::activity.index', ['activities' => $activities]);
    }
}
