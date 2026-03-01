@extends('auth::layouts.guest')
@section('title', __('Saisir votre code'))
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <div class="rounded-2 d-flex justify-content-center align-items-center bg-primary bg-opacity-10 flex-shrink-0" style="width:40px;height:40px;">
        <i data-lucide="hash" class="text-primary"></i>
    </div>
    <div>
        <h4 class="mb-0">{{ __('Saisir votre code') }}</h4>
        <p class="text-muted mb-0 small">{{ __('Entrez le code à 6 chiffres reçu.') }}</p>
    </div>
</div>
@if(session('status'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i data-lucide="check-circle" class="text-success"></i>
        <span>{{ session('status') }}</span>
    </div>
@endif
@if(session('sms_sent'))
    <div class="alert alert-info d-flex align-items-center gap-2 mb-3">
        <i data-lucide="phone" class="text-info"></i>
        <span>{{ session('sms_sent') }}</span>
    </div>
@endif
@if($errors->has('sms'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i data-lucide="alert-triangle" class="text-danger"></i>
        <span>{{ $errors->first('sms') }}</span>
    </div>
@endif
@if($errors->has('token'))
    <div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
        <i data-lucide="alert-triangle" class="text-danger"></i>
        <span>{{ $errors->first('token') }}</span>
    </div>
@endif
<form action="{{ route('magic-link.confirm') }}" method="POST">
    @csrf
    <div class="input-group mb-3">
        <span class="input-group-text"><i data-lucide="mail"></i></span>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $email) }}" required>
    </div>
    @error('email')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
    <div class="mb-3">
        <label for="token" class="form-label fw-medium text-muted">{{ __('Code de connexion') }}</label>
        <input id="token" name="token" type="text" inputmode="numeric" pattern="[0-9]*" class="form-control text-center fw-bold @error('token') is-invalid @enderror" style="font-size:1.5rem;letter-spacing:0.4em;font-family:monospace;" placeholder="000000" maxlength="6" value="{{ old('token') }}" required autofocus>
        <div class="form-text text-center text-muted">{{ __('Code valide') }} {{ $expiryMinutes ?? 15 }} {{ __('minutes') }}</div>
    </div>
    @error('token')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
    <button type="submit" class="btn btn-primary w-100 py-3 d-flex align-items-center justify-content-center gap-1">
        <i data-lucide="log-in"></i>{{ __('Se connecter') }}
    </button>
    @if($hasPhone ?? false)
        <div class="border-top mt-4 pt-4"
             x-data="{ countdown: {{ $smsButtonDelay ?? 10 }}, ready: false }"
             x-init="if(countdown > 0) { let t = setInterval(() => { countdown--; if(countdown <= 0) { ready = true; clearInterval(t); } }, 1000); } else { ready = true; }">
            <p class="text-center text-muted small mb-3">{{ __('Vous n\'avez pas reçu le code ?') }}</p>
            <div x-show="!ready" class="text-center">
                <span class="text-muted small">
                    <i data-lucide="phone" style="width:16px;height:16px;"></i>
                    {{ __('SMS disponible dans') }} <span x-text="countdown" class="fw-bold text-primary"></span> {{ __('secondes') }}
                </span>
            </div>
            <div x-show="ready" x-cloak>
                <form action="{{ route('magic-link.sms') }}" method="POST">
                    @csrf
                    <input type="hidden" name="email" value="{{ old('email', $email) }}">
                    <button type="submit" class="btn btn-outline-primary w-100 d-flex align-items-center justify-content-center gap-1">
                        <i data-lucide="phone"></i>{{ __('Recevoir par SMS') }}
                    </button>
                </form>
            </div>
        </div>
    @endif
    <div class="text-center mt-4">
        <a href="{{ route('magic-link.request') }}" class="text-primary text-decoration-underline d-inline-flex align-items-center gap-1">
            <i data-lucide="arrow-left"></i>{{ __('Demander un nouveau code') }}
        </a>
    </div>
</form>
@endsection
