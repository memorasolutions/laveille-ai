@extends('auth::layouts.guest')

@section('title', __('Confirmation requise'))

@section('content')
<div class="text-center mb-32">
    <div class="d-flex align-items-center justify-content-center mb-24">
        <div class="w-72-px h-72-px radius-12 d-flex justify-content-center align-items-center bg-warning-focus mx-auto">
            <iconify-icon icon="solar:lock-password-bold" class="text-warning-main" style="font-size:2rem;"></iconify-icon>
        </div>
    </div>
    <h4 class="mb-12">{{ __('Confirmation requise') }}</h4>
    <p class="text-secondary-light mb-0">
        {{ __('Cette action nécessite de confirmer votre mot de passe pour continuer.') }}
    </p>
</div>

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div class="icon-field mb-20">
        <span class="icon top-50 translate-middle-y">
            <iconify-icon icon="solar:lock-bold"></iconify-icon>
        </span>
        <input
            id="password"
            type="password"
            name="password"
            required
            autocomplete="current-password"
            placeholder="{{ __('Votre mot de passe') }}"
            class="form-control h-56-px bg-neutral-50 radius-12 @error('password') is-invalid @enderror"
        >
    </div>
    @error('password')<div class="text-danger-main text-sm mb-16">{{ $message }}</div>@enderror

    <button type="submit" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 d-flex align-items-center justify-content-center gap-1">
        <iconify-icon icon="solar:shield-check-bold"></iconify-icon>
        {{ __('Confirmer') }}
    </button>
</form>

<div class="text-center mt-24">
    <a href="{{ route('user.dashboard') }}" class="text-primary-600 text-sm text-decoration-underline d-inline-flex align-items-center gap-1">
        <iconify-icon icon="solar:arrow-left-bold"></iconify-icon>
        {{ __('Retour au tableau de bord') }}
    </a>
</div>
@endsection
