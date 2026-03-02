<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Enums\ConversationStatus;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Events\AgentMessageReceived;
use Modules\AI\Events\HumanTakeoverRequested;
use Modules\AI\Models\AiConversation;
use Modules\AI\Models\AiMessage;

class AgentDashboardController extends Controller
{
    public function index(): View
    {
        $waitingConversations = AiConversation::where('status', ConversationStatus::WaitingHuman)
            ->with(['user', 'messages'])
            ->latest()
            ->get();

        $myConversations = AiConversation::where('status', ConversationStatus::HumanActive)
            ->where('agent_id', auth()->id())
            ->with(['user', 'messages'])
            ->latest()
            ->get();

        return view('ai::admin.agent.index', compact('waitingConversations', 'myConversations'));
    }

    public function claim(AiConversation $conversation): RedirectResponse
    {
        if ($conversation->status !== ConversationStatus::WaitingHuman) {
            abort(409, 'Cette conversation n\'est plus disponible.');
        }

        $conversation->update([
            'status' => ConversationStatus::HumanActive,
            'agent_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Conversation prise en charge.');
    }

    public function reply(Request $request, AiConversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        if ((int) $conversation->agent_id !== (int) auth()->id()) {
            abort(403);
        }

        AiMessage::create([
            'conversation_id' => $conversation->id,
            'role' => MessageRole::Agent,
            'content' => $validated['message'],
        ]);

        event(new AgentMessageReceived($conversation, $validated['message'], auth()->user()->name));

        return redirect()->back()->with('success', 'Réponse envoyée.');
    }

    public function close(AiConversation $conversation): RedirectResponse
    {
        if ((int) $conversation->agent_id !== (int) auth()->id()) {
            abort(403);
        }

        $conversation->update([
            'status' => ConversationStatus::Closed,
            'closed_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Conversation fermée.');
    }

    public function release(AiConversation $conversation): RedirectResponse
    {
        if ((int) $conversation->agent_id !== (int) auth()->id()) {
            abort(403);
        }

        $conversation->update([
            'status' => ConversationStatus::WaitingHuman,
            'agent_id' => null,
        ]);

        return redirect()->back()->with('success', 'Conversation relâchée.');
    }
}
