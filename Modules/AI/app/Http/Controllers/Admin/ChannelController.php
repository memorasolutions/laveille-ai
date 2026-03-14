<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\AI\Models\Channel;

class ChannelController extends Controller
{
    public function index(): View
    {
        $channels = Channel::all();

        return view('ai::admin.channels.index', compact('channels'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'type' => 'required|in:email,whatsapp,telegram,sms',
            'credentials' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $data['inbound_secret'] = Str::random(40);

        Channel::create($data);

        return back()->with('success', __('Le canal a été créé.'));
    }

    public function update(Request $request, Channel $channel): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|max:255',
            'type' => 'required|in:email,whatsapp,telegram,sms',
            'credentials' => 'nullable|array',
            'settings' => 'nullable|array',
        ]);

        $channel->update($data);

        return back()->with('success', __('Le canal a été mis à jour.'));
    }

    public function destroy(Channel $channel): RedirectResponse
    {
        $channel->delete();

        return back()->with('success', __('Le canal a été supprimé.'));
    }

    public function toggle(Channel $channel): RedirectResponse
    {
        $channel->update(['is_active' => ! $channel->is_active]);

        return back()->with('success', __('Le statut du canal a été mis à jour.'));
    }
}
