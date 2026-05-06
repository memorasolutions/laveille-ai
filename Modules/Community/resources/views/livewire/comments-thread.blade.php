<div>
    <h5 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark);" class="mb-3">
        {{ __('Commentaires') }} ({{ $this->comments->count() }})
    </h5>

    {{-- Formulaire principal --}}
    <div class="p-3 rounded mb-4" style="background: var(--c-primary-light);">
        @if($showSuccess)
            <div class="alert alert-success mb-3" role="status" aria-live="polite">
                <strong>✓</strong> {{ $successMessage }}
            </div>
        @endif
        @guest
            <div class="mb-2">
                <input type="text" wire:model="guestName" class="form-control" placeholder="{{ __('Votre nom') }}">
                @error('guestName') <small class="text-danger">{{ $message }}</small> @enderror
            </div>
        @endguest
        <textarea wire:model="newComment" class="form-control mb-2" rows="3" placeholder="{{ __('Votre commentaire...') }}"></textarea>
        @error('newComment') <small class="text-danger">{{ $message }}</small> @enderror
        <button wire:click="addComment" class="btn btn-sm" wire:loading.attr="disabled" style="background: var(--c-accent); color: #fff;">
            <span wire:loading.remove>{{ __('Envoyer') }}</span>
            <span wire:loading>{{ __('Envoi...') }}</span>
        </button>
    </div>

    {{-- Liste des commentaires --}}
    @forelse($this->comments as $comment)
        @include('community::livewire.comment-item', ['comment' => $comment, 'depth' => 0])
    @empty
        <p class="text-muted">{{ __('Aucun commentaire pour le moment. Soyez le premier !') }}</p>
    @endforelse
</div>
