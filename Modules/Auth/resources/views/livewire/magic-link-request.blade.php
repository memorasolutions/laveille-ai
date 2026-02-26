@extends('auth::layouts.guest')

@section('title', __('Connexion sans mot de passe'))

@section('content')
<div class="d-flex align-items-center gap-2 mb-24">
    <div class="w-40-px h-40-px radius-8 d-flex justify-content-center align-items-center bg-primary-100 flex-shrink-0">
        <iconify-icon icon="solar:magic-stick-bold" class="text-primary-600" style="font-size:1.3rem;"></iconify-icon>
    </div>
    <div>
        <h4 class="mb-0">{{ __('Connexion sans mot de passe') }}</h4>
        <p class="text-secondary-light mb-0 text-sm">{{ __('Entrez votre courriel pour recevoir un code à 6 caractères.') }}</p>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-success d-flex flex-column gap-1 radius-8 mb-20">
        <div>{{ session('status') }}</div>
        <a href="{{ route('magic-link.verify') }}?email={{ urlencode(old('email', '')) }}" class="fw-semibold text-success-main text-decoration-underline">
            {{ __('Saisir mon code') }} →
        </a>
    </div>
@endif

@if(session('dev_magic_code'))
<div class="alert alert-warning radius-8 mb-20">
    <div class="d-flex align-items-center gap-2 fw-semibold mb-4">
        <iconify-icon icon="solar:bug-bold"></iconify-icon>
        <span>{{ __('DEV - Code magic link :') }}</span>
    </div>
    <code class="fs-4 fw-bold" style="letter-spacing:0.3em;">{{ session('dev_magic_code') }}</code>
    <button onclick="navigator.clipboard.writeText('{{ session('dev_magic_code') }}'); this.textContent='Copié ✓';"
            class="btn btn-warning btn-sm ms-2 radius-4 py-0">
        {{ __('Copier') }}
    </button>
</div>
@endif

<form action="{{ route('magic-link.send') }}" method="POST">
    @csrf
    <div class="icon-field mb-20">
        <span class="icon top-50 translate-middle-y">
            <iconify-icon icon="mage:email"></iconify-icon>
        </span>
        <input
            id="email"
            name="email"
            type="email"
            class="form-control h-56-px bg-neutral-50 radius-12 @error('email') is-invalid @enderror"
            placeholder="vous@exemple.com"
            value="{{ old('email') }}"
            required
            autofocus
        >
    </div>
    @error('email')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

    <button type="submit" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 d-flex align-items-center justify-content-center gap-1">
        <iconify-icon icon="solar:letter-send-bold"></iconify-icon>
        {{ __('Envoyer le code') }}
    </button>

    <div class="text-center mt-24">
        <a href="{{ route('login') }}" class="text-primary-600 text-decoration-underline d-inline-flex align-items-center gap-1">
            <iconify-icon icon="solar:arrow-left-bold"></iconify-icon>
            {{ __('Retour à la connexion') }}
        </a>
    </div>
</form>
@endsection
