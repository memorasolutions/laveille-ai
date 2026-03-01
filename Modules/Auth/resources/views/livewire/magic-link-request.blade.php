@extends('auth::layouts.guest')
@section('title', __('Connexion sans mot de passe'))
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <div class="rounded-2 d-flex justify-content-center align-items-center bg-primary bg-opacity-10 flex-shrink-0" style="width:40px;height:40px;">
        <i data-lucide="wand-2" class="text-primary"></i>
    </div>
    <div>
        <h4 class="mb-0">{{ __('Connexion sans mot de passe') }}</h4>
        <p class="text-muted mb-0 small">{{ __('Entrez votre courriel pour recevoir un code à 6 caractères.') }}</p>
    </div>
</div>
@if(session('status'))
    <div class="alert alert-success d-flex flex-column gap-1 mb-3">
        <div>{{ session('status') }}</div>
        <a href="{{ route('magic-link.verify') }}?email={{ urlencode(old('email', '')) }}" class="fw-semibold text-success text-decoration-underline">
            {{ __('Saisir mon code') }} &rarr;
        </a>
    </div>
@endif
@if(session('dev_magic_code'))
<div class="alert alert-warning mb-3">
    <div class="d-flex align-items-center gap-2 fw-semibold mb-1">
        <i data-lucide="bug"></i>
        <span>{{ __('DEV - Code magic link :') }}</span>
    </div>
    <code class="fs-4 fw-bold" style="letter-spacing:0.3em;">{{ session('dev_magic_code') }}</code>
    <button onclick="navigator.clipboard.writeText('{{ session('dev_magic_code') }}'); this.textContent='{{ __('Copié') }} ✓';"
            class="btn btn-warning btn-sm ms-2">
        {{ __('Copier') }}
    </button>
</div>
@endif
<form action="{{ route('magic-link.send') }}" method="POST">
    @csrf
    <div class="input-group mb-3">
        <span class="input-group-text"><i data-lucide="mail"></i></span>
        <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" placeholder="vous@exemple.com" value="{{ old('email') }}" required autofocus>
    </div>
    @error('email')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
    <button type="submit" class="btn btn-primary w-100 py-3 d-flex align-items-center justify-content-center gap-1">
        <i data-lucide="send"></i>{{ __('Envoyer le code') }}
    </button>
    <div class="text-center mt-4">
        <a href="{{ route('login') }}" class="text-primary text-decoration-underline d-inline-flex align-items-center gap-1">
            <i data-lucide="arrow-left"></i>{{ __('Retour à la connexion') }}
        </a>
    </div>
</form>
@endsection
