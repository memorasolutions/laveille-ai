<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\SaaS\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\SaaS\Models\Plan;

class CheckoutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
        ]);

        /** @var Plan $plan */
        $plan = Plan::findOrFail($validated['plan_id']);

        if (empty($plan->stripe_price_id)) {
            return back()->with('error', 'Ce plan n\'est pas encore disponible pour le paiement.');
        }

        try {
            $checkoutSession = $request->user()->newSubscription('default', $plan->stripe_price_id)
                ->trialDays($plan->trial_days)
                ->checkout([
                    'success_url' => route('checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('checkout.cancel'),
                ]);

            Log::info('Checkout initiated', [
                'user_id' => $request->user()->id,
                'plan' => $plan->name,
                'stripe_price' => $plan->stripe_price_id,
            ]);

            return redirect($checkoutSession->url);
        } catch (\Exception $e) {
            Log::error('Checkout failed', [
                'user_id' => $request->user()->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Erreur lors de la création du paiement. Veuillez réessayer.');
        }
    }

    public function success(Request $request): View
    {
        $sessionId = $request->query('session_id');

        if ($sessionId) {
            Log::info('Checkout success', [
                'user_id' => $request->user()?->id,
                'session_id' => $sessionId,
            ]);
        }

        session()->flash('success', 'Votre abonnement a été activé avec succès !');

        return view('saas::checkout.success');
    }

    public function cancel(): View
    {
        session()->flash('info', 'Le paiement a été annulé. Aucun montant n\'a été débité.');

        return view('saas::checkout.cancel');
    }

    public function portal(Request $request): RedirectResponse
    {
        return $request->user()->redirectToBillingPortal(route('user.subscription'));
    }
}
