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
            'ad_code' => 'nullable|string|required_without:ad_slot',
            'ad_slot' => 'nullable|string|max:32',
            'ad_format' => 'nullable|in:auto,horizontal,rectangle,vertical,fluid',
            'min_height' => 'nullable|integer|min:0|max:2000',
            'sort_order' => 'integer',
        ]);

        // Les interrupteurs non cochés ne sont pas transmis par le formulaire : lire
        // explicitement, sinon impossible de créer un emplacement externe (AdSense) ni
        // de régler l'état actif de façon fiable.
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_external'] = $request->boolean('is_external');
        $validated['lazy'] = $request->boolean('lazy');

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
            'ad_code' => 'nullable|string|required_without:ad_slot',
            'ad_slot' => 'nullable|string|max:32',
            'ad_format' => 'nullable|in:auto,horizontal,rectangle,vertical,fluid',
            'min_height' => 'nullable|integer|min:0|max:2000',
            'sort_order' => 'integer',
        ]);

        // Même raison qu'à la création : un interrupteur décoché n'est pas transmis.
        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_external'] = $request->boolean('is_external');
        $validated['lazy'] = $request->boolean('lazy');

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
