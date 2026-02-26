<?php

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Backoffice\Models\FeatureFlagCondition;
use Spatie\Permission\Models\Role;

class FeatureFlagController
{
    public function index(): View
    {
        $features = DB::table('features')
            ->where('scope', 'global')
            ->orderBy('name')
            ->get();

        $conditions = FeatureFlagCondition::all()->keyBy('feature_name');
        $availableTypes = FeatureFlagCondition::availableTypes();
        $roles = Role::pluck('name');

        return view('backoffice::feature-flags.index', compact('features', 'conditions', 'availableTypes', 'roles'));
    }

    public function toggle(string $name): RedirectResponse
    {
        $feature = DB::table('features')
            ->where('scope', 'global')
            ->where('name', $name)
            ->first();

        if ($feature) {
            $newValue = $feature->value === 'true' ? 'false' : 'true';
            DB::table('features')
                ->where('scope', 'global')
                ->where('name', $name)
                ->update(['value' => $newValue]);
        } else {
            DB::table('features')->insert([
                'name' => $name,
                'scope' => 'global',
                'value' => 'true',
            ]);
        }

        return back()->with('success', 'Feature "'.$name.'" mise à jour.');
    }

    public function updateConditions(Request $request, string $name): RedirectResponse
    {
        $validated = $request->validate([
            'condition_type' => 'required|in:always,percentage,roles,environment,schedule',
            'condition_config' => 'nullable|array',
        ]);

        FeatureFlagCondition::updateOrCreate(
            ['feature_name' => $name],
            [
                'condition_type' => $validated['condition_type'],
                'condition_config' => $validated['condition_config'] ?? null,
            ]
        );

        return back()->with('success', 'Conditions de "'.$name.'" mises à jour.');
    }
}
