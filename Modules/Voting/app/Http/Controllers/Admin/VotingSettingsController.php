<?php

declare(strict_types=1);

namespace Modules\Voting\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Settings\Facades\Settings;

class VotingSettingsController extends Controller
{
    private const KEYS = [
        'voting.threshold_noticed' => ['default' => 2, 'type' => 'integer'],
        'voting.threshold_approved' => ['default' => 5, 'type' => 'integer'],
        'voting.threshold_favorite' => ['default' => 10, 'type' => 'integer'],
        'voting.rate_limit' => ['default' => 50, 'type' => 'integer'],
        'voting.reputation_vote_cast' => ['default' => 1, 'type' => 'integer'],
        'voting.reputation_community_approved' => ['default' => 15, 'type' => 'integer'],
        'reputation.threshold_contributeur' => ['default' => 15, 'type' => 'integer'],
        'reputation.threshold_verifie' => ['default' => 50, 'type' => 'integer'],
        'reputation.threshold_expert' => ['default' => 150, 'type' => 'integer'],
        'reputation.multiplier_contributeur' => ['default' => 1.25, 'type' => 'double'],
        'reputation.multiplier_verifie' => ['default' => 1.5, 'type' => 'double'],
        'reputation.multiplier_expert' => ['default' => 2.0, 'type' => 'double'],
        'reputation.ban_duration_days' => ['default' => 7, 'type' => 'integer'],
    ];

    public function edit(): View
    {
        $settings = [];
        foreach (self::KEYS as $key => $config) {
            $settings[$key] = Settings::get($key, $config['default']);
        }

        return view('voting::admin.settings', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $rules = [];
        foreach (self::KEYS as $key => $config) {
            $rules[$key] = $config['type'] === 'double'
                ? 'required|numeric|min:1'
                : 'required|integer|min:0';
        }

        $validated = $request->validate($rules);

        foreach ($validated as $key => $value) {
            $config = self::KEYS[$key];
            $group = str_contains($key, 'reputation.') ? 'reputation' : 'voting';
            Settings::set($key, $value, $config['type'], $group);
        }

        return redirect()->back()->with('success', __('Configuration enregistree.'));
    }
}
