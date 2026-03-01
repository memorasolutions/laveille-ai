@extends('auth::layouts.guest')
@section('title', __('Vérification de courriel'))
@section('content')
<div class="text-center mb-5">
    <div class="d-flex align-items-center justify-content-center mb-4">
        <div class="rounded-3 d-flex justify-content-center align-items-center bg-primary bg-opacity-10 mx-auto" style="width:72px;height:72px;">
            <i data-lucide="mail" class="text-primary" style="width:32px;height:32px;"></i>
        </div>
    </div>
    <h4 class="mb-2">{{ __('Vérification de courriel') }}</h4>
    <p class="text-muted small mb-0">{{ __('Nous vous avons envoyé un lien de vérification à votre adresse courriel.') }}<br>{{ __('Vérifiez aussi vos spams.') }}</p>
</div>
@if (session('resent'))
    <div class="alert alert-success d-flex align-items-center gap-2 mb-3">
        <i data-lucide="check-circle" class="text-success"></i>
        <span>{{ __('Courriel de vérification renvoyé !') }}</span>
    </div>
@endif
<form method="POST" action="{{ route('verification.send') }}" class="mb-3">
    @csrf
    <button type="submit" class="btn btn-primary w-100 py-3">
        <i data-lucide="send" class="me-2"></i>{{ __('Renvoyer le courriel') }}
    </button>
</form>
<div class="text-center">
    <form method="POST" action="{{ route('logout') }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
            <i data-lucide="log-out"></i>{{ __('Déconnexion') }}
        </button>
    </form>
</div>
@endsection
