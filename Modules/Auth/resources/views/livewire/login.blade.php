<!-- Author: MEMORA solutions, https://memora.solutions ; info@memora.ca -->
<div>
    <h1 class="auth-title">{{ Settings::get('branding.login_title', __('Connexion')) }}</h1>
    @if(Settings::get('branding.login_subtitle'))
        <p class="auth-subtitle">{{ Settings::get('branding.login_subtitle') }}</p>
    @else
        <p class="auth-subtitle">{{ __('Accédez à votre compte') }}</p>
    @endif

    @if(session('status'))
        <div class="auth-alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <form wire:submit="authenticate">
        <div style="margin-bottom:1.25rem;">
            <label for="login-email" class="auth-label">{{ __('Courriel') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <input wire:model="email" type="email" id="login-email" autocomplete="email" class="auth-input" placeholder="{{ __('votre@courriel.com') }}" required autofocus>
            </div>
            @error('email')<p class="auth-error">{{ $message }}</p>@enderror
        </div>

        <div style="margin-bottom:1.25rem;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <label for="login-password" class="auth-label" style="margin-bottom:0;">{{ __('Mot de passe') }}</label>
                <a href="{{ route('password.request') }}" class="auth-link" style="font-size:0.875rem;" wire:navigate>{{ __('Mot de passe oublié ?') }}</a>
            </div>
            <div class="auth-input-group" style="margin-top:0.625rem;">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 10a2 2 0 0 0-2 2c0 1.02-.1 2.51-.26 4"/><path d="M14 13.12c0 2.38 0 6.38-1 8.88"/><path d="M17.29 21.02c.12-.6.43-2.3.5-3.02"/><path d="M2 12a10 10 0 0 1 18-6"/><path d="M2 16h.01"/><path d="M21.8 16c.2-2 .131-5.354 0-6"/><path d="M5 19.5C5.5 18 6 15 6 12a6 6 0 0 1 .34-2"/><path d="M8.65 22c.21-.66.45-1.32.57-2"/><path d="M9 6.8a6 6 0 0 1 9 5.2v2"/></svg>
                </div>
                <input wire:model="password" type="password" id="login-password" autocomplete="current-password" class="auth-input" style="padding-inline-end:3rem;" placeholder="{{ __('Mot de passe') }}" required>
                <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('login-password')" aria-label="{{ __('Afficher le mot de passe') }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password')<p class="auth-error">{{ $message }}</p>@enderror
        </div>

        <div class="auth-checkbox-group" style="margin-bottom:1.5rem;">
            <input wire:model="remember" class="auth-checkbox" type="checkbox" id="remember">
            <label for="remember" class="auth-label" style="margin-bottom:0;font-weight:400;">{{ __('Se souvenir de moi') }}</label>
        </div>

        <button type="submit" class="auth-btn" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('Se connecter') }}</span>
            <span wire:loading>{{ __('Connexion en cours...') }}</span>
        </button>
    </form>

    <div class="auth-divider">
        <span>{{ __('Ou continuer avec') }}</span>
    </div>

    <div style="display:flex;flex-direction:column;gap:0.75rem;">
        <a href="{{ route('social.redirect', 'google') }}" class="auth-social-btn">
            <div class="social-icon">
                <svg width="20" height="20" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            </div>
            <span>Google</span>
        </a>
        <a href="{{ route('social.redirect', 'github') }}" class="auth-social-btn">
            <div class="social-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 0C5.37 0 0 5.37 0 12c0 5.31 3.435 9.795 8.205 11.385.6.105.825-.255.825-.57 0-.285-.015-1.23-.015-2.235-3.015.555-3.795-.735-4.035-1.41-.135-.345-.72-1.41-1.23-1.695-.42-.225-1.02-.78-.015-.795.945-.015 1.62.87 1.845 1.23 1.08 1.815 2.805 1.305 3.495.99.105-.78.42-1.305.765-1.605-2.67-.3-5.46-1.335-5.46-5.925 0-1.305.465-2.385 1.23-3.225-.12-.3-.54-1.53.12-3.18 0 0 1.005-.315 3.3 1.23.96-.27 1.98-.405 3-.405s2.04.135 3 .405c2.295-1.56 3.3-1.23 3.3-1.23.66 1.65.24 2.88.12 3.18.765.84 1.23 1.905 1.23 3.225 0 4.605-2.805 5.625-5.475 5.925.435.375.81 1.095.81 2.22 0 1.605-.015 2.895-.015 3.3 0 .315.225.69.825.57A12.02 12.02 0 0 0 24 12c0-6.63-5.37-12-12-12z"/></svg>
            </div>
            <span>GitHub</span>
        </a>
    </div>

    <div style="text-align:center;margin-top:1.5rem;">
        <p class="auth-text-muted" style="margin-bottom:0.5rem;">{{ __('Pas de compte ?') }} <a href="{{ route('register') }}" class="auth-link" wire:navigate>{{ __('Créer un compte') }}</a></p>
        <p class="auth-text-muted"><a href="{{ route('magic-link.request') }}" class="auth-link" wire:navigate>{{ __('Connexion sans mot de passe (code)') }}</a></p>
    </div>
</div>

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
