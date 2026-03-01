<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
@extends('auth::layouts.guest')
@section('title', __('Confirmation requise'))
@section('content')
<h1 class="auth-title">{{ __('Confirmation requise') }}</h1>
<p class="auth-subtitle">{{ __('Cette action nécessite de confirmer votre mot de passe pour continuer.') }}</p>

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    <div style="margin-bottom:1.25rem;">
        <label for="confirm-password" class="auth-label">{{ __('Mot de passe') }}</label>
        <div class="auth-input-group">
            <div class="auth-input-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <input id="confirm-password" type="password" name="password" autocomplete="current-password" class="auth-input" placeholder="{{ __('Votre mot de passe') }}" required>
        </div>
        @error('password')<p class="auth-error">{{ $message }}</p>@enderror
    </div>

    <button type="submit" class="auth-btn">{{ __('Confirmer') }}</button>
</form>

<div style="text-align:center;margin-top:1.5rem;">
    <a href="{{ route('user.dashboard') }}" class="auth-link">{{ __('Retour au tableau de bord') }}</a>
</div>
@endsection
