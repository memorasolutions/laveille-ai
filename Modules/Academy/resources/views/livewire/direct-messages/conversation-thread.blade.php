{{--
    Author: MEMORA solutions, https://memora.solutions

    Messagerie directe (DM) - fil de messages d'une conversation. Pattern
    aria-live="polite" + role="log" + auto-scroll repris tel quel du ChatBot
    (Modules/AI/resources/views/livewire/chatbot.blade.php) pour rester cohérent
    avec l'existant. Contraste WCAG AAA : texte #1A1D23 sur fond blanc/#F0FDFA.
--}}
<div>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <a href="{{ route('academy.messages.index') }}" wire:navigate style="color: #064E5A; text-decoration: none; font-size: 0.9rem;">
                &larr; Retour aux messages
            </a>
            <h2 style="font-family: var(--f-heading); color: var(--sys-text-default, #1A1D23); font-size: 1.25rem; margin: 4px 0 0;">
                {{ $this->otherParticipant?->name ?? 'Utilisateur retiré' }}
            </h2>
            <span style="color: var(--sys-text-muted, #6B7280); font-size: 0.9rem;">
                {{ $this->conversation->course?->title }}
            </span>
        </div>
    </div>

    <div
        role="log"
        aria-live="polite"
        aria-label="Fil de messages"
        wire:poll.5s="$refresh"
        style="max-height: 480px; overflow-y: auto; padding: 16px; border: 1px solid #E5E7EB; border-radius: 8px; background-color: #FAFAFA; display: flex; flex-direction: column; gap: 10px;"
        x-init="
            const el = $el;
            const scroll = () => el.scrollTop = el.scrollHeight;
            scroll();
            new MutationObserver(scroll).observe(el, { childList: true, subtree: true, characterData: true });
        "
    >
        @forelse($this->threadMessages as $message)
            @php($isMine = $message->sender_id === auth()->id())
            <div style="display: flex; {{ $isMine ? 'justify-content: flex-end;' : 'justify-content: flex-start;' }}">
                <div style="max-width: 75%; padding: 10px 14px; border-radius: 10px; background-color: {{ $isMine ? '#064E5A' : '#fff' }}; color: {{ $isMine ? '#fff' : '#1A1D23' }}; border: 1px solid {{ $isMine ? '#064E5A' : '#E5E7EB' }};">
                    <div style="font-size: 0.95rem; line-height: 1.5; white-space: pre-wrap;">{{ $message->body }}</div>
                    <div style="font-size: 0.7rem; margin-top: 4px; opacity: 0.8;">
                        {{ $message->created_at?->timezone('America/Toronto')->format('H\hi') }}
                        @if($isMine && $message->read_at)
                            &middot; Lu
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p style="color: var(--sys-text-muted, #6B7280); text-align: center;">Aucun message pour l'instant. Écrivez le premier !</p>
        @endforelse
    </div>

    @if($errorMessage)
        <div role="alert" style="margin-top: 10px; padding: 10px 14px; border-radius: 8px; background-color: #FEF2F2; color: #991B1B; border: 1px solid #FCA5A5;">
            {{ $errorMessage }}
        </div>
    @endif

    <form wire:submit.prevent="sendMessage" class="mt-3">
        <label for="dm-body" class="visually-hidden">Votre message</label>
        <div class="d-flex" style="gap: 8px;">
            <textarea
                id="dm-body"
                wire:model="body"
                rows="2"
                maxlength="{{ \Modules\Academy\Models\DirectMessage::MAX_LENGTH }}"
                placeholder="Écrire un message..."
                style="flex: 1; padding: 10px 14px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 0.95rem; resize: vertical;"
                @keydown.enter.prevent.exact="$wire.sendMessage()"
            ></textarea>
            <button type="submit"
                    wire:loading.attr="disabled"
                    class="btn"
                    style="background-color: #064E5A; color: #fff; border-radius: 8px; padding: 0 20px; align-self: flex-end; height: 44px;">
                Envoyer
            </button>
        </div>
        @error('body')
            <span role="alert" style="color: #991B1B; font-size: 0.85rem;">{{ $message }}</span>
        @enderror
    </form>
</div>
