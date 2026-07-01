<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * CRUD admin des paliers d'abonnement Academy (freemium/pro/organisation).
 * Réservé à can('academy.manage') via le groupe de routes admin/academy (voir
 * routes/admin.php). Aucune écriture Stripe ici : `price_cents` est une donnée
 * NEUTRE saisie par l'admin, `stripe_price_id` reste NULL tant que l'admin ne
 * colle pas manuellement l'identifiant d'un vrai prix créé dans le Dashboard
 * Stripe (hors de ce contrôleur).
 */

declare(strict_types=1);

namespace Modules\Academy\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Academy\Models\SubscriptionTier;

class AdminSubscriptionTierController extends Controller
{
    public function index(): View
    {
        $subscriptionTiers = SubscriptionTier::withCount('assignments')->ordered()->paginate(15);

        return view('academy::admin.subscription-tiers.index', compact('subscriptionTiers'));
    }

    public function create(): View
    {
        $featureKeys = config('academy.subscription_tier_feature_keys', []);

        return view('academy::admin.subscription-tiers.create', compact('featureKeys'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $slug = Str::slug($validated['name']);
        if ($slug === '') {
            $slug = uniqid('palier-');
        }

        $isDefault = $request->boolean('is_default');
        $isActive  = $request->boolean('is_active');

        if ($isDefault) {
            SubscriptionTier::query()->where('is_default', true)->update(['is_default' => false]);
        }

        SubscriptionTier::create([
            'name'             => $validated['name'],
            'slug'             => $slug,
            'description'      => $validated['description'] ?? null,
            'price_cents'      => $validated['price_cents'] ?? null,
            'billing_period'   => $validated['billing_period'],
            'features'         => $validated['features'] ?? [],
            'max_seats'        => $validated['max_seats'] ?? null,
            'stripe_price_id'  => $validated['stripe_price_id'] ?? null,
            'is_default'       => $isDefault,
            'is_active'        => $isActive,
            'sort_order'       => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.academy.subscription-tiers.index')
            ->with('success', 'Palier créé avec succès.');
    }

    public function edit(SubscriptionTier $subscriptionTier): View
    {
        $featureKeys = config('academy.subscription_tier_feature_keys', []);

        return view('academy::admin.subscription-tiers.edit', compact('subscriptionTier', 'featureKeys'));
    }

    public function update(Request $request, SubscriptionTier $subscriptionTier): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $slug = $subscriptionTier->slug;
        if ($validated['name'] !== $subscriptionTier->name) {
            $slug = Str::slug($validated['name']);
            if ($slug === '') {
                $slug = uniqid('palier-');
            }
        }

        $isDefault = $request->boolean('is_default');
        $isActive  = $request->boolean('is_active');

        if ($isDefault) {
            SubscriptionTier::query()
                ->where('id', '!=', $subscriptionTier->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $subscriptionTier->update([
            'name'             => $validated['name'],
            'slug'             => $slug,
            'description'      => $validated['description'] ?? null,
            'price_cents'      => $validated['price_cents'] ?? null,
            'billing_period'   => $validated['billing_period'],
            'features'         => $validated['features'] ?? [],
            'max_seats'        => $validated['max_seats'] ?? null,
            'stripe_price_id'  => $validated['stripe_price_id'] ?? null,
            'is_default'       => $isDefault,
            'is_active'        => $isActive,
            'sort_order'       => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.academy.subscription-tiers.index')
            ->with('success', 'Palier mis à jour avec succès.');
    }

    public function toggleStatus(SubscriptionTier $subscriptionTier): RedirectResponse
    {
        $newStatus = ! $subscriptionTier->is_active;

        $subscriptionTier->update(['is_active' => $newStatus]);

        $message = $newStatus ? 'Palier activé.' : 'Palier désactivé.';

        return redirect()->route('admin.academy.subscription-tiers.index')
            ->with('success', $message);
    }

    public function destroy(SubscriptionTier $subscriptionTier): RedirectResponse
    {
        if ($subscriptionTier->assignments()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer un palier ayant des utilisateurs assignés. Désactivez-le plutôt.');
        }

        $subscriptionTier->delete();

        return redirect()->route('admin.academy.subscription-tiers.index')
            ->with('success', 'Palier supprimé avec succès.');
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        $featureKeys = array_keys(config('academy.subscription_tier_feature_keys', []));

        return [
            'name'             => ['required', 'string', 'max:255'],
            'description'      => ['nullable', 'string', 'max:1000'],
            'price_cents'      => ['nullable', 'integer', 'min:0'],
            'billing_period'   => ['required', Rule::in(['monthly', 'yearly'])],
            'features'         => ['nullable', 'array'],
            'features.*'       => ['string', Rule::in($featureKeys)],
            'max_seats'        => ['nullable', 'integer', 'min:1'],
            'stripe_price_id'  => ['nullable', 'string', 'max:255'],
            'is_default'       => ['nullable', 'boolean'],
            'is_active'        => ['nullable', 'boolean'],
            'sort_order'       => ['nullable', 'integer', 'min:0'],
        ];
    }
}
