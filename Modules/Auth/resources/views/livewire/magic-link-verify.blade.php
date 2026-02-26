@extends('auth::layouts.guest')

@section('title', __('Saisir votre code'))

@section('content')
<div class="d-flex align-items-center gap-2 mb-24">
    <div class="w-40-px h-40-px radius-8 d-flex justify-content-center align-items-center bg-primary-100 flex-shrink-0">
        <iconify-icon icon="solar:code-bold" class="text-primary-600" style="font-size:1.3rem;"></iconify-icon>
    </div>
    <div>
        <h4 class="mb-0">{{ __('Saisir votre code') }}</h4>
        <p class="text-secondary-light mb-0 text-sm">{{ __('Entrez le code à 6 chiffres reçu.') }}</p>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success radius-8 mb-20 d-flex align-items-center gap-2">
        <iconify-icon icon="solar:check-circle-bold" class="text-success-main"></iconify-icon>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if(session('sms_sent'))
    <div class="alert alert-info radius-8 mb-20 d-flex align-items-center gap-2">
        <iconify-icon icon="solar:phone-bold" class="text-info-main"></iconify-icon>
        <span>{{ session('sms_sent') }}</span>
    </div>
@endif

@if($errors->has('sms'))
    <div class="alert alert-danger radius-8 mb-20 d-flex align-items-center gap-2">
        <iconify-icon icon="solar:danger-triangle-bold" class="text-danger-main"></iconify-icon>
        <span>{{ $errors->first('sms') }}</span>
    </div>
@endif

@if($errors->has('token'))
    <div class="alert alert-danger radius-8 mb-20 d-flex align-items-center gap-2">
        <iconify-icon icon="solar:danger-triangle-bold" class="text-danger-main"></iconify-icon>
        <span>{{ $errors->first('token') }}</span>
    </div>
@endif

<form action="{{ route('magic-link.confirm') }}" method="POST">
    @csrf

    <div class="icon-field mb-16">
        <span class="icon top-50 translate-middle-y">
            <iconify-icon icon="mage:email"></iconify-icon>
        </span>
        <input
            id="email"
            name="email"
            type="email"
            class="form-control h-56-px bg-neutral-50 radius-12 @error('email') is-invalid @enderror"
            value="{{ old('email', $email) }}"
            required
        >
    </div>
    @error('email')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

    <div class="mb-20">
        <label for="token" class="form-label fw-medium text-primary-light">{{ __('Code de connexion') }}</label>
        <input
            id="token"
            name="token"
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            class="form-control h-56-px bg-neutral-50 radius-12 text-center fw-bold @error('token') is-invalid @enderror"
            style="font-size:1.5rem;letter-spacing:0.4em;font-family:monospace;"
            placeholder="000000"
            maxlength="6"
            value="{{ old('token') }}"
            required
            autofocus
        >
        <div class="form-text text-center text-secondary-light">{{ __('Code valide') }} {{ $expiryMinutes ?? 15 }} {{ __('minutes') }}</div>
    </div>
    @error('token')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

    <button type="submit" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 d-flex align-items-center justify-content-center gap-1">
        <iconify-icon icon="solar:login-3-bold"></iconify-icon>
        {{ __('Se connecter') }}
    </button>

    @if($hasPhone ?? false)
        <div class="border-top border-neutral-200 mt-20 pt-20"
             x-data="{ countdown: {{ $smsButtonDelay ?? 10 }}, ready: false }"
             x-init="if(countdown > 0) { let t = setInterval(() => { countdown--; if(countdown <= 0) { ready = true; clearInterval(t); } }, 1000); } else { ready = true; }">

            <p class="text-center text-secondary-light text-sm mb-12">{{ __('Vous n\'avez pas reçu le code ?') }}</p>

            <div x-show="!ready" class="text-center">
                <span class="text-secondary-light text-sm">
                    <iconify-icon icon="solar:phone-outline" class="text-lg align-middle"></iconify-icon>
                    {{ __('SMS disponible dans') }} <span x-text="countdown" class="fw-bold text-primary-600"></span> {{ __('secondes') }}
                </span>
            </div>

            <div x-show="ready" x-cloak>
                <form action="{{ route('magic-link.sms') }}" method="POST">
                    @csrf
                    <input type="hidden" name="email" value="{{ old('email', $email) }}">
                    <button type="submit" class="btn btn-outline-primary text-sm btn-sm px-12 py-12 w-100 radius-12 d-flex align-items-center justify-content-center gap-1">
                        <iconify-icon icon="solar:phone-outline"></iconify-icon>
                        {{ __('Recevoir par SMS') }}
                    </button>
                </form>
            </div>
        </div>
    @endif

    <div class="text-center mt-24">
        <a href="{{ route('magic-link.request') }}" class="text-primary-600 text-decoration-underline d-inline-flex align-items-center gap-1">
            <iconify-icon icon="solar:arrow-left-bold"></iconify-icon>
            {{ __('Demander un nouveau code') }}
        </a>
    </div>
</form>
@endsection
