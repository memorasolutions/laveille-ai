<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WelcomeNewsletterNotification;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:100',
        ]);

        $subscriber = Subscriber::firstOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name'] ?? null]
        );

        if (! $subscriber->isConfirmed()) {
            \Illuminate\Support\Facades\Notification::route('mail', $subscriber->email)
                ->notify(new WelcomeNewsletterNotification($subscriber));
        }

        $message = __('Vérifiez votre courriel pour confirmer votre abonnement ! Pensez à regarder dans vos courriers indésirables (spams) si vous ne le voyez pas dans quelques minutes.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('newsletter_success', $message);
    }

    public function confirm(string $token): RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)->firstOrFail();

        if (! $subscriber->isConfirmed()) {
            $subscriber->update(['confirmed_at' => now()]);
        }

        return redirect('/')->with('newsletter_confirmed', 'Abonnement confirmé ! Merci.');
    }

    public function unsubscribe(string $token): View|RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if (! $subscriber) {
            return redirect('/')->with('error', 'Lien invalide.');
        }

        $subscriber->update(['unsubscribed_at' => now()]);

        return redirect('/')->with('newsletter_unsubscribed', 'Vous avez été désabonné avec succès.');
    }

    public function unsubscribeOneClick(string $token): \Illuminate\Http\Response
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if ($subscriber && $subscriber->unsubscribed_at === null) {
            $subscriber->update(['unsubscribed_at' => now()]);
            \Illuminate\Support\Facades\Log::info('RFC8058 one-click unsubscribe', [
                'email' => $subscriber->email,
                'token' => $token,
            ]);
        }

        return response('', 204);
    }
}
