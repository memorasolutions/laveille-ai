<div>
    <h2 class="h2 text-dark">{{ __('Mot de passe oublié') }}</h2>
    <p class="mt-2 text-muted">{{ __('Entrez votre courriel et nous vous enverrons un lien de réinitialisation.') }}</p>
    @if($status)
        <div class="alert alert-success mt-4 mb-3">{{ $status }}</div>
    @endif
    <form wire:submit="sendResetLink" class="mt-5">
        <div class="mb-4">
            <label class="form-label">{{ __('Courriel') }}</label>
            <div class="input-group @error('email') is-invalid @enderror">
                <span class="input-group-text">
                    <i data-lucide="at-sign" aria-hidden="true"></i>
                </span>
                <input wire:model="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="{{ __('Courriel') }}" required autofocus>
            </div>
            @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" class="btn btn-primary w-100 py-3 mb-4">
            <span wire:loading.remove>{{ __('Envoyer le lien') }}</span>
            <span wire:loading>{{ __('Envoi...') }}</span>
        </button>
        <div class="text-center">
            <a href="{{ route('login') }}" class="text-decoration-none text-primary" wire:navigate>{{ __('Retour à la connexion') }}</a>
        </div>
    </form>
</div>
