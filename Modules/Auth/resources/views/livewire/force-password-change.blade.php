@extends('auth::layouts.guest')
@section('title', __('Changer votre mot de passe'))
@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <div class="rounded-2 d-flex justify-content-center align-items-center bg-warning bg-opacity-10 flex-shrink-0" style="width:40px;height:40px;">
        <i data-lucide="lock" class="text-warning"></i>
    </div>
    <div>
        <h4 class="mb-0">{{ __('Changer votre mot de passe') }}</h4>
        <p class="text-muted mb-0 small">{{ __('Vous devez définir un nouveau mot de passe pour continuer.') }}</p>
    </div>
</div>
@if(session('status'))
    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
        <i data-lucide="info" class="text-warning"></i><span>{{ session('status') }}</span>
    </div>
@endif
<form action="{{ route('password.force-change.update') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="password" class="form-label fw-medium text-muted">{{ __('Nouveau mot de passe') }}</label>
        <div class="input-group">
            <span class="input-group-text"><i data-lucide="key-round"></i></span>
            <input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required autofocus>
        </div>
        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="password_confirmation" class="form-label fw-medium text-muted">{{ __('Confirmer le mot de passe') }}</label>
        <div class="input-group">
            <span class="input-group-text"><i data-lucide="key-round"></i></span>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary w-100 py-3 d-flex align-items-center justify-content-center gap-1">
        <i data-lucide="check-circle"></i>{{ __('Enregistrer le nouveau mot de passe') }}
    </button>
</form>
@endsection
