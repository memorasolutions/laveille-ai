@extends('auth::layouts.guest')

@section('title', 'Changer votre mot de passe')

@section('content')
<div class="d-flex align-items-center gap-2 mb-24">
    <div class="w-40-px h-40-px radius-8 d-flex justify-content-center align-items-center bg-warning-100 flex-shrink-0">
        <iconify-icon icon="solar:lock-password-bold" class="text-warning-600" style="font-size:1.3rem;"></iconify-icon>
    </div>
    <div>
        <h4 class="mb-0">Changer votre mot de passe</h4>
        <p class="text-secondary-light mb-0 text-sm">Vous devez définir un nouveau mot de passe pour continuer.</p>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-warning radius-8 mb-20 d-flex align-items-center gap-2">
        <iconify-icon icon="solar:info-circle-bold" class="text-warning-main"></iconify-icon>
        <span>{{ session('status') }}</span>
    </div>
@endif

<form action="{{ route('password.force-change.update') }}" method="POST">
    @csrf

    <div class="mb-20">
        <label for="password" class="form-label fw-medium text-primary-light">Nouveau mot de passe</label>
        <div class="icon-field">
            <span class="icon top-50 translate-middle-y">
                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
            </span>
            <input
                id="password"
                name="password"
                type="password"
                class="form-control h-56-px bg-neutral-50 radius-12 @error('password') is-invalid @enderror"
                required
                autofocus
            >
        </div>
        @error('password')<div class="text-danger-main text-sm mt-4">{{ $message }}</div>@enderror
    </div>

    <div class="mb-20">
        <label for="password_confirmation" class="form-label fw-medium text-primary-light">Confirmer le mot de passe</label>
        <div class="icon-field">
            <span class="icon top-50 translate-middle-y">
                <iconify-icon icon="solar:lock-password-outline"></iconify-icon>
            </span>
            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="form-control h-56-px bg-neutral-50 radius-12"
                required
            >
        </div>
    </div>

    <button type="submit" class="btn btn-primary text-sm btn-sm px-12 py-16 w-100 radius-12 d-flex align-items-center justify-content-center gap-1">
        <iconify-icon icon="solar:check-circle-bold"></iconify-icon>
        Enregistrer le nouveau mot de passe
    </button>
</form>
@endsection
