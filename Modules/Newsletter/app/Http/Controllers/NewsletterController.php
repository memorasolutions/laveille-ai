<?php

declare(strict_types=1);

namespace Modules\Newsletter\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Newsletter\Models\Subscriber;
use Modules\Newsletter\Notifications\WelcomeNewsletterNotification;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): RedirectResponse
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
            // Send confirmation email
            \Illuminate\Support\Facades\Notification::route('mail', $subscriber->email)
                ->notify(new WelcomeNewsletterNotification($subscriber));
        }

        return back()->with('newsletter_success', 'Vérifiez votre courriel pour confirmer votre abonnement !');
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
}
