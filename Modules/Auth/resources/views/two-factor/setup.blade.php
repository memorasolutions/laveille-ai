@extends('auth::layouts.app')

@section('title', __('Configurer la double authentification'))

@section('content')

<div class="mb-4">
    <h1 class="fw-semibold mb-1">{{ __('Double authentification') }}</h1>
    <p class="text-muted mb-0">{{ __('Renforcez la sécurité de votre compte avec l\'authentification à deux facteurs.') }}</p>
</div>

<div class="row gy-4">

    {{-- QR Code + Confirmation --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="qr-code" class="text-primary"></i>
                    {{ __('Scanner le QR code') }}
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted text-sm mb-3">
                    {{ __('Scannez ce code avec Google Authenticator, Authy ou une application compatible TOTP.') }}
                </p>

                <div class="text-center mb-3">
                    <img src="{{ $qrCodeSvg }}" alt="QR Code 2FA"
                         class="rounded border p-2 bg-white"
                         style="width:180px; height:180px;">
                </div>

                <form action="{{ route('user.two-factor.confirm') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="code" class="form-label fw-medium text-muted">{{ __('Code à 6 chiffres') }}</label>
                        <input type="text" id="code" name="code"
                               autocomplete="one-time-code"
                               pattern="[0-9]{6}" maxlength="6"
                               inputmode="numeric"
                               placeholder="000000"
                               class="form-control rounded-2 text-center font-monospace fw-semibold @error('code') is-invalid @enderror"
                               style="font-size:1.25rem; letter-spacing:0.3em;">
                        @error('code')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-primary rounded-2 w-100">
                        <i data-lucide="shield-check"></i>
                        {{ __('Activer la double authentification') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Codes de secours --}}
    @if(!empty($recoveryCodes))
    <div class="col-md-6">
        <div class="card h-100 border border-warning border-opacity-25">
            <div class="card-header">
                <h5 class="card-title fw-semibold mb-0 d-flex align-items-center gap-2">
                    <i data-lucide="key" class="text-warning"></i>
                    {{ __('Codes de secours') }}
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
                    <i data-lucide="alert-triangle" class="flex-shrink-0 mt-1"></i>
                    <p class="mb-0 text-sm">{{ __('Conservez ces codes en lieu sûr. Ils vous permettront de vous connecter si vous perdez accès à votre application authenticator.') }}</p>
                </div>
                <div class="rounded border p-2 mb-2"
                     style="background:#f8fafc; font-family:monospace;">
                    @foreach($recoveryCodes as $code)
                        <div class="text-secondary mb-1" style="user-select:all;">{{ $code }}</div>
                    @endforeach
                </div>
                <p class="text-danger text-sm d-flex align-items-center gap-1 mb-0">
                    <i data-lucide="eye-off"></i>
                    {{ __('Ces codes ne seront plus affichés après cette page.') }}
                </p>
            </div>
        </div>
    </div>
    @endif

</div>

<div class="mt-3">
    <a href="{{ route('user.profile') }}" class="btn btn-outline-secondary rounded-2">
        <i data-lucide="arrow-left"></i>
        {{ __('Retour au profil') }}
    </a>
</div>

@endsection
