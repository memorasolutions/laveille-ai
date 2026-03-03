<?php

declare(strict_types=1);

namespace Modules\ShortUrl\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Modules\ShortUrl\Services\ShortUrlService;

class ShortUrlRedirectController
{
    public function __construct(
        private readonly ShortUrlService $service
    ) {}

    public function __invoke(Request $request, string $slug): RedirectResponse|Response
    {
        $shortUrl = $this->service->resolve($slug);

        if (! $shortUrl) {
            abort(404);
        }

        if (! $shortUrl->isAccessible()) {
            abort(410, 'Ce lien a expiré.');
        }

        if (! empty($shortUrl->password) && ! $request->session()->get("short_url_password_{$shortUrl->id}")) {
            return response()->view('shorturl::public.password', compact('shortUrl', 'slug'));
        }

        $this->service->trackClick($shortUrl, $request);

        return redirect()->away($shortUrl->original_url, $shortUrl->redirect_type ?? 302);
    }

    public function checkPassword(Request $request, string $slug): RedirectResponse
    {
        $shortUrl = $this->service->resolve($slug);

        if (! $shortUrl) {
            abort(404);
        }

        $request->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->input('password'), $shortUrl->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        $request->session()->put("short_url_password_{$shortUrl->id}", true);

        return redirect("/s/{$slug}");
    }
}
