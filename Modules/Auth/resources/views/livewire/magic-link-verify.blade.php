<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.guest')
@section('title', __('Saisir votre code'))
@section('content')
<h1 class="auth-title">{{ __('Saisir votre code') }}</h1>
<p class="auth-subtitle">{{ __('Entrez le code à 6 chiffres reçu par courriel.') }}</p>

@if(session('status'))
    <div class="auth-alert-success" role="alert">{{ session('status') }}</div>
@endif
@if(session('sms_sent'))
    <div class="auth-alert-success" role="alert">{{ session('sms_sent') }}</div>
@endif
@if($errors->has('sms'))
    <div class="auth-alert-error" role="alert">{{ $errors->first('sms') }}</div>
@endif
@if($errors->has('token'))
    <div class="auth-alert-error" role="alert">{{ $errors->first('token') }}</div>
@endif

<form action="{{ route('magic-link.confirm') }}" method="POST">
    @csrf
    <div style="margin-bottom:1.25rem;">
        <label for="verify-email" class="auth-label">{{ __('Courriel') }}</label>
        <div class="auth-input-group">
            <div class="auth-input-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </div>
            <input id="verify-email" name="email" type="email" autocomplete="email" class="auth-input" value="{{ old('email', $email) }}" required>
        </div>
        @error('email')<p class="auth-error">{{ $message }}</p>@enderror
    </div>

    <div style="margin-bottom:1.25rem;">
        <label for="token" class="auth-label">{{ __('Code de connexion') }}</label>
        <input id="token" name="token" type="text" inputmode="numeric" pattern="[0-9]*" class="auth-input" style="text-align:center;font-weight:700;font-size:1.5rem;letter-spacing:0.4em;font-family:monospace;padding-inline-start:1rem;" placeholder="000000" maxlength="6" value="{{ old('token') }}" required autofocus>
        <p class="auth-text-muted" style="text-align:center;font-size:0.85rem;margin-top:0.5rem;">{{ __('Code valide') }} {{ $expiryMinutes ?? 15 }} {{ __('minutes') }}</p>
        @error('token')<p class="auth-error">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="auth-btn">{{ __('Se connecter') }}</button>

    @if($hasPhone ?? false)
        <div style="border-top:1px solid #e5e7eb;margin-top:1.5rem;padding-top:1.5rem;"
             x-data="{ countdown: {{ $smsButtonDelay ?? 10 }}, ready: false }"
             x-init="if(countdown > 0) { let t = setInterval(() => { countdown--; if(countdown <= 0) { ready = true; clearInterval(t); } }, 1000); } else { ready = true; }">
            <p class="auth-text-muted" style="text-align:center;font-size:0.85rem;margin-bottom:0.75rem;">{{ __('Vous n\'avez pas reçu le code ?') }}</p>
            <div x-show="!ready" style="text-align:center;">
                <span class="auth-text-muted" style="font-size:0.85rem;">
                    {{ __('SMS disponible dans') }} <span x-text="countdown" style="font-weight:700;color:#0284c7;"></span> {{ __('secondes') }}
                </span>
            </div>
            <div x-show="ready" x-cloak>
                <form action="{{ route('magic-link.sms') }}" method="POST">
                    @csrf
                    <input type="hidden" name="email" value="{{ old('email', $email) }}">
                    <button type="submit" class="auth-btn" style="background:transparent;color:#0284c7;border:2px solid #e5e7eb;">{{ __('Recevoir par SMS') }}</button>
                </form>
            </div>
        </div>
    @endif

    <div style="text-align:center;margin-top:1.5rem;">
        <a href="{{ route('magic-link.request') }}" class="auth-link">{{ __('Demander un nouveau code') }}</a>
    </div>
</form>
@endsection
