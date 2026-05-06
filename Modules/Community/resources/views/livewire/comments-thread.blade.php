<div>
    <h5 style="font-family: var(--f-heading); font-weight: 700; color: var(--c-dark);" class="mb-3">
        {{ __('Commentaires') }} ({{ $this->comments->count() }})
    </h5>

    {{-- Formulaire principal --}}
    <div class="p-3 rounded mb-4" style="background: var(--c-primary-light);"
         x-data="{
           hasLinks: false,
           hasHTML: false,
           check(v) {
             this.hasLinks = /https?:\/\/|ftp:\/\/|www\.|\b[a-z0-9-]+\.(com|fr|ca|io|net|org|co|ai)\b/i.test(v || '');
             this.hasHTML = /<[a-z][^>]*>/i.test(v || '');
           }
         }">
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
        <textarea wire:model="newComment"
                  @input="check($el.value)"
                  class="form-control mb-2" rows="3"
                  placeholder="{{ __('Votre commentaire...') }}"
                  aria-describedby="comment-policy-{{ $this->getId() ?? 'main' }}"></textarea>
        @error('newComment') <small class="text-danger">{{ $message }}</small> @enderror

        {{-- #192 : warning temps reel si lien ou HTML detecte --}}
        <div x-show="hasLinks || hasHTML" x-transition.opacity class="alert alert-warning small mb-2 py-2" role="alert">
            ⚠️
            <span x-show="hasLinks && !hasHTML">{{ __('Lien externe détecté') }}</span>
            <span x-show="hasHTML && !hasLinks">{{ __('Code HTML détecté') }}</span>
            <span x-show="hasLinks && hasHTML">{{ __('Lien externe et code HTML détectés') }}</span>
            — {{ __('Les balises HTML seront retirées et le commentaire sera placé en modération avant publication.') }}
        </div>

        <button wire:click="addComment" class="btn btn-sm" wire:loading.attr="disabled" style="background: var(--c-accent); color: #fff;">
            <span wire:loading.remove>{{ __('Envoyer') }}</span>
            <span wire:loading>{{ __('Envoi...') }}</span>
        </button>

        {{-- #192 : politique commentaires sous le bouton --}}
        <p id="comment-policy-{{ $this->getId() ?? 'main' }}" class="form-text small text-muted mt-2 mb-0">
            💡 {{ __('Aucun lien externe ni code HTML accepté. Soyez respectueux. Les commentaires sont modérés avant publication.') }}
        </p>
    </div>

    {{-- Liste des commentaires --}}
    @forelse($this->comments as $comment)
        @include('community::livewire.comment-item', ['comment' => $comment, 'depth' => 0])
    @empty
        <p class="text-muted">{{ __('Aucun commentaire pour le moment. Soyez le premier !') }}</p>
    @endforelse
</div>
