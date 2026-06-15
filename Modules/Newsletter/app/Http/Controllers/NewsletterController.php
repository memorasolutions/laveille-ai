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
        $message = __('Vérifiez votre courriel pour confirmer votre abonnement ! Pensez à regarder dans vos courriers indésirables (spams) si vous ne le voyez pas dans quelques minutes.');
        $silentSuccess = fn () => $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('newsletter_success', $message);

        // Anti-bot — honeypot maison : champ caché 'hp_url' (un humain ne le remplit JAMAIS → zéro faux positif).
        // Graceful : si le champ est absent (form sans honeypot), aucun blocage. Rejet SILENCIEUX (le bot n'apprend rien).
        if ($request->filled('hp_url')) {
            return $silentSuccess();
        }

        $validated = $request->validate([
            'email' => 'required|email:rfc,dns|max:255',
            'name' => 'nullable|string|max:100',
        ], [
            'email.email' => __('L\'adresse courriel n\'est pas valide ou son domaine n\'existe pas.'),
        ]);

        // Anti-bot — domaines de courriels jetables connus → rejet SILENCIEUX (liste minimale, jamais de vrais domaines).
        $domain = strtolower((string) substr((string) strrchr($validated['email'], '@'), 1));
        if (in_array($domain, (array) config('newsletter.disposable_domains', []), true)) {
            return $silentSuccess();
        }

        $subscriber = Subscriber::firstOrCreate(
            ['email' => $validated['email']],
            ['name' => $validated['name'] ?? null]
        );

        if (! $subscriber->isConfirmed()) {
            \Illuminate\Support\Facades\Notification::route('mail', $subscriber->email)
                ->notify(new WelcomeNewsletterNotification($subscriber));
        }

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
            return redirect('/')->with('error', __('Lien invalide.'));
        }

        // Désabonnement immédiat à l'arrivée (legal compliance one-click).
        // Idempotent : on garde la date d'origine si déjà désabonné.
        if ($subscriber->unsubscribed_at === null) {
            $subscriber->update(['unsubscribed_at' => now()]);
        }

        return view('newsletter::unsubscribe-confirmed', [
            'subscriber' => $subscriber->fresh(),
        ]);
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

    public function saveFeedback(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if (! $subscriber) {
            return $this->respondInvalidToken($request);
        }

        $validated = $request->validate([
            'reason' => 'required|in:too_frequent,not_relevant,no_value,life_change,other',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $subscriber->update([
            'unsubscribe_reason' => $validated['reason'],
            'unsubscribe_feedback' => $validated['feedback'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => __('Merci pour votre retour.')]);
        }

        return back()->with('newsletter_feedback_saved', __('Merci pour votre retour.'));
    }

    public function pauseSubscription(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if (! $subscriber) {
            return $this->respondInvalidToken($request);
        }

        $validated = $request->validate([
            'days' => 'required|integer|in:30,60,90',
        ]);

        $days = (int) $validated['days'];

        // Pause ≠ désabo : on réactive et on positionne paused_until.
        $subscriber->update([
            'paused_until' => now()->addDays($days),
            'unsubscribed_at' => null,
        ]);

        $message = __('Abonnement mis en pause pour :days jours.', ['days' => $days]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('newsletter_paused', $message);
    }

    public function updateFrequency(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if (! $subscriber) {
            return $this->respondInvalidToken($request);
        }

        $validated = $request->validate([
            'frequency' => 'required|in:weekly,biweekly,monthly',
        ]);

        $subscriber->update([
            'frequency_preference' => $validated['frequency'],
            'unsubscribed_at' => null,
        ]);

        $message = __('Préférence de fréquence mise à jour.');

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('newsletter_frequency_updated', $message);
    }

    public function resubscribe(Request $request, string $token): JsonResponse|RedirectResponse
    {
        $subscriber = Subscriber::where('token', $token)->first();

        if (! $subscriber) {
            return $this->respondInvalidToken($request);
        }

        $subscriber->update([
            'unsubscribed_at' => null,
            'paused_until' => null,
        ]);

        $message = __('Vous êtes à nouveau abonné. Heureux de vous revoir !');

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('newsletter_resubscribed', $message);
    }

    private function respondInvalidToken(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => __('Lien invalide.')], 404);
        }

        return redirect('/')->with('error', __('Lien invalide.'));
    }
}
