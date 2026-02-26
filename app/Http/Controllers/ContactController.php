<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Mail\ContactMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('contact');
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $key = 'contact.'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            return back()->withErrors([
                'rate_limit' => __('Trop de tentatives. Réessayez dans :seconds secondes.', ['seconds' => $seconds]),
            ]);
        }

        RateLimiter::hit($key, 3600);

        Mail::to(config('mail.from.address'))->send(new ContactMail($validated));

        return redirect()->route('contact.show')->with('success', 'Votre message a été envoyé. Nous vous répondrons rapidement.');
    }
}
