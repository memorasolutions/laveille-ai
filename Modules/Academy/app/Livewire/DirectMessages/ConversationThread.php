<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 *
 * Messagerie directe (DM) — fil de messages d'une conversation. IDOR : mount()
 * vérifie EXPLICITEMENT (abort_if, pattern Modules/Authors) que l'utilisateur
 * connecté est bien l'un des DEUX participants avant toute lecture, en plus de
 * la Policy DirectMessageConversationPolicy (défense en profondeur — la policy
 * seule ne suffit pas si un futur appel oublie authorize()).
 */

declare(strict_types=1);

namespace Modules\Academy\Livewire\DirectMessages;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Modules\Academy\Models\DirectMessage;
use Modules\Academy\Models\DirectMessageConversation;
use Modules\Academy\Services\DirectMessageService;

class ConversationThread extends Component
{
    #[Locked]
    public int $conversationId;

    public string $body = '';

    public ?string $errorMessage = null;

    public function mount(DirectMessageConversation $conversation): void
    {
        abort_unless(Auth::check(), 403);
        abort_unless((bool) config('academy.direct_messaging_enabled', false), 404);

        $user = Auth::user();

        // ANTI-IDOR : abort_if explicite (pattern Modules/Authors), en plus de la
        // Policy — jamais de lecture d'un fil dont on n'est pas participant.
        abort_if(! $conversation->hasParticipant($user), 403, "Vous ne participez pas à cette conversation.");

        $this->conversationId = $conversation->id;

        app(DirectMessageService::class)->markConversationRead($conversation, $user);
    }

    #[Computed]
    public function conversation(): DirectMessageConversation
    {
        $conversation = DirectMessageConversation::query()->with(['userOne', 'userTwo', 'course'])->findOrFail($this->conversationId);

        // Re-vérification à CHAQUE accès computed (anti-IDOR, défense en profondeur :
        // une propriété Computed peut être ré-évaluée après un changement d'état).
        abort_if(! $conversation->hasParticipant(Auth::user()), 403);

        return $conversation;
    }

    #[Computed]
    public function otherParticipant()
    {
        return $this->conversation->otherParticipant(Auth::user());
    }

    /**
     * ACTION: renommé messages() -> threadMessages() SELF: 1 ligne RAISON: collision
     * avec Livewire\Features\SupportValidation\HandlesValidation::messages() (messages
     * de validation custom), qui provoquait un TypeError array_merge() lors de tout
     * appel à $this->validate() une fois qu'un Computed public "messages" existait.
     *
     * @return Collection<int, DirectMessage>
     */
    #[Computed]
    public function threadMessages(): Collection
    {
        return DirectMessage::query()
            ->where('conversation_id', $this->conversationId)
            ->with(['sender'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    public function sendMessage(): void
    {
        $this->errorMessage = null;

        $user = Auth::user();
        $conversation = $this->conversation; // relance abort_if si besoin (anti-IDOR)

        $this->validate([
            'body' => ['required', 'string', 'min:1', 'max:' . DirectMessage::MAX_LENGTH],
        ]);

        // Rate-limit anti-spam (défense en profondeur, en plus de celui du service).
        $rateLimitKey = 'academy_dm_ui:' . $user->id;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $this->errorMessage = 'Vous envoyez des messages trop rapidement. Attendez une minute.';

            return;
        }
        RateLimiter::hit($rateLimitKey, 60);

        $message = app(DirectMessageService::class)->send($conversation, $user, $this->body);

        if ($message === null) {
            $this->errorMessage = "Ce message n'a pas pu être envoyé (relation pédagogique introuvable ou limite atteinte).";

            return;
        }

        $this->body = '';
        unset($this->threadMessages);
    }

    public function render()
    {
        return view('academy::livewire.direct-messages.conversation-thread');
    }
}
