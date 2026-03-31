<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Modules\SEO\Models\UrlRedirect;
use Modules\Settings\Facades\Settings;

class UrlRedirectController extends Controller
{
    public function index(Request $request): View
    {
        $redirects = UrlRedirect::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('from_url', 'like', "%{$search}%")
                        ->orWhere('to_url', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate((int) Settings::get('backoffice.url_redirects_per_page', 25))
            ->withQueryString();

        return view('seo::admin.redirects.index', compact('redirects'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        UrlRedirect::create($validated);

        $this->flushRedirectCache();

        return redirect()->back()->with('success', 'Redirection créée.');
    }

    public function update(Request $request, UrlRedirect $redirect): RedirectResponse
    {
        $validated = $request->validate($this->validationRules($redirect));

        $redirect->update($validated);

        $this->flushRedirectCache();

        return redirect()->back()->with('success', 'Redirection mise à jour.');
    }

    public function destroy(UrlRedirect $redirect): RedirectResponse
    {
        $redirect->delete();

        $this->flushRedirectCache();

        return redirect()->back()->with('success', 'Redirection supprimée.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function validationRules(?UrlRedirect $redirect = null): array
    {
        $uniqueRule = 'unique:url_redirects,from_url';
        if ($redirect) {
            $uniqueRule .= ','.$redirect->id;
        }

        return [
            'from_url' => ['required', 'string', 'max:2048', $uniqueRule],
            'to_url' => ['required', 'string', 'max:2048'],
            'status_code' => ['required', 'in:301,302,307,308'],
            'is_active' => ['sometimes', 'boolean'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    private function flushRedirectCache(): void
    {
        // Clear all cached redirect lookups
        Cache::flush();
    }
}
