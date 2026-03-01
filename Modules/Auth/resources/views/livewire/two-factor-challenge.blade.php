<div>
    <div class="d-flex align-items-center gap-2 mb-2">
        <div class="rounded-2 d-flex justify-content-center align-items-center bg-primary bg-opacity-10 flex-shrink-0" style="width:40px;height:40px;">
            <i data-lucide="shield" class="text-primary"></i>
        </div>
        <h4 class="mb-0">{{ __('Double authentification') }}</h4>
    </div>
    <p class="text-muted mb-5 small">
        @if ($usingRecoveryCode)
            {{ __('Entrez l\'un de vos codes de récupération.') }}
        @else
            {{ __('Entrez le code à 6 chiffres de votre application d\'authentification.') }}
        @endif
    </p>
    @if (! $usingRecoveryCode)
        <div class="mb-3">
            <label for="code" class="form-label fw-medium text-muted">{{ __('Code OTP') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i data-lucide="lock"></i></span>
                <input wire:model="code" type="text" id="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus placeholder="000000" class="form-control text-center fw-bold @error('code') is-invalid @enderror" style="font-size:1.5rem;letter-spacing:0.4em;font-family:monospace;">
            </div>
            @error('code')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>
    @else
        <div class="mb-3">
            <label for="recoveryCode" class="form-label fw-medium text-muted">{{ __('Code de récupération') }}</label>
            <div class="input-group">
                <span class="input-group-text"><i data-lucide="key"></i></span>
                <input wire:model="recoveryCode" type="text" id="recoveryCode" autofocus placeholder="XXXXXXXX-XXXXXXXX" class="form-control @error('recoveryCode') is-invalid @enderror" style="font-family:monospace;">
            </div>
            @error('recoveryCode')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>
    @endif
    <button wire:click="authenticate" wire:loading.attr="disabled" type="button" class="btn btn-primary w-100 py-3">
        <span wire:loading.remove class="d-inline-flex align-items-center gap-1"><i data-lucide="shield-check"></i>{{ __('Vérifier') }}</span>
        <span wire:loading>{{ __('Vérification en cours...') }}</span>
    </button>
    <div class="text-center mt-4">
        <button wire:click="toggleRecoveryMode" type="button" class="btn btn-link text-primary p-0 text-decoration-underline d-inline-flex align-items-center gap-1">
            @if ($usingRecoveryCode)
                <i data-lucide="smartphone"></i>{{ __('Utiliser le code OTP') }}
            @else
                <i data-lucide="key"></i>{{ __('Utiliser un code de récupération') }}
            @endif
        </button>
    </div>
</div>
