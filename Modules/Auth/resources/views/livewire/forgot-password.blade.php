<div>
    <h1 class="auth-title">{{ __('Mot de passe oublié') }}</h1>
    <p class="auth-subtitle">{{ __('Entrez votre courriel et nous vous enverrons un lien de réinitialisation.') }}</p>

    @if($status)
        <div class="auth-alert-success" role="alert">{{ $status }}</div>
    @endif

    <form wire:submit="sendResetLink">
        <div style="margin-bottom:1.25rem;">
            <label for="forgot-email" class="auth-label">{{ __('Courriel') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <input wire:model="email" type="email" id="forgot-email" autocomplete="email" class="auth-input" placeholder="{{ __('votre@courriel.com') }}" required autofocus>
            </div>
            @error('email')<p class="auth-error">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="auth-btn" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('Envoyer le lien') }}</span>
            <span wire:loading>{{ __('Envoi...') }}</span>
        </button>
    </form>

    <div style="text-align:center;margin-top:1.5rem;">
        <a href="{{ route('login') }}" class="auth-link" wire:navigate>{{ __('Retour à la connexion') }}</a>
    </div>
</div>
