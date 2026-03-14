<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\SaaS\Models\Plan;
use Modules\SaaS\Services\SubscriptionService;
use Symfony\Component\HttpFoundation\Response;

class UserSubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(): View
    {
        $user = auth()->user();

        $activeSub = DB::table('subscriptions')
            ->where('user_id', $user->id)
            ->whereIn('stripe_status', ['active', 'trialing'])
            ->whereNull('ends_at')
            ->first();

        if ($activeSub) {
            $currentPlan = Plan::where('stripe_price_id', $activeSub->stripe_price)->first();
            $planName = $currentPlan->name ?? 'Pro';
        } else {
            $currentPlan = null;
            $planName = 'Free';
            $activeSub = null;
        }

        $allPlans = Plan::active()->ordered()->get();

        return view('auth::subscription.index', compact(
            'user', 'planName', 'currentPlan', 'activeSub', 'allPlans'
        ));
    }

    public function cancel(SubscriptionService $service): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $endsAt = $service->cancel($user);

        if ($endsAt) {
            return back()->with('success', "Abonnement annulé. Il restera actif jusqu'au {$endsAt}.");
        }

        return back()->with('error', 'Aucun abonnement actif à annuler.');
    }

    public function resume(SubscriptionService $service): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($service->resume($user)) {
            return back()->with('success', 'Abonnement réactivé.');
        }

        return back()->with('error', "L'abonnement ne peut pas être réactivé.");
    }

    public function invoices(SubscriptionService $service): View
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        return view('auth::subscription.invoices', [
            'invoices' => $service->getInvoices($user),
            'status' => $service->getStatus($user),
        ]);
    }

    public function swapPlan(Request $request, SubscriptionService $service): RedirectResponse
    {
        $validated = $request->validate([
            'plan_id' => ['required', 'exists:plans,id'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        /** @var Plan $plan */
        $plan = Plan::findOrFail($validated['plan_id']);

        if (! $user->subscribed('default')) {
            return back()->with('error', __('Vous devez avoir un abonnement actif pour changer de plan.'));
        }

        try {
            $service->swap($user, $plan->stripe_price_id);

            return back()->with('success', __('Plan changé avec succès.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Échec du changement de plan.'));
        }
    }

    public function downloadInvoice(Request $request, string $invoiceId): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return $user->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
        ]);
    }
}
