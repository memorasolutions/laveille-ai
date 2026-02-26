<div>
    <div class="d-flex align-items-center gap-2 mb-12">
        <div class="w-40-px h-40-px radius-8 d-flex justify-content-center align-items-center bg-primary-100 flex-shrink-0">
            <iconify-icon icon="solar:shield-keyhole-bold" class="text-primary-600" style="font-size:1.3rem;"></iconify-icon>
        </div>
        <h4 class="mb-0">{{ __('Double authentification') }}</h4>
    </div>

    <p class="text-secondary-light mb-32 text-sm">
        @if ($usingRecoveryCode)
            {{ __('Entrez l\'un de vos codes de récupération.') }}
        @else
            {{ __('Entrez le code à 6 chiffres de votre application d\'authentification.') }}
        @endif
    </p>

    @if (! $usingRecoveryCode)
        <div class="mb-20">
            <label for="code" class="form-label fw-medium text-primary-light">{{ __('Code OTP') }}</label>
            <div class="icon-field">
                <span class="icon top-50 translate-middle-y">
                    <iconify-icon icon="solar:lock-bold"></iconify-icon>
                </span>
                <input
                    wire:model="code"
                    type="text"
                    id="code"
                    inputmode="numeric"
                    maxlength="6"
                    autocomplete="one-time-code"
                    autofocus
                    placeholder="000000"
                    class="form-control h-56-px bg-neutral-50 radius-12 text-center fw-bold @error('code') is-invalid @enderror"
                    style="font-size:1.5rem;letter-spacing:0.4em;font-family:monospace;"
                >
            </div>
            @error('code')<div class="text-danger-main text-sm mt-8">{{ $message }}</div>@enderror
        </div>
    @else
        <div class="mb-20">
            <label for="recoveryCode" class="form-label fw-medium text-primary-light">{{ __('Code de récupération') }}</label>
            <div class="icon-field">
                <span class="icon top-50 translate-middle-y">
                    <iconify-icon icon="solar:key-bold"></iconify-icon>
                </span>
                <input
                    wire:model="recoveryCode"
                    type="text"
                    id="recoveryCode"
                    autofocus
                    placeholder="XXXXXXXX-XXXXXXXX"
                    class="form-control h-56-px bg-neutral-50 radius-12 @error('recoveryCode') is-invalid @enderror"
                    style="font-family:monospace;"
                >
            </div>
            @error('recoveryCode')<div class="text-danger-main text-sm mt-8">{{ $message }}</div>@enderror
        </div>
    @endif

    <button
        wire:click="authenticate"
        wire:loading.attr="disabled"
        type="button"
        class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12"
    >
        <span wire:loading.remove class="d-inline-flex align-items-center gap-1">
            <iconify-icon icon="solar:shield-check-bold"></iconify-icon>
            {{ __('Vérifier') }}
        </span>
        <span wire:loading>{{ __('Vérification en cours...') }}</span>
    </button>

    <div class="text-center mt-24">
        <button
            wire:click="toggleRecoveryMode"
            type="button"
            class="btn btn-link text-primary-600 text-sm p-0 text-decoration-underline d-inline-flex align-items-center gap-1"
        >
            @if ($usingRecoveryCode)
                <iconify-icon icon="solar:smartphone-bold"></iconify-icon>
                {{ __('Utiliser le code OTP') }}
            @else
                <iconify-icon icon="solar:key-bold"></iconify-icon>
                {{ __('Utiliser un code de récupération') }}
            @endif
        </button>
    </div>
</div>
