<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class UserSavedController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();

        $savedPrompts = class_exists(\Modules\Tools\Models\SavedPrompt::class)
            ? \Modules\Tools\Models\SavedPrompt::forUser($user->id)->latest()->get()
            : collect();

        $savedTeamPresets = class_exists(\Modules\Tools\Models\SavedTeamPreset::class)
            ? \Modules\Tools\Models\SavedTeamPreset::forUser($user->id)->latest()->get()
            : collect();

        return view('auth::saved.index', compact('user', 'savedPrompts', 'savedTeamPresets'));
    }
}
