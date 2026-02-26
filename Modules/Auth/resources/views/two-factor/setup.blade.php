@extends('auth::layouts.app')

@section('title', __('Configurer la double authentification'))

@section('content')

<div class="mb-24">
    <h1 class="fw-semibold mb-4">{{ __('Double authentification') }}</h1>
    <p class="text-secondary-light mb-0">{{ __('Renforcez la sécurité de votre compte avec l\'authentification à deux facteurs.') }}</p>
</div>

<div class="row gy-4">

    {{-- QR Code + Confirmation --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title fw-semibold text-lg mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:qr-code-outline" class="text-primary-600"></iconify-icon>
                    {{ __('Scanner le QR code') }}
                </h5>
            </div>
            <div class="card-body">
                <p class="text-secondary-light text-sm mb-16">
                    {{ __('Scannez ce code avec Google Authenticator, Authy ou une application compatible TOTP.') }}
                </p>

                <div class="text-center mb-20">
                    <img src="{{ $qrCodeSvg }}" alt="QR Code 2FA"
                         class="rounded border p-8 bg-white"
                         style="width:180px; height:180px;">
                </div>

                <form action="{{ route('user.two-factor.confirm') }}" method="POST">
                    @csrf
                    <div class="mb-16">
                        <label for="code" class="form-label fw-medium text-secondary-light">{{ __('Code à 6 chiffres') }}</label>
                        <input type="text" id="code" name="code"
                               autocomplete="one-time-code"
                               pattern="[0-9]{6}" maxlength="6"
                               inputmode="numeric"
                               placeholder="000000"
                               class="form-control radius-8 text-center font-monospace fw-semibold @error('code') is-invalid @enderror"
                               style="font-size:1.25rem; letter-spacing:0.3em;">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary-600 radius-8 w-100">
                        <iconify-icon icon="solar:shield-check-outline"></iconify-icon>
                        {{ __('Activer la double authentification') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Codes de secours --}}
    @if(!empty($recoveryCodes))
    <div class="col-md-6">
        <div class="card h-100 border border-warning-focus">
            <div class="card-header">
                <h5 class="card-title fw-semibold text-lg mb-0 d-flex align-items-center gap-2">
                    <iconify-icon icon="solar:key-outline" class="text-warning-600"></iconify-icon>
                    {{ __('Codes de secours') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning d-flex align-items-start gap-8 mb-16">
                    <iconify-icon icon="solar:danger-triangle-outline" class="flex-shrink-0 mt-2"></iconify-icon>
                    <p class="mb-0 text-sm">{{ __('Conservez ces codes en lieu sûr. Ils vous permettront de vous connecter si vous perdez accès à votre application authenticator.') }}</p>
                </div>
                <div class="rounded border p-12 mb-12"
                     style="background:#f8fafc; font-family:monospace;">
                    @foreach($recoveryCodes as $code)
                        <div class="text-secondary mb-4" style="user-select:all;">{{ $code }}</div>
                    @endforeach
                </div>
                <p class="text-danger-main text-xs d-flex align-items-center gap-4 mb-0">
                    <iconify-icon icon="solar:eye-closed-outline"></iconify-icon>
                    {{ __('Ces codes ne seront plus affichés après cette page.') }}
                </p>
            </div>
        </div>
    </div>
    @endif

</div>

<div class="mt-20">
    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary radius-8">
        <iconify-icon icon="solar:arrow-left-outline"></iconify-icon>
        {{ __('Retour au profil') }}
    </a>
</div>

@endsection
