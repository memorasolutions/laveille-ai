@extends('auth::layouts.guest')
@section('title', __('Vérification de courriel'))
@section('content')
<h1 class="auth-title">{{ __('Vérification de courriel') }}</h1>
<p class="auth-subtitle">{{ __('Nous vous avons envoyé un lien de vérification à votre adresse courriel.') }}<br>{{ __('Vérifiez aussi vos spams.') }}</p>

@if (session('resent'))
    <div class="auth-alert-success" role="alert">{{ __('Courriel de vérification renvoyé !') }}</div>
@endif

<form method="POST" action="{{ route('verification.send') }}" style="margin-bottom:1rem;">
    @csrf
    <button type="submit" class="auth-btn">{{ __('Renvoyer le courriel') }}</button>
</form>

<div style="text-align:center;">
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" class="auth-link" style="background:none;border:none;cursor:pointer;padding:0;">{{ __('Déconnexion') }}</button>
    </form>
</div>
@endsection
