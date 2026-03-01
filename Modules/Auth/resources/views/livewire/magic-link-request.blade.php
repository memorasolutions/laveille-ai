@extends('auth::layouts.guest')
@section('title', __('Connexion sans mot de passe'))
@section('content')
<h1 class="auth-title">{{ __('Connexion sans mot de passe') }}</h1>
<p class="auth-subtitle">{{ __('Entrez votre courriel pour recevoir un code à 6 caractères.') }}</p>

@if(session('status'))
    <div class="auth-alert-success" role="alert" style="display:flex;flex-direction:column;gap:0.25rem;">
        <div>{{ session('status') }}</div>
        <a href="{{ route('magic-link.verify') }}?email={{ urlencode(old('email', '')) }}" class="auth-link" style="font-weight:600;">
            {{ __('Saisir mon code') }} &rarr;
        </a>
    </div>
@endif

@if(session('dev_magic_code'))
<div style="background-color:#fef3c7;border:1px solid #f59e0b;border-radius:0.375rem;padding:0.75rem;margin-bottom:1.5rem;">
    <div style="font-weight:600;margin-bottom:0.25rem;">{{ __('DEV - Code magic link :') }}</div>
    <code style="font-size:1.25rem;font-weight:700;letter-spacing:0.3em;">{{ session('dev_magic_code') }}</code>
    <button onclick="navigator.clipboard.writeText('{{ session('dev_magic_code') }}'); this.textContent='{{ __('Copié') }}';" style="background:#f59e0b;color:#000;border:none;border-radius:0.25rem;padding:0.25rem 0.5rem;font-size:0.875rem;margin-left:0.5rem;cursor:pointer;">{{ __('Copier') }}</button>
</div>
@endif

<form action="{{ route('magic-link.send') }}" method="POST">
    @csrf
    <div style="margin-bottom:1.25rem;">
        <label for="magic-email" class="auth-label">{{ __('Courriel') }}</label>
        <div class="auth-input-group">
            <div class="auth-input-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
            </div>
            <input id="magic-email" name="email" type="email" autocomplete="email" class="auth-input" placeholder="{{ __('votre@courriel.com') }}" value="{{ old('email') }}" required autofocus>
        </div>
        @error('email')<p class="auth-error">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="auth-btn">{{ __('Envoyer le code') }}</button>

    <div style="text-align:center;margin-top:1.5rem;">
        <a href="{{ route('login') }}" class="auth-link">{{ __('Retour à la connexion') }}</a>
    </div>
</form>
@endsection
