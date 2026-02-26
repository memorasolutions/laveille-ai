@extends('auth::layouts.guest')

@section('title', __('Vérification de courriel'))

@section('content')
<div class="text-center mb-32">
    <div class="d-flex align-items-center justify-content-center mb-24">
        <div class="w-72-px h-72-px radius-12 d-flex justify-content-center align-items-center bg-primary-100 mx-auto">
            <iconify-icon icon="solar:letter-bold" class="text-primary-600" style="font-size:2rem;"></iconify-icon>
        </div>
    </div>
    <h4 class="mb-12">{{ __('Vérification de courriel') }}</h4>
    <p class="text-secondary-light text-sm mb-0">
        {{ __('Nous vous avons envoyé un lien de vérification à votre adresse courriel.') }}<br>{{ __('Vérifiez aussi vos spams.') }}
    </p>
</div>

@if (session('resent'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-20 radius-8">
        <iconify-icon icon="solar:check-circle-bold" class="text-success-main" style="font-size:1.1rem;"></iconify-icon>
        <span>{{ __('Courriel de vérification renvoyé !') }}</span>
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}" class="mb-16">
    @csrf
    <button type="submit" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12">
        <iconify-icon icon="solar:letter-send-bold" class="me-2"></iconify-icon>
        {{ __('Renvoyer le courriel') }}
    </button>
</form>

<div class="text-center">
    <form method="POST" action="{{ route('logout') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-secondary radius-8 btn-sm px-16 d-inline-flex align-items-center gap-1">
            <iconify-icon icon="solar:logout-2-bold"></iconify-icon>
            {{ __('Déconnexion') }}
        </button>
    </form>
</div>
@endsection
