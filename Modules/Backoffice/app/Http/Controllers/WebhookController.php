<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Backoffice\Models\WebhookEndpoint;

class WebhookController extends Controller
{
    public function index()
    {
        return view('backoffice::webhooks.index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'url' => 'required|url',
            'secret' => 'nullable|string',
        ]);

        WebhookEndpoint::create($validated);

        return redirect()
            ->route('admin.webhooks.index')
            ->with('success', 'Webhook créé');
    }

    public function destroy(WebhookEndpoint $webhook)
    {
        $webhook->delete();

        return redirect()
            ->route('admin.webhooks.index')
            ->with('success', 'Webhook supprimé');
    }
}
