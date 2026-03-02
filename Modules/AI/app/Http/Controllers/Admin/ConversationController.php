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
use Modules\AI\Models\AiConversation;

class ConversationController extends Controller
{
    public function index(Request $request): View
    {
        $query = AiConversation::with('user')
            ->withCount('messages')
            ->latest();

        if ($request->filled('status')) {
            $validStatuses = array_column(ConversationStatus::cases(), 'value');
            if (in_array($request->input('status'), $validStatuses, true)) {
                $query->where('status', $request->input('status'));
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        $conversations = $query->paginate(20)->appends($request->query());

        $statusCounts = [];
        foreach (ConversationStatus::cases() as $status) {
            $statusCounts[$status->value] = AiConversation::where('status', $status->value)->count();
        }

        return view('ai::admin.conversations.index', compact('conversations', 'statusCounts'));
    }

    public function show(AiConversation $conversation): View
    {
        $conversation->load([
            'user',
            'messages' => fn ($query) => $query->orderBy('created_at', 'asc'),
        ]);

        return view('ai::admin.conversations.show', compact('conversation'));
    }

    public function destroy(AiConversation $conversation): RedirectResponse
    {
        $conversation->status = ConversationStatus::Closed;
        $conversation->closed_at = now();
        $conversation->save();

        return redirect()->route('admin.ai.conversations.index')
            ->with('success', 'Conversation fermée.');
    }
}
