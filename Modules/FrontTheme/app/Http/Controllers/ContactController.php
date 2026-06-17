<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\FrontTheme\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(): View
    {
        return view('fronttheme::contact');
    }

    public function send(Request $request): RedirectResponse|JsonResponse
    {
        $success = function () use ($request) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => __('Votre message a bien été envoyé.')]);
            }

            return back()->with('success', __('Votre message a bien été envoyé.'));
        };

        // Anti-bot — honeypot maison : champ caché 'hp_url' qu'un humain ne remplit jamais.
        // Rejet SILENCIEUX (on renvoie le succès, le bot n'apprend rien) sans envoyer le courriel.
        if ($request->filled('hp_url')) {
            return $success();
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10'],
        ]);

        // Anti-pourriel de liens SEO : compte les URL (http(s)://, www. ou domaine.tld/chemin).
        // 4 liens ou plus dans le message → rejet silencieux (cas typique du spam de backlinks).
        $urlCount = preg_match_all('~(?:https?://|www\.|\b[a-z0-9][a-z0-9.-]*\.[a-z]{2,}/)\S*~i', (string) $validated['message']);
        if ($urlCount >= 4) {
            return $success();
        }

        // Corps enrichi : qui a écrit + comment répondre (l'expéditeur reste l'adresse du site
        // pour la délivrabilité ; le Reply-To pointe vers le visiteur → « Répondre » lui écrit).
        $body = 'Nouveau message reçu via le formulaire de contact de '.config('app.name').'.'."\n\n"
            .'De : '.$validated['name'].' <'.$validated['email'].'>'."\n"
            .'Sujet : '.$validated['subject']."\n"
            .'Date : '.now()->timezone('America/Toronto')->format('Y-m-d H:i').' (Québec)'."\n\n"
            .'Message :'."\n".$validated['message']."\n\n"
            .'— Pour lui répondre, utilisez simplement « Répondre » : votre réponse ira directement à '.$validated['email'].'.';

        Mail::raw($body, function ($message) use ($validated) {
            $message->from(config('mail.from.address'), config('mail.from.name'))
                ->to(config('mail.from.address'))
                ->subject('Contact: '.$validated['subject'])
                ->replyTo($validated['email'], $validated['name']);
        });

        return $success();
    }
}
