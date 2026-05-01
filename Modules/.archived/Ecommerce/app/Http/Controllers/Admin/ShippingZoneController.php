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
use Modules\Ecommerce\Models\ShippingZone;

class ShippingZoneController extends Controller
{
    public function index(): View
    {
        $zones = ShippingZone::withCount('methods')->orderBy('sort_order')->paginate(15);

        return view('ecommerce::admin.shipping-zones.index', compact('zones'));
    }

    public function create(): View
    {
        return view('ecommerce::admin.shipping-zones.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $zone = ShippingZone::create([
            'name' => $data['name'],
            'regions' => $data['regions'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->syncMethods($zone, $data['methods'] ?? []);

        return redirect()->route('admin.ecommerce.shipping-zones.index')
            ->with('success', __('Zone de livraison créée.'));
    }

    public function edit(ShippingZone $shippingZone): View
    {
        $shippingZone->load('methods');

        return view('ecommerce::admin.shipping-zones.edit', ['zone' => $shippingZone]);
    }

    public function update(Request $request, ShippingZone $shippingZone): RedirectResponse
    {
        $data = $request->validate($this->rules());

        $shippingZone->update([
            'name' => $data['name'],
            'regions' => $data['regions'],
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->syncMethods($shippingZone, $data['methods'] ?? []);

        return redirect()->route('admin.ecommerce.shipping-zones.index')
            ->with('success', __('Zone de livraison mise à jour.'));
    }

    public function destroy(ShippingZone $shippingZone): RedirectResponse
    {
        $shippingZone->delete();

        return redirect()->route('admin.ecommerce.shipping-zones.index')
            ->with('success', __('Zone de livraison supprimée.'));
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'regions' => 'required|array|min:1',
            'regions.*' => 'string|max:5',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
            'methods' => 'nullable|array',
            'methods.*.name' => 'required|string|max:255',
            'methods.*.type' => 'required|in:flat_rate,free,per_weight,percentage',
            'methods.*.cost' => 'required|numeric|min:0',
            'methods.*.min_order' => 'nullable|numeric|min:0',
            'methods.*.max_order' => 'nullable|numeric|min:0',
        ];
    }

    /** @param array<int, array<string, mixed>> $methods */
    private function syncMethods(ShippingZone $zone, array $methods): void
    {
        $zone->methods()->delete();

        foreach ($methods as $i => $m) {
            $zone->methods()->create([
                'name' => $m['name'],
                'type' => $m['type'],
                'cost' => $m['cost'],
                'min_order' => $m['min_order'] ?? null,
                'max_order' => $m['max_order'] ?? null,
                'is_active' => true,
                'sort_order' => $i,
            ]);
        }
    }
}
