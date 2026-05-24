<?php

declare(strict_types=1);

namespace Modules\Authors\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Authors\Models\AuthorProfile;

final class UpgradeController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $authorProfile = AuthorProfile::where('user_id', $user->id)->first();
        $isPremium = method_exists($user, 'subscribed') && $user->subscribed('default');

        return view('authors::upgrade', compact('user', 'authorProfile', 'isPremium'));
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $priceId = config('cashier.price_premium');
        abort_if(empty($priceId), 503, "Configuration Stripe incomplète. Contactez l'administrateur.");

        return $user->newSubscription('default', $priceId)->checkout([
            'success_url' => route('authors.upgrade').'?session_id={CHECKOUT_SESSION_ID}&status=success',
            'cancel_url' => route('authors.upgrade').'?status=cancelled',
            'allow_promotion_codes' => true,
        ]);
    }

    public function billingPortal(Request $request)
    {
        $user = $request->user();
        abort_if($user === null, 401);

        return $user->redirectToBillingPortal(route('authors.upgrade'));
    }
}
