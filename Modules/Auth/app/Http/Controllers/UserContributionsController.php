<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class UserContributionsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();

        $suggestions = class_exists(\Modules\Directory\Models\ToolSuggestion::class)
            ? \Modules\Directory\Models\ToolSuggestion::where('user_id', $user->id)->with('suggestable')->latest()->get()
            : collect();

        $votes = class_exists(\Modules\Roadmap\Models\Vote::class)
            ? \Modules\Roadmap\Models\Vote::where('user_id', $user->id)->with('idea')->latest()->get()
            : collect();

        return view('auth::contributions.index', compact('user', 'suggestions', 'votes'));
    }
}
