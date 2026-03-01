@extends('auth::layouts.guest')
@section('title', __('Changer votre mot de passe'))
@section('content')
<h1 class="auth-title">{{ __('Changer votre mot de passe') }}</h1>
<p class="auth-subtitle">{{ __('Vous devez définir un nouveau mot de passe pour continuer.') }}</p>

@if(session('status'))
    <div class="auth-alert-error" role="alert">{{ session('status') }}</div>
@endif

<form action="{{ route('password.force-change.update') }}" method="POST">
    @csrf
    <div style="margin-bottom:1.25rem;">
        <label for="force-password" class="auth-label">{{ __('Nouveau mot de passe') }}</label>
        <div class="auth-input-group">
            <div class="auth-input-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <input id="force-password" name="password" type="password" class="auth-input" style="padding-inline-end:3rem;" required autofocus>
            <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('force-password')" aria-label="{{ __('Afficher le mot de passe') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            </button>
        </div>
        @error('password')<p class="auth-error">{{ $message }}</p>@enderror
    </div>

    <div style="margin-bottom:1.25rem;">
        <label for="force-password-confirm" class="auth-label">{{ __('Confirmer le mot de passe') }}</label>
        <div class="auth-input-group">
            <div class="auth-input-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
            </div>
            <input id="force-password-confirm" name="password_confirmation" type="password" class="auth-input" required>
        </div>
    </div>

    <button type="submit" class="auth-btn">{{ __('Enregistrer le nouveau mot de passe') }}</button>
</form>

@push('scripts')
<script>
function togglePasswordVisibility(id) {
    var input = document.getElementById(id);
    var btn = input.parentElement.querySelector('.toggle-password-btn');
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>';
    }
}
</script>
@endpush
@endsection
