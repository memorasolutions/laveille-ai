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
use Illuminate\View\View;
use Modules\AI\Models\Channel;
use Modules\AI\Models\ChannelMessage;

class UnifiedInboxController extends Controller
{
    public function index(Request $request): View
    {
        $query = ChannelMessage::with(['channel', 'ticket'])->latest();

        if ($request->filled('channel_id')) {
            $query->where('channel_id', $request->input('channel_id'));
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $messages = $query->paginate(30);
        $channels = Channel::active()->get();

        return view('ai::admin.inbox.index', compact('messages', 'channels'));
    }

    public function show(ChannelMessage $channelMessage): View
    {
        $channelMessage->load(['channel', 'ticket']);

        return view('ai::admin.inbox.show', compact('channelMessage'));
    }

    public function linkToTicket(Request $request, ChannelMessage $channelMessage): RedirectResponse
    {
        $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
        ]);

        $channelMessage->update(['ticket_id' => $request->input('ticket_id')]);

        return back()->with('success', __('Le message a été lié au ticket.'));
    }
}
