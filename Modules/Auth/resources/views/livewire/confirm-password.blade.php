@extends('auth::layouts.guest')
@section('title', __('Confirmation requise'))
@section('content')
<div class="text-center mb-5">
    <div class="d-flex align-items-center justify-content-center mb-4">
        <div class="rounded-3 d-flex justify-content-center align-items-center bg-warning bg-opacity-10 mx-auto" style="width:72px;height:72px;">
            <i data-lucide="lock" class="text-warning" style="width:32px;height:32px;"></i>
        </div>
    </div>
    <h4 class="mb-2">{{ __('Confirmation requise') }}</h4>
    <p class="text-muted mb-0">{{ __('Cette action nécessite de confirmer votre mot de passe pour continuer.') }}</p>
</div>
<form method="POST" action="{{ route('password.confirm') }}">
    @csrf
    <div class="input-group mb-3">
        <span class="input-group-text"><i data-lucide="lock"></i></span>
        <input id="password" type="password" name="password" required autocomplete="current-password" placeholder="{{ __('Votre mot de passe') }}" class="form-control @error('password') is-invalid @enderror">
    </div>
    @error('password')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
    <button type="submit" class="btn btn-primary w-100 py-3 d-flex align-items-center justify-content-center gap-1">
        <i data-lucide="shield-check"></i>{{ __('Confirmer') }}
    </button>
</form>
<div class="text-center mt-4">
    <a href="{{ route('user.dashboard') }}" class="text-primary text-decoration-underline d-inline-flex align-items-center gap-1">
        <i data-lucide="arrow-left"></i>{{ __('Retour au tableau de bord') }}
    </a>
</div>
@endsection
