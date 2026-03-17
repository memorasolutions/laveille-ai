<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Promotion;

class PromotionController extends Controller
{
    public function index(): View
    {
        $promotions = Promotion::orderBy('priority', 'desc')->paginate(15);

        return view('ecommerce::admin.promotions.index', compact('promotions'));
    }

    public function create(): View
    {
        return view('ecommerce::admin.promotions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $validated['is_stackable'] = $request->boolean('is_stackable');
        $validated['is_automatic'] = $request->boolean('is_automatic');
        $validated['is_active'] = $request->boolean('is_active');

        Promotion::create($validated);

        session()->flash('success', __('Promotion créée avec succès.'));

        return redirect()->route('admin.ecommerce.promotions.index');
    }

    public function edit(Promotion $promotion): View
    {
        return view('ecommerce::admin.promotions.edit', compact('promotion'));
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $validated['is_stackable'] = $request->boolean('is_stackable');
        $validated['is_automatic'] = $request->boolean('is_automatic');
        $validated['is_active'] = $request->boolean('is_active');

        $promotion->update($validated);

        session()->flash('success', __('Promotion mise à jour avec succès.'));

        return redirect()->route('admin.ecommerce.promotions.index');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $promotion->delete();

        session()->flash('success', __('Promotion supprimée avec succès.'));

        return redirect()->route('admin.ecommerce.promotions.index');
    }

    private function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'type' => 'required|in:percentage_off,fixed_off,bogo,free_shipping,tiered_pricing',
            'value' => 'nullable|numeric|min:0',
            'applies_to' => 'required|in:all,specific_products,specific_categories',
            'target_ids' => 'nullable|array',
            'conditions' => 'nullable|array',
            'tiers' => 'nullable|array',
            'bogo_config' => 'nullable|array',
            'priority' => 'integer|min:0',
            'is_stackable' => 'nullable',
            'is_automatic' => 'nullable',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'nullable',
        ];
    }
}
