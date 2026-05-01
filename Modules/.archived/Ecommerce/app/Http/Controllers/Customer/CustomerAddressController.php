<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Ecommerce\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Address;

class CustomerAddressController extends Controller
{
    public function index(Request $request): View
    {
        $addresses = Address::where('user_id', $request->user()->id)->get();

        return view('ecommerce::customer.addresses', compact('addresses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:shipping,billing',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();

        if (! empty($validated['is_default'])) {
            Address::where('user_id', $user->id)
                ->where('type', $validated['type'])
                ->update(['is_default' => false]);
        }

        Address::create([...$validated, 'user_id' => $user->id]);

        return redirect()->back()->with('success', __('Adresse ajoutée avec succès.'));
    }

    public function update(Request $request, Address $address): RedirectResponse
    {
        abort_if((int) $address->user_id !== (int) $request->user()->id, 403);

        $validated = $request->validate([
            'type' => 'required|in:shipping,billing',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company' => 'nullable|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'state' => 'required|string|max:255',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|max:2',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'boolean',
        ]);

        $user = $request->user();

        if (! empty($validated['is_default'])) {
            Address::where('user_id', $user->id)
                ->where('type', $validated['type'])
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update($validated);

        return redirect()->back()->with('success', __('Adresse mise à jour.'));
    }

    public function destroy(Request $request, Address $address): RedirectResponse
    {
        abort_if((int) $address->user_id !== (int) $request->user()->id, 403);

        $address->delete();

        return redirect()->back()->with('success', __('Adresse supprimée.'));
    }
}
