<div class="mb-3" style="margin-left: {{ $depth * 40 }}px;">
    <div class="p-3 rounded" style="background: {{ $depth > 0 ? '#f8f9fa' : 'var(--c-primary-light)' }};">
        <div class="d-flex align-items-center mb-2">
            {{-- Avatar initiale --}}
            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--c-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; flex-shrink: 0;" class="me-2">
                {{ strtoupper(substr($comment->user?->name ?? $comment->guest_name ?? '?', 0, 1)) }}
            </div>
            <div>
                <strong style="font-size: 0.9rem;">{{ $comment->user?->name ?? $comment->guest_name }}</strong>
                <small class="text-muted ms-2">{{ $comment->created_at->diffForHumans() }}</small>
            </div>
        </div>
        <p class="mb-2" style="font-size: 0.95rem;">{{ $comment->content }}</p>
        <div style="font-size: 0.8rem;">
            @if($depth < 3)
                <button wire:click="reply({{ $comment->id }})" class="btn btn-sm btn-link p-0" style="color: var(--c-accent); font-size: 0.8rem;">{{ __('Répondre') }}</button>
            @endif
            @auth
                @if($comment->user_id === auth()->id())
                    <button wire:click="deleteComment({{ $comment->id }})" class="btn btn-sm btn-link p-0 ms-2" style="color: #DC2626; font-size: 0.8rem;">{{ __('Supprimer') }}</button>
                @endif
            @endauth
        </div>
    </div>

    {{-- Formulaire de réponse --}}
    @if($replyingTo === $comment->id)
        <div class="mt-2 p-2 rounded" style="margin-left: 40px; background: #f8f9fa;">
            @guest
                <input type="text" wire:model="guestName" class="form-control form-control-sm mb-1" placeholder="{{ __('Votre nom') }}">
            @endguest
            <textarea wire:model="newComment" class="form-control form-control-sm mb-1" rows="2" placeholder="{{ __('Votre réponse...') }}"></textarea>
            <button wire:click="addComment" class="btn btn-sm" style="background: var(--c-accent); color: #fff;">{{ __('Envoyer') }}</button>
            <button wire:click="cancelReply" class="btn btn-sm btn-outline-secondary">{{ __('Annuler') }}</button>
        </div>
    @endif

    {{-- Réponses enfants --}}
    @foreach($comment->children as $child)
        @include('community::livewire.comment-item', ['comment' => $child, 'depth' => $depth + 1])
    @endforeach
</div>
