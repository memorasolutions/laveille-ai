<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\AI\Http\Controllers\Admin;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\AI\Enums\TicketPriority;
use Modules\AI\Enums\TicketStatus;
use Modules\AI\Models\SlaPolicy;
use Modules\AI\Models\Ticket;
use Modules\AI\Models\TicketReply;
use Modules\AI\Models\TicketTag;
use Modules\AI\Enums\MessageRole;
use Modules\AI\Models\AiConversation;

class TicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ticket::with(['user', 'agent', 'tags'])->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($priority = $request->query('priority')) {
            $query->where('priority', $priority);
        }
        if ($agentId = $request->query('agent_id')) {
            $query->where('agent_id', $agentId);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $tickets = $query->paginate(20);

        return view('ai::admin.tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        $agents = User::permission('manage_ai')->get();
        $slaPolicies = SlaPolicy::active()->get();
        $tags = TicketTag::all();
        $priorities = TicketPriority::cases();
        $statuses = TicketStatus::cases();

        return view('ai::admin.tickets.create', compact('agents', 'slaPolicies', 'tags', 'priorities', 'statuses'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'priority' => 'required|in:low,medium,high,urgent',
            'category' => 'nullable|max:100',
            'agent_id' => 'nullable|exists:users,id',
            'sla_policy_id' => 'nullable|exists:sla_policies,id',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:ticket_tags,id',
        ]);

        $ticket = new Ticket();
        $ticket->fill($data);
        $ticket->user_id = auth()->id();

        if (! empty($data['sla_policy_id'])) {
            $slaPolicy = SlaPolicy::find($data['sla_policy_id']);
            if ($slaPolicy) {
                $ticket->due_at = $slaPolicy->calculateDueAt(Carbon::now());
            }
        }

        $ticket->save();

        if (! empty($data['tags'])) {
            $ticket->tags()->sync($data['tags']);
        }

        return redirect()->route('admin.ai.tickets.show', $ticket)
            ->with('success', __('Le ticket a été créé avec succès.'));
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['replies.user', 'tags', 'slaPolicy', 'user', 'agent']);

        return view('ai::admin.tickets.show', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'status' => 'sometimes|required|in:open,in_progress,waiting_customer,resolved,closed',
            'priority' => 'sometimes|required|in:low,medium,high,urgent',
            'agent_id' => 'nullable|exists:users,id',
            'category' => 'nullable|max:100',
        ]);

        $ticket->update($data);

        return back()->with('success', __('Le ticket a été mis à jour.'));
    }

    public function reply(Request $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validate([
            'content' => 'required|max:5000',
            'is_internal' => 'boolean',
        ]);

        $reply = TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'content' => $data['content'],
            'is_internal' => $request->boolean('is_internal'),
        ]);

        if ($ticket->agent_id
            && (int) $reply->user_id === (int) $ticket->agent_id
            && $ticket->first_response_at === null) {
            $ticket->update(['first_response_at' => now()]);
        }

        return back()->with('success', __('Votre réponse a été ajoutée.'));
    }

    public function close(Ticket $ticket): RedirectResponse
    {
        $ticket->update([
            'status' => TicketStatus::Closed,
            'closed_at' => now(),
        ]);

        return back()->with('success', __('Le ticket a été fermé.'));
    }

    public function resolve(Ticket $ticket): RedirectResponse
    {
        $ticket->update([
            'status' => TicketStatus::Resolved,
            'resolved_at' => now(),
        ]);

        return back()->with('success', __('Le ticket a été résolu.'));
    }

    public function createFromConversation(AiConversation $conversation): RedirectResponse
    {
        $userMessages = $conversation->messages()
            ->where('role', MessageRole::User)
            ->latest()
            ->take(5)
            ->get()
            ->pluck('content')
            ->reverse()
            ->implode("\n\n");

        $ticket = Ticket::create([
            'title' => $conversation->title ?: __('Ticket depuis conversation') . " #{$conversation->id}",
            'description' => $userMessages ?: __('Aucun message utilisateur.'),
            'user_id' => $conversation->user_id,
            'conversation_id' => $conversation->id,
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::Medium,
        ]);

        return redirect()->route('admin.ai.tickets.show', $ticket)
            ->with('success', __('Le ticket a été créé depuis la conversation.'));
    }
}
