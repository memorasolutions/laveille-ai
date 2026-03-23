<?php

declare(strict_types=1);

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

namespace Modules\Ads\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ads\Models\AdPlacement;
use Modules\Ads\Services\AdsRenderer;

class AdPlacementController extends Controller
{
    public function index(): View
    {
        $ads = AdPlacement::orderBy('sort_order')->get();

        return view('ads::admin.index', compact('ads'));
    }

    public function create(): View
    {
        return view('ads::admin.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:ads_placements,key',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'ad_code' => 'required|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        AdPlacement::create($validated);

        return redirect()->route('admin.ads.index')->with('success', __('Publicité créée.'));
    }

    public function edit(AdPlacement $ad): View
    {
        return view('ads::admin.edit', compact('ad'));
    }

    public function update(Request $request, AdPlacement $ad): RedirectResponse
    {
        $validated = $request->validate([
            'key' => 'required|string|unique:ads_placements,key,'.$ad->id,
            'name' => 'required|string',
            'description' => 'nullable|string',
            'ad_code' => 'required|string',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $ad->update($validated);
        app(AdsRenderer::class)->clearCache($ad->key);

        return redirect()->route('admin.ads.index')->with('success', __('Publicité mise à jour.'));
    }

    public function destroy(AdPlacement $ad): RedirectResponse
    {
        app(AdsRenderer::class)->clearCache($ad->key);
        $ad->delete();

        return redirect()->route('admin.ads.index')->with('success', __('Publicité supprimée.'));
    }
}
