<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Messagerie directe (DM) — liste des conversations de l'utilisateur courant,
 * triée par activité récente, avec badge « non lus » par fil. Lecture SEULE
 * scopée à auth()->id() (scopeForUser, anti-IDOR) : jamais de conversation
 * d'un autre utilisateur listée ici, même par erreur de requête.
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\DirectMessages;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Academy\Models\DirectMessage;
use Modules\Academy\Models\DirectMessageConversation;

class ConversationList extends Component
{
    public function mount(): void
    {
        abort_unless(Auth::check(), 403);
        abort_unless((bool) config('academy.direct_messaging_enabled', false), 404);
    }

    /**
     * @return Collection<int, array{conversation: DirectMessageConversation, other: \App\Models\User|null, unread: int, lastMessage: DirectMessage|null}>
     */
    #[Computed]
    public function conversations(): Collection
    {
        $user = Auth::user();

        return DirectMessageConversation::query()
            ->forUser($user)
            ->with(['userOne', 'userTwo', 'course'])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function (DirectMessageConversation $conversation) use ($user): array {
                $unread = DirectMessage::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('recipient_id', $user->id)
                    ->whereNull('read_at')
                    ->count();

                $lastMessage = DirectMessage::query()
                    ->where('conversation_id', $conversation->id)
                    ->latest('id')
                    ->first();

                return [
                    'conversation' => $conversation,
                    'other'        => $conversation->otherParticipant($user),
                    'unread'       => $unread,
                    'lastMessage'  => $lastMessage,
                ];
            });
    }

    #[Computed]
    public function totalUnread(): int
    {
        return $this->conversations->sum('unread');
    }

    public function render()
    {
        return view('academy::livewire.direct-messages.conversation-list');
    }
}
