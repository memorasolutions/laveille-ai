<div>
    <h1 class="auth-title">{{ __('Double authentification') }}</h1>
    <p class="auth-subtitle">
        @if ($usingRecoveryCode)
            {{ __('Entrez l\'un de vos codes de récupération.') }}
        @else
            {{ __('Entrez le code à 6 chiffres de votre application d\'authentification.') }}
        @endif
    </p>

    @if (! $usingRecoveryCode)
        <div style="margin-bottom:1.25rem;">
            <label for="code" class="auth-label">{{ __('Code OTP') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                </div>
                <input wire:model="code" type="text" id="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus placeholder="000000" class="auth-input" style="text-align:center;font-weight:700;font-size:1.5rem;letter-spacing:0.4em;font-family:monospace;">
            </div>
            @error('code')<p class="auth-error">{{ $message }}</p>@enderror
        </div>
    @else
        <div style="margin-bottom:1.25rem;">
            <label for="recoveryCode" class="auth-label">{{ __('Code de récupération') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <input wire:model="recoveryCode" type="text" id="recoveryCode" autofocus placeholder="XXXXXXXX-XXXXXXXX" class="auth-input" style="font-family:monospace;">
            </div>
            @error('recoveryCode')<p class="auth-error">{{ $message }}</p>@enderror
        </div>
    @endif

    <button wire:click="authenticate" wire:loading.attr="disabled" type="button" class="auth-btn">
        <span wire:loading.remove>{{ __('Vérifier') }}</span>
        <span wire:loading>{{ __('Vérification en cours...') }}</span>
    </button>

    <div style="text-align:center;margin-top:1.5rem;">
        <button wire:click="toggleRecoveryMode" type="button" class="auth-link" style="background:none;border:none;cursor:pointer;padding:0;text-decoration:underline;">
            @if ($usingRecoveryCode)
                {{ __('Utiliser le code OTP') }}
            @else
                {{ __('Utiliser un code de récupération') }}
            @endif
        </button>
    </div>
</div>
