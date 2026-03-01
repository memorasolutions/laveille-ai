<div>
    <h1 class="auth-title">{{ __('Réinitialiser le mot de passe') }}</h1>
    <p class="auth-subtitle">{{ __('Choisissez un nouveau mot de passe pour votre compte.') }}</p>

    <form wire:submit="resetPassword">
        <div style="margin-bottom:1.25rem;">
            <label for="reset-email" class="auth-label">{{ __('Courriel') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                </div>
                <input wire:model="email" type="email" id="reset-email" autocomplete="email" class="auth-input" required>
            </div>
            @error('email')<p class="auth-error">{{ $message }}</p>@enderror
        </div>

        <div style="margin-bottom:1.25rem;">
            <label for="reset-password" class="auth-label">{{ __('Nouveau mot de passe') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <input wire:model="password" type="password" id="reset-password" class="auth-input" style="padding-inline-end:3rem;" placeholder="{{ __('Nouveau mot de passe') }}" required>
                <button type="button" class="toggle-password-btn" onclick="togglePasswordVisibility('reset-password')" aria-label="{{ __('Afficher le mot de passe') }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
            </div>
            @error('password')<p class="auth-error">{{ $message }}</p>@enderror
        </div>

        <div style="margin-bottom:1.25rem;">
            <label for="reset-password-confirm" class="auth-label">{{ __('Confirmer le mot de passe') }}</label>
            <div class="auth-input-group">
                <div class="auth-input-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                </div>
                <input wire:model="password_confirmation" type="password" id="reset-password-confirm" class="auth-input" placeholder="{{ __('Confirmer le mot de passe') }}" required>
            </div>
        </div>

        <button type="submit" class="auth-btn" wire:loading.attr="disabled">
            <span wire:loading.remove>{{ __('Réinitialiser') }}</span>
            <span wire:loading>{{ __('Réinitialisation...') }}</span>
        </button>
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
</div>
